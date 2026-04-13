<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

session_start();
header('Content-Type: application/json');

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function saveImageUpload(array $file, string $prefix): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $tmp = (string)($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($tmp);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    if (!isset($allowed[$mime])) {
        return null;
    }

    $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
        return null;
    }

    $filename = sprintf('%s_%s.%s', $prefix, bin2hex(random_bytes(8)), $allowed[$mime]);
    $target = $uploadDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($tmp, $target)) {
        return null;
    }

    return 'uploads/' . $filename;
}

function slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? '';
    $value = trim($value, '-');
    if ($value === '') {
        $value = 'portfolio';
    }
    return substr($value, 0, 90);
}

function uniqueSlug(PDO $pdo, string $base): string
{
    $slug = $base;
    $i = 1;
    $stmt = $pdo->prepare('SELECT id FROM portfolios WHERE slug = :slug LIMIT 1');
    while (true) {
        $stmt->execute(['slug' => $slug]);
        if ($stmt->fetch() === false) {
            return $slug;
        }
        $i++;
        $slug = substr($base, 0, 80) . '-' . $i;
    }
}

if (!isset($_SESSION['user_id'])) {
    respond(401, ['ok' => false, 'message' => 'Unauthorized']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['ok' => false, 'message' => 'Method not allowed']);
}

$rawData = trim((string)($_POST['data'] ?? ''));
if ($rawData === '') {
    $rawData = trim((string)file_get_contents('php://input'));
}

if ($rawData === '') {
    respond(400, ['ok' => false, 'message' => 'Empty request body']);
}

$data = json_decode($rawData, true);
if (!is_array($data)) {
    respond(400, ['ok' => false, 'message' => 'Invalid JSON']);
}

$firstName = trim((string)($data['firstName'] ?? ''));
$lastName = trim((string)($data['lastName'] ?? ''));
$jobTitle = trim((string)($data['jobTitle'] ?? ''));
$email = trim((string)($data['email'] ?? ''));

if ($firstName === '' || $lastName === '' || $jobTitle === '' || $email === '') {
    respond(422, ['ok' => false, 'message' => 'Missing required fields']);
}

$profilePhoto = trim((string)($data['profilePhoto'] ?? ''));
$location = trim((string)($data['location'] ?? ''));
$website = trim((string)($data['website'] ?? ''));
$bioShort = trim((string)($data['bioShort'] ?? ''));
$bioLong = trim((string)($data['bioLong'] ?? ''));
$yearsExp = trim((string)($data['yearsExp'] ?? ''));
$phone = trim((string)($data['phone'] ?? ''));
$portfolioTitle = trim((string)($data['portfolioTitle'] ?? ''));
$portfolioTheme = trim((string)($data['portfolioTheme'] ?? 'aurora'));
$isPublic = !empty($data['isPublic']) ? 1 : 0;

if (!in_array($portfolioTheme, ['aurora', 'midnight', 'sunset'], true)) {
    $portfolioTheme = 'aurora';
}

$skills = is_array($data['skills'] ?? null) ? $data['skills'] : [];
$projects = is_array($data['projects'] ?? null) ? $data['projects'] : [];

$socialMap = [
    'github' => trim((string)($data['github'] ?? '')),
    'linkedin' => trim((string)($data['linkedin'] ?? '')),
    'twitter' => trim((string)($data['twitter'] ?? '')),
    'instagram' => trim((string)($data['instagram'] ?? '')),
];

