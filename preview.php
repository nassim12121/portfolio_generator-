<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$userName = trim((string)($_SESSION['user_name'] ?? 'Portfolio Owner'));
$cssVersion = (string) filemtime(__DIR__ . '/css/style.css');

$pdo = db();
$userId = (int)$_SESSION['user_id'];
$requestedPortfolioId = (int)($_GET['id'] ?? 0);

if ($requestedPortfolioId > 0) {
  try {
    $portfolioStmt = $pdo->prepare(
      'SELECT id, portfolio_title, slug, is_public, theme_name, job_title, profile_photo_url, location, website_url, bio_short, bio_long, years_exp, phone, email
       FROM portfolios
       WHERE user_id = :user_id AND id = :id
       LIMIT 1'
    );
    $portfolioStmt->execute(['user_id' => $userId, 'id' => $requestedPortfolioId]);
    $portfolio = $portfolioStmt->fetch();
  } catch (Throwable $ignored) {
    $portfolioStmt = $pdo->prepare(
      'SELECT id, job_title, profile_photo_url, location, website_url, bio_short, bio_long, years_exp, phone, email
       FROM portfolios
       WHERE user_id = :user_id AND id = :id
       LIMIT 1'
    );
    $portfolioStmt->execute(['user_id' => $userId, 'id' => $requestedPortfolioId]);
    $portfolio = $portfolioStmt->fetch();
  }
} else {
  try {
    $portfolioStmt = $pdo->prepare(
      'SELECT id, portfolio_title, slug, is_public, theme_name, job_title, profile_photo_url, location, website_url, bio_short, bio_long, years_exp, phone, email
       FROM portfolios
       WHERE user_id = :user_id
       ORDER BY id DESC
       LIMIT 1'
    );
    $portfolioStmt->execute(['user_id' => $userId]);
    $portfolio = $portfolioStmt->fetch();
  } catch (Throwable $ignored) {
    $portfolioStmt = $pdo->prepare(
      'SELECT id, job_title, profile_photo_url, location, website_url, bio_short, bio_long, years_exp, phone, email
       FROM portfolios
       WHERE user_id = :user_id
       ORDER BY id DESC
       LIMIT 1'
    );
    $portfolioStmt->execute(['user_id' => $userId]);
    $portfolio = $portfolioStmt->fetch();
  }
}

$skills = [];
$projects = [];
$socialLinks = [];
$savedPortfolios = [];

try {
  $savedStmt = $pdo->prepare('SELECT id, portfolio_title, created_at FROM portfolios WHERE user_id = :user_id ORDER BY id DESC LIMIT 8');
  $savedStmt->execute(['user_id' => $userId]);
  $savedPortfolios = $savedStmt->fetchAll();
} catch (Throwable $ignored) {
}

