<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Not authenticated']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$requestedId = (int)($_GET['id'] ?? 0);
$pdo = db();

try {
    $pdo->beginTransaction();

    if ($requestedId > 0) {
        $portfolioStmt = $pdo->prepare('SELECT id FROM portfolios WHERE user_id = :user_id AND id = :id LIMIT 1');
        $portfolioStmt->execute(['user_id' => $userId, 'id' => $requestedId]);
    } else {
        $portfolioStmt = $pdo->prepare('SELECT id FROM portfolios WHERE user_id = :user_id ORDER BY id DESC LIMIT 1');
        $portfolioStmt->execute(['user_id' => $userId]);
    }
    $portfolio = $portfolioStmt->fetch();

    if ($portfolio === false) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Portfolio not found']);
        exit;
    }

    $portfolioId = (int)$portfolio['id'];

    $pdo->prepare('DELETE FROM skills WHERE portfolio_id = :portfolio_id')->execute(['portfolio_id' => $portfolioId]);
    $pdo->prepare('DELETE FROM projects WHERE portfolio_id = :portfolio_id')->execute(['portfolio_id' => $portfolioId]);
    $pdo->prepare('DELETE FROM social_links WHERE portfolio_id = :portfolio_id')->execute(['portfolio_id' => $portfolioId]);
    $pdo->prepare('DELETE FROM portfolios WHERE id = :id')->execute(['id' => $portfolioId]);

    $pdo->commit();

    echo json_encode(['ok' => true, 'message' => 'Portfolio deleted successfully']);
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Error deleting portfolio']);
}