try {
    $pdo = db();

    // Ensure core tables exist for fresh or partially migrated databases.
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS portfolios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            portfolio_title VARCHAR(120) NULL,
            slug VARCHAR(140) NULL,
            is_public TINYINT(1) NOT NULL DEFAULT 0,
            theme_name VARCHAR(30) NOT NULL DEFAULT 'aurora',
            job_title VARCHAR(100),
            profile_photo_url TEXT,
            location VARCHAR(80),
            website_url TEXT,
            bio_short VARCHAR(120),
            bio_long LONGTEXT,
            years_exp VARCHAR(20),
            phone VARCHAR(20),
            email VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS skills (
            id INT AUTO_INCREMENT PRIMARY KEY,
            portfolio_id INT NOT NULL,
            skill_name VARCHAR(50) NOT NULL,
            proficiency_level INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            portfolio_id INT NOT NULL,
            display_order INT NOT NULL DEFAULT 0,
            project_title VARCHAR(100) NOT NULL,
            project_description LONGTEXT,
            project_url TEXT,
            project_image_url TEXT,
            repo_url TEXT,
            tags VARCHAR(200),
            is_featured TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS social_links (
            id INT AUTO_INCREMENT PRIMARY KEY,
            portfolio_id INT NOT NULL,
            platform_name VARCHAR(30),
            profile_url TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE
        )"
    );

    // Backward compatible migration for featured projects.
    try {
        $pdo->exec('ALTER TABLE projects ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0');
    } catch (Throwable $ignored) {
    }

    try {
        $pdo->exec('ALTER TABLE projects ADD COLUMN repo_url TEXT NULL');
    } catch (Throwable $ignored) {
    }

    try {
        $pdo->exec('ALTER TABLE projects ADD COLUMN tags VARCHAR(200) NULL');
    } catch (Throwable $ignored) {
    }

    try {
        $pdo->exec('ALTER TABLE projects ADD COLUMN display_order INT NOT NULL DEFAULT 0');
    } catch (Throwable $ignored) {
    }

    try {
        $pdo->exec("ALTER TABLE portfolios ADD COLUMN portfolio_title VARCHAR(120) NULL");
    } catch (Throwable $ignored) {
    }

    try {
        $pdo->exec("ALTER TABLE portfolios ADD COLUMN slug VARCHAR(140) NULL");
    } catch (Throwable $ignored) {
    }

    try {
        $pdo->exec("ALTER TABLE portfolios ADD COLUMN public_code VARCHAR(20) NULL UNIQUE");
    } catch (Throwable $ignored) {
    }

    try {
        $pdo->exec("ALTER TABLE portfolios ADD COLUMN is_public TINYINT(1) NOT NULL DEFAULT 0");
    } catch (Throwable $ignored) {
    }

    try {
        $pdo->exec("ALTER TABLE portfolios ADD COLUMN theme_name VARCHAR(30) NOT NULL DEFAULT 'aurora'");
    } catch (Throwable $ignored) {
    }

    try {
        $pdo->exec("ALTER TABLE social_links ADD COLUMN platform_name VARCHAR(30) NULL");
    } catch (Throwable $ignored) {
    }

    $pdo->beginTransaction();

    $userId = (int)$_SESSION['user_id'];

    $baseSlug = slugify($portfolioTitle !== '' ? $portfolioTitle : ($firstName . '-' . $lastName));
    $slug = uniqueSlug($pdo, $baseSlug);

    $portfolioStmt = $pdo->prepare(
        'INSERT INTO portfolios (
            user_id, portfolio_title, slug, is_public, theme_name, job_title, profile_photo_url, location, website_url,
            bio_short, bio_long, years_exp, phone, email
        ) VALUES (
            :user_id, :portfolio_title, :slug, :is_public, :theme_name, :job_title, :profile_photo_url, :location, :website_url,
            :bio_short, :bio_long, :years_exp, :phone, :email
        )'
    );

    $portfolioStmt->execute([
        'user_id' => $userId,
        'portfolio_title' => $portfolioTitle !== '' ? $portfolioTitle : null,
        'slug' => $slug,
        'is_public' => $isPublic,
        'theme_name' => $portfolioTheme,
        'job_title' => $jobTitle,
        'profile_photo_url' => null,
        'location' => $location !== '' ? $location : null,
        'website_url' => $website !== '' ? $website : null,
        'bio_short' => $bioShort !== '' ? $bioShort : null,
        'bio_long' => $bioLong !== '' ? $bioLong : null,
        'years_exp' => $yearsExp !== '' ? $yearsExp : null,
        'phone' => $phone !== '' ? $phone : null,
        'email' => $email,
    ]);

    $portfolioId = (int)$pdo->lastInsertId();
    $publicCode = 'PG-' . str_pad((string)$portfolioId, 6, '0', STR_PAD_LEFT);

    try {
        $publicCodeStmt = $pdo->prepare('UPDATE portfolios SET public_code = :public_code WHERE id = :id');
        $publicCodeStmt->execute([
            'public_code' => $publicCode,
            'id' => $portfolioId,
        ]);
    } catch (Throwable $ignored) {
    }

    $uploadedProfilePath = isset($_FILES['profile_photo']) && is_array($_FILES['profile_photo'])
        ? saveImageUpload($_FILES['profile_photo'], 'profile_' . $userId)
        : null;

    $finalProfilePath = $uploadedProfilePath ?? ($profilePhoto !== '' ? $profilePhoto : null);
    if ($finalProfilePath !== null) {
        $updatePortfolioPhoto = $pdo->prepare('UPDATE portfolios SET profile_photo_url = :url WHERE id = :id');
        $updatePortfolioPhoto->execute([
            'url' => $finalProfilePath,
            'id' => $portfolioId,
        ]);
    }

    if (!empty($skills)) {
        $skillStmt = $pdo->prepare(
            'INSERT INTO skills (portfolio_id, skill_name, proficiency_level)
             VALUES (:portfolio_id, :skill_name, :proficiency_level)'
        );

        foreach ($skills as $skill) {
            if (!is_array($skill)) {
                continue;
            }
            $name = trim((string)($skill['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $level = (int)($skill['level'] ?? 0);
            $level = max(0, min(100, $level));

            $skillStmt->execute([
                'portfolio_id' => $portfolioId,
                'skill_name' => $name,
                'proficiency_level' => $level,
            ]);
        }
    }

    if (!empty($projects)) {
        $projectStmt = $pdo->prepare(
            'INSERT INTO projects (
                portfolio_id, project_title, project_description, project_url,
                     project_image_url, repo_url, tags, is_featured, display_order
             ) VALUES (
                :portfolio_id, :project_title, :project_description, :project_url,
                     :project_image_url, :repo_url, :tags, :is_featured, :display_order
             )'
        );

        foreach ($projects as $idx => $project) {
            if (!is_array($project)) {
                continue;
            }

            $title = trim((string)($project['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $existingProjectImage = trim((string)($project['image'] ?? ''));
            $uploadedProjectPath = null;
            if (isset($_FILES['project_images']) && is_array($_FILES['project_images']['name'] ?? null)) {
                if (isset($_FILES['project_images']['error'][$idx]) && $_FILES['project_images']['error'][$idx] === UPLOAD_ERR_OK) {
                    $singleFile = [
                        'name' => $_FILES['project_images']['name'][$idx] ?? '',
                        'type' => $_FILES['project_images']['type'][$idx] ?? '',
                        'tmp_name' => $_FILES['project_images']['tmp_name'][$idx] ?? '',
                        'error' => $_FILES['project_images']['error'][$idx] ?? UPLOAD_ERR_NO_FILE,
                        'size' => $_FILES['project_images']['size'][$idx] ?? 0,
                    ];
                    $uploadedProjectPath = saveImageUpload($singleFile, 'project_' . $userId);
                }
            }

            $finalProjectImage = $uploadedProjectPath ?? ($existingProjectImage !== '' ? $existingProjectImage : null);
            $isFeatured = !empty($project['featured']) ? 1 : 0;

            $projectStmt->execute([
                'portfolio_id' => $portfolioId,
                'project_title' => $title,
                'project_description' => trim((string)($project['description'] ?? '')) ?: null,
                'project_url' => trim((string)($project['demo'] ?? '')) ?: null,
                'project_image_url' => $finalProjectImage,
                'repo_url' => trim((string)($project['repo'] ?? '')) ?: null,
                'tags' => trim((string)($project['tags'] ?? '')) ?: null,
                'is_featured' => $isFeatured,
                'display_order' => (int)($project['order'] ?? $idx),
            ]);
        }
    }

    $socialStmt = $pdo->prepare(
        'INSERT INTO social_links (portfolio_id, platform_name, profile_url)
         VALUES (:portfolio_id, :platform_name, :profile_url)'
    );

    foreach ($socialMap as $platform => $url) {
        if ($url === '') {
            continue;
        }

        $socialStmt->execute([
            'portfolio_id' => $portfolioId,
            'platform_name' => $platform,
            'profile_url' => $url,
        ]);
    }

    $pdo->commit();
    respond(200, ['ok' => true, 'message' => 'Portfolio created', 'slug' => $slug, 'portfolioId' => $portfolioId, 'publicCode' => $publicCode]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('save_portfolio.php error: ' . $e->getMessage());
    respond(500, ['ok' => false, 'message' => 'Server error']);
}