if ($portfolio !== false) {
    $portfolioId = (int)$portfolio['id'];

    $skillsStmt = $pdo->prepare('SELECT skill_name, proficiency_level FROM skills WHERE portfolio_id = :portfolio_id ORDER BY id ASC');
    $skillsStmt->execute(['portfolio_id' => $portfolioId]);
    $skills = $skillsStmt->fetchAll();

    try {
      $projectsStmt = $pdo->prepare(
        'SELECT project_title, project_description, project_url, project_image_url, repo_url, tags, is_featured, display_order
         FROM projects
         WHERE portfolio_id = :portfolio_id
        ORDER BY display_order ASC, id ASC'
      );
      $projectsStmt->execute(['portfolio_id' => $portfolioId]);
      $projects = $projectsStmt->fetchAll();
    } catch (Throwable $ignored) {
      $projectsStmt = $pdo->prepare(
        'SELECT project_title, project_description, project_url, project_image_url, repo_url, tags
         FROM projects
         WHERE portfolio_id = :portfolio_id
         ORDER BY id ASC'
      );
      $projectsStmt->execute(['portfolio_id' => $portfolioId]);
      $projects = $projectsStmt->fetchAll();
    }

    $socialStmt = $pdo->prepare('SELECT platform_name, profile_url FROM social_links WHERE portfolio_id = :portfolio_id ORDER BY id ASC');
    $socialStmt->execute(['portfolio_id' => $portfolioId]);
    $socialLinks = $socialStmt->fetchAll();
}

  $initials = '';
  foreach (preg_split('/\s+/', $userName) as $part) {
    if ($part !== '') {
      $initials .= strtoupper(substr($part, 0, 1));
    }
    if (strlen($initials) >= 2) {
      break;
    }
  }
  if ($initials === '') {
    $initials = 'PO';
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

  $themeName = 'aurora';
  $isPublic = false;
  $shareUrl = '';
  if ($portfolio !== false) {
    $themeName = preg_replace('/[^a-z0-9_-]/i', '', (string)($portfolio['theme_name'] ?? 'aurora')) ?: 'aurora';
    $isPublic = (int)($portfolio['is_public'] ?? 0) === 1;
    $slug = trim((string)($portfolio['slug'] ?? ''));
    if ($isPublic && $slug !== '') {
      $base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
      $dir = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
      $shareUrl = $base . ($dir !== '' ? $dir : '') . '/public_portfolio.php?slug=' . rawurlencode($slug);
    }
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo safeText(($portfolio['portfolio_title'] ?? '') !== '' ? (string)$portfolio['portfolio_title'] : 'My Portfolio Preview'); ?></title>
  <link rel="stylesheet" href="css/style.css?v=<?php echo safeText($cssVersion); ?>" />
</head>
<body class="page-create page-preview theme-<?php echo safeText($themeName); ?>">
  <header>
    <nav>
      <div class="logo">PortfolioGen</div>
      <ul>
        <li><a href="index.html">Home</a></li>
        <li><a href="guest.php">Guest</a></li>
        <li><a href="create.php">Create/Edit</a></li>
        <li><a href="preview.php">My Portfolio</a></li>
        <li><a href="manage_portfolios.php">Manage</a></li>
        <li><a href="logout.php">Logout</a></li>
      </ul>
    </nav>
  </header>

  <main class="container preview-shell">
    <?php if ($portfolio === false): ?>
      <section class="preview-empty">
        <h1>No Portfolio Yet</h1>
        <p>Your preview will appear here after saving your first portfolio.</p>
        <a href="create.php" class="btn-next">Create My Portfolio</a>
      </section>
    <?php else: ?>
      <?php
        $photoUrl = safeUrl($portfolio['profile_photo_url'] ?? '');
        $websiteUrl = safeUrl($portfolio['website_url'] ?? '');
      ?>

      <section class="preview-hero-card">
        <div class="preview-identity">
          <div class="preview-avatar-wrap">
            <?php if ($photoUrl !== ''): ?>
              <img class="preview-avatar" src="<?php echo safeText($photoUrl); ?>" alt="Profile photo" />
            <?php else: ?>
              <div class="preview-avatar preview-avatar-fallback"><?php echo safeText($initials); ?></div>
            <?php endif; ?>
          </div>

          <div class="preview-title-block">
            <p class="preview-kicker">Portfolio Preview</p>
            <h1><?php echo safeText($userName); ?></h1>
            <h2><?php echo safeText($portfolio['job_title'] ?? 'Professional'); ?></h2>
            <?php if ((string)($portfolio['bio_short'] ?? '') !== ''): ?>
              <p class="preview-short-bio"><?php echo safeText($portfolio['bio_short']); ?></p>
            <?php endif; ?>
            <div class="preview-meta-list">
              <?php if ((string)($portfolio['location'] ?? '') !== ''): ?><span><?php echo safeText($portfolio['location']); ?></span><?php endif; ?>
              <?php if ((string)($portfolio['years_exp'] ?? '') !== ''): ?><span><?php echo safeText($portfolio['years_exp']); ?> experience</span><?php endif; ?>
            </div>
          </div>
        </div>

        <aside class="preview-contact-card">
          <h3>Contact</h3>
          <ul>
            <?php if ((string)($portfolio['email'] ?? '') !== ''): ?><li><?php echo safeText($portfolio['email']); ?></li><?php endif; ?>
            <?php if ((string)($portfolio['phone'] ?? '') !== ''): ?><li><?php echo safeText($portfolio['phone']); ?></li><?php endif; ?>
            <?php if ($websiteUrl !== ''): ?><li><a href="<?php echo htmlspecialchars($websiteUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($websiteUrl, ENT_QUOTES, 'UTF-8'); ?></a></li><?php endif; ?>
            <?php if ($shareUrl !== ''): ?><li><a href="<?php echo safeText($shareUrl); ?>" target="_blank" rel="noopener">Public URL</a></li><?php endif; ?>
          </ul>

          <?php if (!empty($savedPortfolios)): ?>
            <div style="margin-top:12px; border-top:1px solid rgba(255,255,255,0.25); padding-top:10px;">
              <div style="font-size:12px; opacity:0.85; margin-bottom:6px;">Saved Versions</div>
              <?php foreach ($savedPortfolios as $sv): ?>
                <div style="margin-bottom:4px;">
                  <a href="preview.php?id=<?php echo (int)$sv['id']; ?>" style="font-size:12px;">
                    #<?php echo (int)$sv['id']; ?> <?php echo safeText((string)($sv['portfolio_title'] ?? 'Portfolio')); ?>
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </aside>
      </section>

      <section class="preview-grid">
        <article class="preview-card preview-about">
          <h3>About</h3>
          <?php if ((string)($portfolio['bio_long'] ?? '') === ''): ?>
            <p>No long bio added yet.</p>
          <?php else: ?>
            <p><?php echo nl2br(safeText($portfolio['bio_long'])); ?></p>
          <?php endif; ?>
        </article>

        <article class="preview-card preview-skills">
          <h3>Skills</h3>
          <?php if (empty($skills)): ?>
            <p>No skills added yet.</p>
          <?php else: ?>
            <div class="skill-bars">
              <?php foreach ($skills as $skill): ?>
                <?php $lvl = max(0, min(100, (int)($skill['proficiency_level'] ?? 0))); ?>
                <div class="skill-item">
                  <div class="skill-label-line">
                    <span><?php echo safeText($skill['skill_name'] ?? 'Skill'); ?></span>
                    <span><?php echo $lvl; ?>%</span>
                  </div>
                  <div class="skill-track"><div class="skill-fill" style="width: <?php echo $lvl; ?>%"></div></div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </article>

        <article class="preview-card preview-social">
          <h3>Social & Links</h3>
          <div class="social-stats">
            <div class="stat-box">
              <div class="stat-label">Email</div>
              <div class="stat-value"><?php echo (string)($portfolio['email'] ?? '') !== '' ? htmlspecialchars(substr($portfolio['email'], 0, 20), ENT_QUOTES, 'UTF-8') : 'N/A'; ?></div>
            </div>
            <div class="stat-box">
              <div class="stat-label">Phone</div>
              <div class="stat-value"><?php echo (string)($portfolio['phone'] ?? '') !== '' ? htmlspecialchars($portfolio['phone'], ENT_QUOTES, 'UTF-8') : 'N/A'; ?></div>
            </div>
          </div>
          <?php if (!empty($socialLinks)): ?>
            <div style="margin-top: 1.2rem; padding-top: 1.2rem; border-top: 1px solid rgba(102, 126, 234, 0.1);">
              <p style="font-size: 0.85rem; color: #5a6378; margin-bottom: 0.8rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05rem;">Follow</p>
              <div class="social-pills">
                <?php foreach ($socialLinks as $social): ?>
                  <?php $socialUrl = safeUrl($social['profile_url'] ?? ''); ?>
                  <?php if ($socialUrl !== ''): ?>
                    <a href="<?php echo htmlspecialchars($socialUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" title="<?php echo safeText(ucfirst((string)($social['platform_name'] ?? 'Profile'))); ?>">
                      <?php echo safeText(ucfirst((string)($social['platform_name'] ?? 'Profile'))); ?>
                    </a>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </article>

        <article class="preview-card preview-projects">
          <h3>Projects</h3>
          <?php if (empty($projects)): ?>
            <p>No projects added yet.</p>
          <?php else: ?>
            <div class="project-grid">
              <?php foreach ($projects as $project): ?>
                <?php
                  $demoUrl = safeUrl($project['project_url'] ?? '');
                  $imageUrl = safeUrl($project['project_image_url'] ?? '');
                  $repoUrl = safeUrl($project['repo_url'] ?? '');
                  $isFeatured = (int)($project['is_featured'] ?? 0) === 1;
                  $rawTags = array_filter(array_map('trim', explode(',', (string)($project['tags'] ?? ''))));
                ?>
                <div class="project-item">
                  <?php if ($imageUrl !== ''): ?>
                    <img class="project-cover" src="<?php echo safeText($imageUrl); ?>" alt="Project image" />
                  <?php endif; ?>

                  <h4>
                    <?php echo safeText($project['project_title'] ?? 'Project'); ?>
                    <?php if ($isFeatured): ?><span class="featured-badge">★ Featured</span><?php endif; ?>
                  </h4>
                  <?php if ((string)($project['project_description'] ?? '') !== ''): ?>
                    <p><?php echo safeText($project['project_description']); ?></p>
                  <?php endif; ?>

                  <?php if (!empty($rawTags)): ?>
                    <div class="project-tags">
                      <?php foreach ($rawTags as $tag): ?>
                        <span><?php echo safeText($tag); ?></span>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>

                  <div class="project-links">
                    <?php if ($demoUrl !== ''): ?><a href="<?php echo htmlspecialchars($demoUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" style="word-break: break-all;"><?php echo htmlspecialchars($demoUrl, ENT_QUOTES, 'UTF-8'); ?></a><?php endif; ?>
                    <?php if ($repoUrl !== ''): ?><br/><a href="<?php echo htmlspecialchars($repoUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" style="word-break: break-all;"><?php echo htmlspecialchars($repoUrl, ENT_QUOTES, 'UTF-8'); ?></a><?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </article>
      </section>

      <div style="text-align: center; margin-top: 2rem; margin-bottom: 2rem; display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
        <a href="create.php" class="btn-next" style="display: inline-block; background: #667eea; color: white; padding: 0.8rem 1.5rem; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 1rem;">✏️ Edit Portfolio</a>
        <a href="download.php?id=<?php echo (int)($portfolio['id'] ?? 0); ?>" class="btn-next" style="display: inline-block; background: #1f9d66; color: white; padding: 0.8rem 1.5rem; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 1rem;">⬇ Download HTML</a>
        <a href="export_pdf.php?id=<?php echo (int)($portfolio['id'] ?? 0); ?>" target="_blank" rel="noopener" class="btn-next" style="display: inline-block; background: #0f766e; color: white; padding: 0.8rem 1.5rem; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 1rem;">🧾 Export PDF</a>
        <a href="manage_portfolios.php" class="btn-next" style="display: inline-block; background: #7c3aed; color: white; padding: 0.8rem 1.5rem; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 1rem;">🗂 Manage Versions</a>
        <button onclick="deletePortfolio()" style="display: inline-block; background: #ff4757; color: white; padding: 0.8rem 1.5rem; border-radius: 6px; border: none; font-weight: 600; font-size: 1rem; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.background='#ff3838'" onmouseout="this.style.background='#ff4757'">🗑️ Delete Portfolio</button>
      </div>

      <script>
      function deletePortfolio() {
        if (!confirm('⚠️ Are you sure you want to delete your portfolio? This cannot be undone.')) {
          return;
        }

        fetch('delete_portfolio.php?id=<?php echo (int)($portfolio['id'] ?? 0); ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          }
        })
        .then(res => res.json())
        .then(data => {
          if (data.ok) {
            alert('✓ Portfolio deleted successfully');
            window.location.href = 'create.php';
          } else {
            alert('✗ Error: ' + (data.message || 'Unknown error'));
          }
        })
        .catch(err => {
          alert('✗ Error deleting portfolio');
          console.error(err);
        });
      }
      </script>
    <?php endif; ?>
  </main>
</body>
</html>
