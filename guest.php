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

function initials(string $firstName, string $lastName): string
{
    $letters = trim(mb_substr($firstName, 0, 1) . mb_substr($lastName, 0, 1));
    return $letters !== '' ? mb_strtoupper($letters) : 'FP';
}

$pdo = db();

try {
  $pdo->exec("ALTER TABLE portfolios ADD COLUMN public_code VARCHAR(20) NULL UNIQUE");
} catch (Throwable $ignored) {
}

$query = trim((string)($_GET['q'] ?? ''));
$cssVersion = (string)filemtime(__DIR__ . '/css/style.css');
$portfolios = [];
$resultCount = 0;

if ($query !== '') {
  $exactStmt = null;

  if (ctype_digit($query)) {
    $exactStmt = $pdo->prepare(
      'SELECT p.id, p.public_code, p.portfolio_title, p.slug, p.job_title, p.profile_photo_url, p.location, p.bio_short, p.theme_name,
              u.first_name, u.last_name
       FROM portfolios p
       INNER JOIN users u ON u.id = p.user_id
       WHERE p.is_public = 1 AND (p.id = :id OR p.public_code = :code)
       LIMIT 1'
    );
    $exactStmt->execute(['id' => (int)$query, 'code' => 'PG-' . str_pad($query, 6, '0', STR_PAD_LEFT)]);
  } else {
    $normalizedQuery = strtolower(trim($query));
    $slugCandidate = preg_replace('/[^a-z0-9]+/i', '-', $normalizedQuery) ?? '';
    $slugCandidate = trim($slugCandidate, '-');

    $exactStmt = $pdo->prepare(
      'SELECT p.id, p.public_code, p.portfolio_title, p.slug, p.job_title, p.profile_photo_url, p.location, p.bio_short, p.theme_name,
              u.first_name, u.last_name
       FROM portfolios p
       INNER JOIN users u ON u.id = p.user_id
       WHERE p.is_public = 1 AND (p.public_code = :code OR p.slug = :slug OR p.portfolio_title = :title)
       LIMIT 1'
    );
    $exactStmt->execute([
      'code' => $query,
      'slug' => $slugCandidate,
      'title' => $query,
    ]);
  }

  $exactMatch = $exactStmt ? $exactStmt->fetch() : false;
  if ($exactMatch !== false) {
    $portfolios = [$exactMatch];
    $resultCount = 1;
  } else {
    $sql =
      'SELECT DISTINCT p.id, p.public_code, p.portfolio_title, p.slug, p.job_title, p.profile_photo_url, p.location, p.bio_short, p.theme_name,
              u.first_name, u.last_name
       FROM portfolios p
       INNER JOIN users u ON u.id = p.user_id
       WHERE p.is_public = 1
         AND (
           p.public_code LIKE :q0 OR
           u.first_name LIKE :q1 OR
           u.last_name LIKE :q2 OR
           p.portfolio_title LIKE :q3 OR
           p.job_title LIKE :q4 OR
           p.slug LIKE :q5 OR
           p.location LIKE :q6 OR
           EXISTS (
             SELECT 1
             FROM skills s
             WHERE s.portfolio_id = p.id AND s.skill_name LIKE :q7
           ) OR
           EXISTS (
             SELECT 1
             FROM projects pr
             WHERE pr.portfolio_id = p.id AND (pr.project_title LIKE :q8 OR pr.tags LIKE :q9 OR pr.project_description LIKE :q10)
           )
         )
       ORDER BY p.id DESC
       LIMIT 24';
    $queryValue = '%' . $query . '%';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      'q0' => $queryValue,
      'q1' => $queryValue,
      'q2' => $queryValue,
      'q3' => $queryValue,
      'q4' => $queryValue,
      'q5' => $queryValue,
      'q6' => $queryValue,
      'q7' => $queryValue,
      'q8' => $queryValue,
      'q9' => $queryValue,
      'q10' => $queryValue,
    ]);
    $portfolios = $stmt->fetchAll();
    $resultCount = count($portfolios);
  }
} else {
  $stmt = $pdo->query(
    'SELECT p.id, p.public_code, p.portfolio_title, p.slug, p.job_title, p.profile_photo_url, p.location, p.bio_short, p.theme_name,
            u.first_name, u.last_name
     FROM portfolios p
     INNER JOIN users u ON u.id = p.user_id
     WHERE p.is_public = 1
     ORDER BY p.id DESC
     LIMIT 24'
  );
  $portfolios = $stmt->fetchAll();
  $resultCount = count($portfolios);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Guest Explorer | PortfolioGen</title>
  <meta name="description" content="Browse public freelancer portfolios and search by name, title, or portfolio slug." />
  <link rel="stylesheet" href="css/style.css?v=<?php echo safeText($cssVersion); ?>" />
</head>
<body class="page-create page-guest">
  <header>
    <nav>
      <div class="logo">PortfolioGen</div>
      <ul>
        <li><a href="index.html">Home</a></li>
        <li><a href="guest.php" style="color:#0f6fc5;font-weight:700;">Find Portfolio</a></li>
        <li><a href="login.html">Login</a></li>
      </ul>
    </nav>
  </header>

  <main class="container guest-shell">
    <section class="guest-hero">
      <div>
        <span class="guest-badge">Find Portfolio</span>
        <h1>Open a freelancer portfolio with a public code.</h1>
        <p>For a professional lookup, paste the public code, slug, or portfolio ID. The public code is the recommended option because it is cleaner and safer to share.</p>
        <p class="guest-result-meta"><?php echo $query !== '' ? 'Showing ' . $resultCount . ' result' . ($resultCount === 1 ? '' : 's') . ' for “' . safeText($query) . '”.' : 'Showing the latest public freelancer portfolios.'; ?></p>
      </div>
      <form class="guest-search" method="GET" action="guest.php">
        <input type="search" name="q" value="<?php echo safeText($query); ?>" placeholder="Enter code like PG-000002 or PG-NASSIM-001" />
        <button type="submit" class="btn-next">Open</button>
      </form>
    </section>

    <section class="guest-grid">
      <?php if (empty($portfolios)): ?>
        <article class="guest-empty">
          <h2>No public portfolios found</h2>
          <p><?php echo $query !== '' ? 'Try another search term.' : 'No freelancer has published a public portfolio yet.'; ?></p>
          <a href="index.html" class="btn-next">Back to Home</a>
        </article>
      <?php else: ?>
        <?php foreach ($portfolios as $portfolio): ?>
          <?php
            $firstName = (string)($portfolio['first_name'] ?? '');
            $lastName = (string)($portfolio['last_name'] ?? '');
            $fullName = trim($firstName . ' ' . $lastName);
            $portfolioTitle = trim((string)($portfolio['portfolio_title'] ?? ''));
            $title = $portfolioTitle !== '' ? $portfolioTitle : ($fullName !== '' ? $fullName . ' Portfolio' : 'Public Portfolio');
            $photoUrl = safeUrl($portfolio['profile_photo_url'] ?? '');
            $slug = (string)($portfolio['slug'] ?? '');
            $publicCode = trim((string)($portfolio['public_code'] ?? ''));
            $jobTitle = (string)($portfolio['job_title'] ?? 'Professional');
            $bioShort = (string)($portfolio['bio_short'] ?? '');
            $location = (string)($portfolio['location'] ?? '');
          ?>
          <article class="guest-card">
            <div class="guest-card-top">
              <?php if ($photoUrl !== ''): ?>
                <img class="guest-avatar" src="<?php echo safeText($photoUrl); ?>" alt="<?php echo safeText($fullName !== '' ? $fullName : $title); ?>" />
              <?php else: ?>
                <div class="guest-avatar guest-avatar-fallback"><?php echo safeText(initials($firstName, $lastName)); ?></div>
              <?php endif; ?>
              <div>
                <p class="guest-name"><?php echo safeText($fullName !== '' ? $fullName : 'Freelancer'); ?></p>
                <h2><?php echo safeText($title); ?></h2>
                <p class="guest-meta"><?php echo safeText($jobTitle); ?><?php echo $location !== '' ? ' · ' . safeText($location) : ''; ?></p>
                <?php if ($publicCode !== ''): ?><p class="guest-code">Code: <?php echo safeText($publicCode); ?></p><?php endif; ?>
              </div>
            </div>
            <?php if ($bioShort !== ''): ?>
              <p class="guest-bio"><?php echo safeText($bioShort); ?></p>
            <?php endif; ?>
            <div class="guest-actions">
              <a class="btn-next" href="public_portfolio.php?slug=<?php echo rawurlencode($slug); ?>" target="_blank" rel="noopener">View Portfolio</a>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
