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

if (!isset($_SESSION['user_id'])) {
    respond(401, ['ok' => false, 'message' => 'Unauthorized']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(405, ['ok' => false, 'message' => 'Method not allowed']);
}

try {
    $pdo = db();
    $userId = (int)$_SESSION['user_id'];
    $requestedPortfolioId = (int)($_GET['id'] ?? 0);

    $userStmt = $pdo->prepare('SELECT first_name, last_name FROM users WHERE id = :id LIMIT 1');
    $userStmt->execute(['id' => $userId]);
    $user = $userStmt->fetch();

    try {
        if ($requestedPortfolioId > 0) {
            $portfolioStmt = $pdo->prepare(
                'SELECT id, portfolio_title, is_public, theme_name, job_title, profile_photo_url, location, website_url, bio_short, bio_long, years_exp, phone, email
                 FROM portfolios
                 WHERE user_id = :user_id AND id = :id
                 LIMIT 1'
            );
            $portfolioStmt->execute(['user_id' => $userId, 'id' => $requestedPortfolioId]);
        } else {
            $portfolioStmt = $pdo->prepare(
                'SELECT id, portfolio_title, is_public, theme_name, job_title, profile_photo_url, location, website_url, bio_short, bio_long, years_exp, phone, email
                 FROM portfolios
                 WHERE user_id = :user_id
                 ORDER BY id DESC
                 LIMIT 1'
            );
            $portfolioStmt->execute(['user_id' => $userId]);
        }
        $portfolio = $portfolioStmt->fetch();
    } catch (Throwable $ignored) {
        if ($requestedPortfolioId > 0) {
            $portfolioStmt = $pdo->prepare(
                'SELECT id, job_title, profile_photo_url, location, website_url, bio_short, bio_long, years_exp, phone, email
                 FROM portfolios
                 WHERE user_id = :user_id AND id = :id
                 LIMIT 1'
            );
            $portfolioStmt->execute(['user_id' => $userId, 'id' => $requestedPortfolioId]);
        } else {
            $portfolioStmt = $pdo->prepare(
                'SELECT id, job_title, profile_photo_url, location, website_url, bio_short, bio_long, years_exp, phone, email
                 FROM portfolios
                 WHERE user_id = :user_id
                 ORDER BY id DESC
                 LIMIT 1'
            );
            $portfolioStmt->execute(['user_id' => $userId]);
        }
        $portfolio = $portfolioStmt->fetch();
    }

    if ($portfolio === false) {
        respond(200, ['ok' => true, 'hasPortfolio' => false]);
    }

    $portfolioId = (int)$portfolio['id'];

    $skillsStmt = $pdo->prepare(
        'SELECT skill_name, proficiency_level
         FROM skills
         WHERE portfolio_id = :portfolio_id
         ORDER BY id ASC'
    );
    $skillsStmt->execute(['portfolio_id' => $portfolioId]);
    $skillsRows = $skillsStmt->fetchAll();

    try {
        $projectsStmt = $pdo->prepare(
              'SELECT project_title, project_description, project_url, project_image_url, repo_url, tags, is_featured, display_order
             FROM projects
             WHERE portfolio_id = :portfolio_id
               ORDER BY display_order ASC, id ASC'
        );
        $projectsStmt->execute(['portfolio_id' => $portfolioId]);
        $projectRows = $projectsStmt->fetchAll();
    } catch (Throwable $ignored) {
        $projectsStmt = $pdo->prepare(
            'SELECT project_title, project_description, project_url, project_image_url, repo_url, tags
             FROM projects
             WHERE portfolio_id = :portfolio_id
             ORDER BY id ASC'
        );
        $projectsStmt->execute(['portfolio_id' => $portfolioId]);
        $projectRows = $projectsStmt->fetchAll();
    }

    $socialStmt = $pdo->prepare(
        'SELECT platform_name, profile_url
         FROM social_links
         WHERE portfolio_id = :portfolio_id'
    );
    $socialStmt->execute(['portfolio_id' => $portfolioId]);
    $socialRows = $socialStmt->fetchAll();

    $social = [
        'github' => '',
        'linkedin' => '',
        'twitter' => '',
        'instagram' => '',
    ];

    foreach ($socialRows as $row) {
        $platform = strtolower(trim((string)($row['platform_name'] ?? '')));
        if (array_key_exists($platform, $social)) {
            $social[$platform] = (string)($row['profile_url'] ?? '');
        }
    }

    $skills = [];
    foreach ($skillsRows as $row) {
        $skills[] = [
            'name' => (string)($row['skill_name'] ?? ''),
            'level' => (int)($row['proficiency_level'] ?? 0),
        ];
    }

    $projects = [];
    foreach ($projectRows as $row) {
        $projects[] = [
            'title' => (string)($row['project_title'] ?? ''),
            'description' => (string)($row['project_description'] ?? ''),
            'demo' => (string)($row['project_url'] ?? ''),
            'image' => (string)($row['project_image_url'] ?? ''),
            'repo' => (string)($row['repo_url'] ?? ''),
            'tags' => (string)($row['tags'] ?? ''),
            'featured' => (int)($row['is_featured'] ?? 0) === 1,
        ];
    }

    respond(200, [
        'ok' => true,
        'hasPortfolio' => true,
        'data' => [
            'id' => $portfolioId,
            'firstName' => (string)($user['first_name'] ?? ''),
            'lastName' => (string)($user['last_name'] ?? ''),
            'portfolioTitle' => (string)($portfolio['portfolio_title'] ?? ''),
            'isPublic' => (int)($portfolio['is_public'] ?? 0) === 1,
            'portfolioTheme' => (string)($portfolio['theme_name'] ?? 'aurora'),
            'jobTitle' => (string)($portfolio['job_title'] ?? ''),
            'profilePhoto' => (string)($portfolio['profile_photo_url'] ?? ''),
            'location' => (string)($portfolio['location'] ?? ''),
            'website' => (string)($portfolio['website_url'] ?? ''),
            'bioShort' => (string)($portfolio['bio_short'] ?? ''),
            'bioLong' => (string)($portfolio['bio_long'] ?? ''),
            'yearsExp' => (string)($portfolio['years_exp'] ?? ''),
            'skills' => $skills,
            'projects' => $projects,
            'email' => (string)($portfolio['email'] ?? ''),
            'phone' => (string)($portfolio['phone'] ?? ''),
            'github' => $social['github'],
            'linkedin' => $social['linkedin'],
            'twitter' => $social['twitter'],
            'instagram' => $social['instagram'],
        ],
    ]);
} catch (Throwable $e) {
    respond(500, ['ok' => false, 'message' => 'Server error']);
}
