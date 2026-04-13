<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

function safeText($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function safeUrl($value): string
{
    $url = trim((string)$value);
    if ($url === '') {
        return '';
    }
    if (preg_match('/^(uploads\/|\.\/uploads\/|\/uploads\/)/i', $url)) {
        return $url;
    }
    if (!preg_match('/^https?:\/\//i', $url)) {
        $url = 'https://' . $url;
    }
    return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
}

$pdo = db();
$userId = (int)$_SESSION['user_id'];
$requestedPortfolioId = (int)($_GET['id'] ?? 0);

try {
    if ($requestedPortfolioId > 0) {
        $portfolioStmt = $pdo->prepare(
            'SELECT p.id, p.portfolio_title, p.job_title, p.profile_photo_url, p.location, p.website_url, p.bio_short, p.bio_long, p.years_exp, p.phone, p.email,
                    u.first_name, u.last_name
             FROM portfolios p
             INNER JOIN users u ON u.id = p.user_id
             WHERE p.user_id = :user_id AND p.id = :id
             LIMIT 1'
        );
        $portfolioStmt->execute(['user_id' => $userId, 'id' => $requestedPortfolioId]);
    } else {
        $portfolioStmt = $pdo->prepare(
            'SELECT p.id, p.portfolio_title, p.job_title, p.profile_photo_url, p.location, p.website_url, p.bio_short, p.bio_long, p.years_exp, p.phone, p.email,
                    u.first_name, u.last_name
             FROM portfolios p
             INNER JOIN users u ON u.id = p.user_id
             WHERE p.user_id = :user_id
             ORDER BY p.id DESC
             LIMIT 1'
        );
        $portfolioStmt->execute(['user_id' => $userId]);
    }
    $portfolio = $portfolioStmt->fetch();
} catch (Throwable $ignored) {
    if ($requestedPortfolioId > 0) {
        $portfolioStmt = $pdo->prepare(
            'SELECT p.id, p.job_title, p.profile_photo_url, p.location, p.website_url, p.bio_short, p.bio_long, p.years_exp, p.phone, p.email,
                    u.first_name, u.last_name
             FROM portfolios p
             INNER JOIN users u ON u.id = p.user_id
             WHERE p.user_id = :user_id AND p.id = :id
             LIMIT 1'
        );
        $portfolioStmt->execute(['user_id' => $userId, 'id' => $requestedPortfolioId]);
    } else {
        $portfolioStmt = $pdo->prepare(
            'SELECT p.id, p.job_title, p.profile_photo_url, p.location, p.website_url, p.bio_short, p.bio_long, p.years_exp, p.phone, p.email,
                    u.first_name, u.last_name
             FROM portfolios p
             INNER JOIN users u ON u.id = p.user_id
             WHERE p.user_id = :user_id
             ORDER BY p.id DESC
             LIMIT 1'
        );
        $portfolioStmt->execute(['user_id' => $userId]);
    }
    $portfolio = $portfolioStmt->fetch();
}

if ($portfolio === false) {
    header('Location: preview.php');
    exit;
}

$portfolioId = (int)$portfolio['id'];
$userName = trim(((string)$portfolio['first_name']) . ' ' . ((string)$portfolio['last_name']));
$title = trim((string)($portfolio['portfolio_title'] ?? '')) !== '' ? (string)$portfolio['portfolio_title'] : ($userName . ' Portfolio');

$skillsStmt = $pdo->prepare('SELECT skill_name, proficiency_level FROM skills WHERE portfolio_id = :portfolio_id ORDER BY id ASC');
$skillsStmt->execute(['portfolio_id' => $portfolioId]);
$skills = $skillsStmt->fetchAll();

try {
    $projectsStmt = $pdo->prepare('SELECT project_title, project_description, project_url, repo_url, tags FROM projects WHERE portfolio_id = :portfolio_id ORDER BY display_order ASC, id ASC');
    $projectsStmt->execute(['portfolio_id' => $portfolioId]);
    $projects = $projectsStmt->fetchAll();
} catch (Throwable $ignored) {
    $projectsStmt = $pdo->prepare('SELECT project_title, project_description, project_url, repo_url, tags FROM projects WHERE portfolio_id = :portfolio_id ORDER BY id ASC');
    $projectsStmt->execute(['portfolio_id' => $portfolioId]);
    $projects = $projectsStmt->fetchAll();
}

$socialStmt = $pdo->prepare('SELECT platform_name, profile_url FROM social_links WHERE portfolio_id = :portfolio_id ORDER BY id ASC');
$socialStmt->execute(['portfolio_id' => $portfolioId]);
$social = $socialStmt->fetchAll();

$content = "<!DOCTYPE html>\n";
$content .= "<html lang=\"en\">\n<head>\n<meta charset=\"UTF-8\"/>\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"/>\n";
$content .= "<title>" . safeText($title) . "</title>\n";
$content .= "<style>body{font-family:Arial,sans-serif;margin:40px;color:#1f2937}h1{margin:0}h2{margin-top:24px;border-bottom:1px solid #ddd;padding-bottom:6px}.muted{color:#6b7280}.card{border:1px solid #e5e7eb;border-radius:10px;padding:14px;margin:10px 0}.pill{display:inline-block;background:#eef2ff;border:1px solid #c7d2fe;border-radius:99px;padding:2px 8px;margin-right:6px;font-size:12px}</style>\n";
$content .= "</head>\n<body>\n";
$content .= "<h1>" . safeText($userName) . "</h1>\n";
$content .= "<p class=\"muted\">" . safeText((string)($portfolio['job_title'] ?? '')) . "</p>\n";
$content .= "<p>" . nl2br(safeText((string)($portfolio['bio_short'] ?? ''))) . "</p>\n";
$content .= "<h2>About</h2>\n<p>" . nl2br(safeText((string)($portfolio['bio_long'] ?? ''))) . "</p>\n";
$content .= "<h2>Skills</h2>\n";
foreach ($skills as $s) {
    $content .= "<div class=\"card\"><strong>" . safeText((string)$s['skill_name']) . "</strong> - " . (int)$s['proficiency_level'] . "%</div>\n";
}
$content .= "<h2>Projects</h2>\n";
foreach ($projects as $p) {
    $content .= "<div class=\"card\"><strong>" . safeText((string)$p['project_title']) . "</strong><br/>";
    $content .= safeText((string)($p['project_description'] ?? '')) . "<br/>";
    $tags = array_filter(array_map('trim', explode(',', (string)($p['tags'] ?? ''))));
    foreach ($tags as $tag) {
        $content .= "<span class=\"pill\">" . safeText($tag) . "</span>";
    }
    $demo = safeUrl((string)($p['project_url'] ?? ''));
    $repo = safeUrl((string)($p['repo_url'] ?? ''));
    if ($demo !== '') {
        $content .= "<br/><a href=\"" . safeText($demo) . "\">Demo</a>";
    }
    if ($repo !== '') {
        $content .= " | <a href=\"" . safeText($repo) . "\">Source</a>";
    }
    $content .= "</div>\n";
}

$content .= "<h2>Contact & Social</h2>\n";
if ((string)($portfolio['email'] ?? '') !== '') {
    $content .= "<div>Email: " . safeText((string)$portfolio['email']) . "</div>\n";
}
if ((string)($portfolio['phone'] ?? '') !== '') {
    $content .= "<div>Phone: " . safeText((string)$portfolio['phone']) . "</div>\n";
}
foreach ($social as $row) {
    $url = safeUrl((string)($row['profile_url'] ?? ''));
    if ($url !== '') {
        $content .= "<div>" . safeText(ucfirst((string)($row['platform_name'] ?? 'Profile'))) . ": <a href=\"" . safeText($url) . "\">" . safeText($url) . "</a></div>\n";
    }
}

$content .= "</body>\n</html>";

$filename = preg_replace('/[^a-z0-9_-]+/i', '_', $title) . '.html';
header('Content-Type: text/html; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo $content;
