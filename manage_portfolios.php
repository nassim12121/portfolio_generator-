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
$cssVersion = (string)filemtime(__DIR__ . '/css/style.css');
$rows = [];

try {
    $stmt = $pdo->prepare(
        'SELECT id, COALESCE(portfolio_title, CONCAT("Portfolio #", id)) AS portfolio_title, slug, is_public, theme_name, created_at
         FROM portfolios
         WHERE user_id = :user_id
         ORDER BY id DESC'
    );
    $stmt->execute(['user_id' => $userId]);
    $rows = $stmt->fetchAll();
} catch (Throwable $ignored) {
    $stmt = $pdo->prepare(
        'SELECT id, CONCAT("Portfolio #", id) AS portfolio_title, NULL AS slug, 0 AS is_public, "aurora" AS theme_name, created_at
         FROM portfolios
         WHERE user_id = :user_id
         ORDER BY id DESC'
    );
    $stmt->execute(['user_id' => $userId]);
    $rows = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manage Portfolios</title>
  <link rel="stylesheet" href="css/style.css?v=<?php echo safeText($cssVersion); ?>" />
  <style>
    .manage-wrap { max-width: 1000px; margin: 110px auto 30px; padding: 0 1rem; }
    .manage-card { background: linear-gradient(135deg, #ffffff 0%, #f7fbff 100%); border: 1px solid rgba(15,111,197,.16); border-radius: 16px; padding: 1rem; margin-bottom: 12px; box-shadow: 0 14px 30px rgba(15,62,104,.09); animation: cardIn .45s ease both; }
    .manage-row { display: grid; grid-template-columns: 1fr auto; gap: 12px; align-items: center; }
    .manage-title-input { width: 100%; padding: .65rem .75rem; border: 1px solid #c9daea; border-radius: 10px; }
    .manage-actions { display: flex; flex-wrap: wrap; gap: 8px; }
    .manage-btn { border: 0; border-radius: 8px; padding: .55rem .75rem; text-decoration: none; font-weight: 600; color: #fff; cursor: pointer; }
    .b-preview { background: #0f6fc5; }
    .b-edit { background: #0f4c81; }
    .b-public { background: #00a89d; }
    .b-pdf { background: #1f8f4e; }
    .b-rename { background: #2f6ea3; }
    .b-delete { background: #ef4444; }
    .meta { color: #6b7280; font-size: .9rem; margin-top: .35rem; }
    @keyframes cardIn { from { opacity: 0; transform: translateY(14px);} to { opacity: 1; transform: translateY(0);} }
  </style>
</head>
<body class="page-create">
<header>
  <nav>
    <div class="logo">PortfolioGen</div>
    <ul>
      <li><a href="index.html">Home</a></li>
      <li><a href="guest.php">Guest</a></li>
      <li><a href="create.php">Create/Edit</a></li>
      <li><a href="preview.php">My Portfolio</a></li>
      <li><a href="manage_portfolios.php" style="color:#0f6fc5;font-weight:700;">Manage</a></li>
      <li><a href="logout.php">Logout</a></li>
    </ul>
  </nav>
</header>

<main class="manage-wrap">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;gap:10px;flex-wrap:wrap;">
    <h1 style="margin:0;">Manage Portfolio Versions</h1>
    <a class="manage-btn b-edit" href="create.php">+ Create New Version</a>
  </div>

  <?php if (empty($rows)): ?>
    <div class="manage-card">
      <p style="margin:0;">No saved portfolio yet. Create your first one.</p>
    </div>
  <?php else: ?>
    <?php foreach ($rows as $row): ?>
      <?php
        $id = (int)$row['id'];
        $title = (string)($row['portfolio_title'] ?? ('Portfolio #' . $id));
        $slug = trim((string)($row['slug'] ?? ''));
        $isPublic = (int)($row['is_public'] ?? 0) === 1;
        $theme = (string)($row['theme_name'] ?? 'aurora');
        $created = (string)($row['created_at'] ?? '');
      ?>
      <section class="manage-card" id="portfolio-<?php echo $id; ?>">
        <div class="manage-row">
          <div>
            <input class="manage-title-input" id="title-<?php echo $id; ?>" value="<?php echo safeText($title); ?>" maxlength="120" />
            <div class="meta">ID #<?php echo $id; ?> | Theme: <?php echo safeText($theme); ?> | <?php echo $isPublic ? 'Public' : 'Private'; ?> | Saved: <?php echo safeText($created); ?></div>
          </div>
          <div class="manage-actions">
            <a class="manage-btn b-preview" href="preview.php?id=<?php echo $id; ?>">Preview</a>
            <a class="manage-btn b-edit" href="create.php?portfolio_id=<?php echo $id; ?>">Use In Editor</a>
            <a class="manage-btn b-pdf" href="export_pdf.php?id=<?php echo $id; ?>" target="_blank" rel="noopener">PDF</a>
            <?php if ($isPublic && $slug !== ''): ?>
              <a class="manage-btn b-public" href="public_portfolio.php?slug=<?php echo rawurlencode($slug); ?>" target="_blank" rel="noopener">Public</a>
            <?php endif; ?>
            <button class="manage-btn b-rename" type="button" onclick="renamePortfolio(<?php echo $id; ?>)">Rename</button>
            <button class="manage-btn b-delete" type="button" onclick="deletePortfolio(<?php echo $id; ?>)">Delete</button>
          </div>
        </div>
      </section>
    <?php endforeach; ?>
  <?php endif; ?>
</main>

<script>
function renamePortfolio(id) {
  var input = document.getElementById('title-' + id);
  if (!input) return;
  var title = (input.value || '').trim();
  if (!title) {
    alert('Title cannot be empty.');
    return;
  }

  fetch('rename_portfolio.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: id, title: title })
  })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (!data.ok) throw new Error(data.message || 'Rename failed');
      alert('Portfolio renamed.');
    })
    .catch(function (err) {
      alert('Error: ' + err.message);
    });
}

function deletePortfolio(id) {
  if (!confirm('Delete this portfolio version? This cannot be undone.')) return;

  fetch('delete_portfolio.php?id=' + encodeURIComponent(id), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' }
  })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (!data.ok) throw new Error(data.message || 'Delete failed');
      var row = document.getElementById('portfolio-' + id);
      if (row) row.remove();
    })
    .catch(function (err) {
      alert('Error: ' + err.message);
    });
}
</script>
</body>
</html>
