<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';

function go(string $url): void
{
    header('Location: ' . $url);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (isset($_SESSION['user_id'])) {
        go('create.php');
    }
    go('login.html');
}

$action = $_POST['action'] ?? '';
$email = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');

try {
    $pdo = db();

    if ($action === 'register') {
        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $lastName = trim((string)($_POST['last_name'] ?? ''));

        if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
            go('signup.html?error=missing_fields');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            go('signup.html?error=invalid_email');
        }

        if (strlen($password) < 8) {
            go('signup.html?error=password_too_short');
        }

        $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $checkStmt->execute(['email' => $email]);
        if ($checkStmt->fetch()) {
            go('signup.html?error=email_exists');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $insertStmt = $pdo->prepare(
            'INSERT INTO users (first_name, last_name, email, password) VALUES (:first_name, :last_name, :email, :password)'
        );
        $insertStmt->execute([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => $hash,
        ]);

        $userId = (int)$pdo->lastInsertId();

        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $firstName . ' ' . $lastName;
        $_SESSION['user_email'] = $email;

        go('create.php');
    }

    if ($action === 'login') {
        if ($email === '' || $password === '') {
            go('login.html?error=missing_fields');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            go('login.html?error=invalid_email');
        }

        $userStmt = $pdo->prepare(
            'SELECT id, first_name, last_name, email, password FROM users WHERE email = :email'
        );
        $userStmt->execute(['email' => $email]);
        $user = $userStmt->fetch();

        if (!$user || !password_verify($password, (string)$user['password'])) {
            go('login.html?error=invalid_credentials');
        }

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_email'] = $user['email'];

        go('create.php');
    }

    go('login.html?error=invalid_action');
} catch (Throwable $e) {
    if ($action === 'register') {
        go('signup.html?error=server_error');
    }
    go('login.html?error=server_error');
}
