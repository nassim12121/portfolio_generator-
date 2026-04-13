<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

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

$slug = trim((string)($_GET['slug'] ?? ''));
if ($slug === '') {
    http_response_code(404);
    exit('Portfolio not found');
}

$pdo = db();

$portfolioStmt = $pdo->prepare(
    'SELECT p.id, p.user_id, p.portfolio_title, p.theme_name, p.job_title, p.profile_photo_url, p.location, p.website_url, p.bio_short, p.bio_long, p.years_exp, p.phone, p.email,
            u.first_name, u.last_name
     FROM portfolios p
     INNER JOIN users u ON u.id = p.user_id
     WHERE p.slug = :slug AND p.is_public = 1
     LIMIT 1'
);
$portfolioStmt->execute(['slug' => $slug]);
$portfolio = $portfolioStmt->fetch();

if ($portfolio === false) {
    http_response_code(404);
    exit('Portfolio not found');
}

$portfolioId = (int)$portfolio['id'];
$userName = trim(((string)$portfolio['first_name']) . ' ' . ((string)$portfolio['last_name']));
$cssVersion = (string)filemtime(__DIR__ . '/css/style.css');

$skillsStmt = $pdo->prepare('SELECT skill_name, proficiency_level FROM skills WHERE portfolio_id = :portfolio_id ORDER BY id ASC');
$skillsStmt->execute(['portfolio_id' => $portfolioId]);
$skills = $skillsStmt->fetchAll();

$projectsStmt = $pdo->prepare('SELECT project_title, project_description, project_url, project_image_url, repo_url, tags, is_featured, display_order FROM projects WHERE portfolio_id = :portfolio_id ORDER BY display_order ASC, id ASC');
$projectsStmt->execute(['portfolio_id' => $portfolioId]);
$projects = $projectsStmt->fetchAll();

$socialStmt = $pdo->prepare('SELECT platform_name, profile_url FROM social_links WHERE portfolio_id = :portfolio_id ORDER BY id ASC');
$socialStmt->execute(['portfolio_id' => $portfolioId]);
$socialLinks = $socialStmt->fetchAll();

$photoUrl = safeUrl($portfolio['profile_photo_url'] ?? '');
$themeName = preg_replace('/[^a-z0-9_-]/i', '', (string)($portfolio['theme_name'] ?? 'aurora')) ?: 'aurora';
$pageTitle = trim((string)($portfolio['portfolio_title'] ?? '')) !== '' ? (string)$portfolio['portfolio_title'] : ($userName . ' Portfolio');
$shareUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['SCRIPT_NAME'] ?? '') . '/public_portfolio.php?slug=' . rawurlencode($slug);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo safeText($pageTitle); ?></title>
  <meta name="description" content="<?php echo safeText((string)($portfolio['bio_short'] ?? 'Professional portfolio')); ?>" />
  <meta property="og:title" content="<?php echo safeText($pageTitle); ?>" />
  <meta property="og:description" content="<?php echo safeText((string)($portfolio['bio_short'] ?? 'Professional portfolio')); ?>" />
  <meta property="og:type" content="profile" />
  <meta property="og:url" content="<?php echo safeText($shareUrl); ?>" />
  <?php if ($photoUrl !== ''): ?><meta property="og:image" content="<?php echo safeText($photoUrl); ?>" /><?php endif; ?>
  <link rel="stylesheet" href="css/style.css?v=<?php echo safeText($cssVersion); ?>" />
</head>
<body class="page-create page-preview theme-<?php echo safeText($themeName); ?>">
  <main class="container preview-shell">
    <section class="preview-hero-card">
      <div class="preview-identity">
        <div class="preview-avatar-wrap">
          <?php if ($photoUrl !== ''): ?>
            <img class="preview-avatar" src="<?php echo safeText($photoUrl); ?>" alt="Profile photo" />
          <?php else: ?>
            <div class="preview-avatar preview-avatar-fallback"><?php echo safeText(substr($userName, 0, 2)); ?></div>
          <?php endif; ?>
        </div>
        <div class="preview-title-block">
          <p class="preview-kicker">Public Portfolio</p>
          <h1><?php echo safeText($userName); ?></h1>
          <h2><?php echo safeText($portfolio['job_title'] ?? 'Professional'); ?></h2>
          <?php if ((string)($portfolio['bio_short'] ?? '') !== ''): ?><p class="preview-short-bio"><?php echo safeText($portfolio['bio_short']); ?></p><?php endif; ?>
        </div>
      </div>
    </section>

    <section class="preview-grid">
      <article class="preview-card preview-about">
        <h3>About</h3>
        <p><?php echo nl2br(safeText($portfolio['bio_long'] ?? '')); ?></p>
      </article>

      <article class="preview-card preview-skills">
        <h3>Skills</h3>
        <div class="skill-bars">
          <?php foreach ($skills as $skill): ?>
            <?php $lvl = max(0, min(100, (int)($skill['proficiency_level'] ?? 0))); ?>
            <div class="skill-item">
              <div class="skill-label-line"><span><?php echo safeText($skill['skill_name'] ?? 'Skill'); ?></span><span><?php echo $lvl; ?>%</span></div>
              <div class="skill-track"><div class="skill-fill" style="width: <?php echo $lvl; ?>%"></div></div>
            </div>
          <?php endforeach; ?>
        </div>
      </article>

      <article class="preview-card preview-social">
        <h3>Social</h3>
        <div class="social-pills">
          <?php foreach ($socialLinks as $social): ?>
            <?php $socialUrl = safeUrl($social['profile_url'] ?? ''); ?>
            <?php if ($socialUrl !== ''): ?><a href="<?php echo safeText($socialUrl); ?>" target="_blank" rel="noopener"><?php echo safeText(ucfirst((string)($social['platform_name'] ?? 'Profile'))); ?></a><?php endif; ?>
          <?php endforeach; ?>
        </div>
      </article>

      <article class="preview-card preview-projects">
        <h3>Projects</h3>
        <div class="project-grid">
          <?php foreach ($projects as $project): ?>
            <?php $img = safeUrl($project['project_image_url'] ?? ''); $demo = safeUrl($project['project_url'] ?? ''); $repo = safeUrl($project['repo_url'] ?? ''); ?>
            <div class="project-item">
              <?php if ($img !== ''): ?><img class="project-cover" src="<?php echo safeText($img); ?>" alt="Project image" /><?php endif; ?>
              <h4><?php echo safeText($project['project_title'] ?? 'Project'); ?></h4>
              <?php if ((string)($project['project_description'] ?? '') !== ''): ?><p><?php echo safeText($project['project_description']); ?></p><?php endif; ?>
              <div class="project-links">
                <?php if ($demo !== ''): ?><a href="<?php echo safeText($demo); ?>" target="_blank" rel="noopener">Demo</a><?php endif; ?>
                <?php if ($repo !== ''): ?><a href="<?php echo safeText($repo); ?>" target="_blank" rel="noopener">Source</a><?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </article>
    </section>
  </main>
</body>
</html>
