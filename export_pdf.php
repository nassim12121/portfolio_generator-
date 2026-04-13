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

$pdo = db();
$userId = (int)$_SESSION['user_id'];
$requestedPortfolioId = (int)($_GET['id'] ?? 0);

if ($requestedPortfolioId > 0) {
    $stmt = $pdo->prepare(
        'SELECT p.id, p.portfolio_title, p.job_title, p.bio_short, p.bio_long, p.location, p.website_url, p.phone, p.email, u.first_name, u.last_name
         FROM portfolios p
         INNER JOIN users u ON u.id = p.user_id
         WHERE p.user_id = :user_id AND p.id = :id
         LIMIT 1'
    );
    $stmt->execute(['user_id' => $userId, 'id' => $requestedPortfolioId]);
} else {
    $stmt = $pdo->prepare(
        'SELECT p.id, p.portfolio_title, p.job_title, p.bio_short, p.bio_long, p.location, p.website_url, p.phone, p.email, u.first_name, u.last_name
         FROM portfolios p
         INNER JOIN users u ON u.id = p.user_id
         WHERE p.user_id = :user_id
         ORDER BY p.id DESC
         LIMIT 1'
    );
    $stmt->execute(['user_id' => $userId]);
}
$portfolio = $stmt->fetch();

if ($portfolio === false) {
    exit('Portfolio not found');
}

$portfolioId = (int)$portfolio['id'];
$name = trim((string)$portfolio['first_name'] . ' ' . (string)$portfolio['last_name']);
$title = trim((string)($portfolio['portfolio_title'] ?? '')) ?: ($name . ' Portfolio');

$skillsStmt = $pdo->prepare('SELECT skill_name, proficiency_level FROM skills WHERE portfolio_id = :id ORDER BY id ASC');
$skillsStmt->execute(['id' => $portfolioId]);
$skills = $skillsStmt->fetchAll();

$projectsStmt = $pdo->prepare('SELECT project_title, project_description, tags FROM projects WHERE portfolio_id = :id ORDER BY display_order ASC, id ASC');
try {
    $projectsStmt->execute(['id' => $portfolioId]);
} catch (Throwable $ignored) {
    $projectsStmt = $pdo->prepare('SELECT project_title, project_description, tags FROM projects WHERE portfolio_id = :id ORDER BY id ASC');
    $projectsStmt->execute(['id' => $portfolioId]);
}
$projects = $projectsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo safeText($title); ?> - PDF Export</title>
  <style>
    @page { size: A4; margin: 16mm; }
    body { font-family: Arial, sans-serif; color: #111827; }
    .toolbar { margin-bottom: 14px; }
    .toolbar button { border: 0; background: #2563eb; color: #fff; padding: 8px 12px; border-radius: 6px; cursor: pointer; }
    .muted { color: #6b7280; }
    h1 { margin: 0; font-size: 28px; }
    h2 { margin-top: 18px; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px; font-size: 18px; }
    .skill { display: flex; justify-content: space-between; border: 1px solid #e5e7eb; border-radius: 8px; padding: 6px 9px; margin-bottom: 6px; }
    .project { border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px; margin-bottom: 8px; }
    .tags { margin-top: 6px; }
    .tag { display: inline-block; border: 1px solid #d1d5db; border-radius: 99px; padding: 2px 8px; margin: 0 4px 4px 0; font-size: 12px; }
    @media print { .toolbar { display: none; } }
  </style>
</head>
<body>
  <div class="toolbar">
    <button onclick="window.print()">Print / Save as PDF</button>
  </div>

  <h1><?php echo safeText($name); ?></h1>
  <p class="muted"><?php echo safeText((string)($portfolio['job_title'] ?? '')); ?></p>
  <?php if ((string)($portfolio['bio_short'] ?? '') !== ''): ?><p><?php echo safeText((string)$portfolio['bio_short']); ?></p><?php endif; ?>

  <h2>About</h2>
  <p><?php echo nl2br(safeText((string)($portfolio['bio_long'] ?? ''))); ?></p>

  <h2>Skills</h2>
  <?php foreach ($skills as $skill): ?>
    <div class="skill">
      <span><?php echo safeText((string)$skill['skill_name']); ?></span>
      <strong><?php echo (int)$skill['proficiency_level']; ?>%</strong>
    </div>
  <?php endforeach; ?>

  <h2>Projects</h2>
  <?php foreach ($projects as $project): ?>
    <div class="project">
      <strong><?php echo safeText((string)$project['project_title']); ?></strong>
      <?php if ((string)($project['project_description'] ?? '') !== ''): ?><p><?php echo safeText((string)$project['project_description']); ?></p><?php endif; ?>
      <?php $tags = array_filter(array_map('trim', explode(',', (string)($project['tags'] ?? '')))); ?>
      <?php if (!empty($tags)): ?>
        <div class="tags">
          <?php foreach ($tags as $tag): ?><span class="tag"><?php echo safeText($tag); ?></span><?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <h2>Contact</h2>
  <?php if ((string)($portfolio['email'] ?? '') !== ''): ?><div>Email: <?php echo safeText((string)$portfolio['email']); ?></div><?php endif; ?>
  <?php if ((string)($portfolio['phone'] ?? '') !== ''): ?><div>Phone: <?php echo safeText((string)$portfolio['phone']); ?></div><?php endif; ?>
  <?php if ((string)($portfolio['website_url'] ?? '') !== ''): ?><div>Website: <?php echo safeText((string)$portfolio['website_url']); ?></div><?php endif; ?>

  <script>
    if (new URLSearchParams(window.location.search).get('autoprint') === '1') {
      window.print();
    }
  </script>
</body>
</html>
