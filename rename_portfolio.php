<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}

$raw = (string)file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Invalid JSON']);
    exit;
}

$id = (int)($data['id'] ?? 0);
$title = trim((string)($data['title'] ?? ''));

if ($id <= 0 || $title === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Invalid payload']);
    exit;
}

$pdo = db();
$userId = (int)$_SESSION['user_id'];

try {
    try {
        $stmt = $pdo->prepare('UPDATE portfolios SET portfolio_title = :title WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['title' => $title, 'id' => $id, 'user_id' => $userId]);
    } catch (Throwable $ignored) {
        // Backward compatibility if column doesn't exist yet.
        $pdo->exec('ALTER TABLE portfolios ADD COLUMN portfolio_title VARCHAR(120) NULL');
        $stmt = $pdo->prepare('UPDATE portfolios SET portfolio_title = :title WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['title' => $title, 'id' => $id, 'user_id' => $userId]);
    }

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Server error']);
}
