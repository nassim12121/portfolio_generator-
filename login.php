<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

session_start();

function redirectTo(string $url): void
{
    header('Location: ' . $url);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (isset($_SESSION['user_id'])) {
        redirectTo('create.php');
    }
    redirectTo('login.html');
}

$action = (string)($_POST['action'] ?? '');
$email = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');

try {
    $pdo = db();

    if ($action === 'register') {
        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $lastName = trim((string)($_POST['last_name'] ?? ''));

        if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
            redirectTo('signup.html?error=missing_fields');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirectTo('signup.html?error=invalid_email');
        }

        if (strlen($password) < 8) {
            redirectTo('signup.html?error=password_too_short');
        }

        $findStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $findStmt->execute(['email' => $email]);
        if ($findStmt->fetch() !== false) {
            redirectTo('signup.html?error=email_exists');
        }

        $insertStmt = $pdo->prepare(
            'INSERT INTO users (first_name, last_name, email, password) VALUES (:first_name, :last_name, :email, :password)'
        );
        $insertStmt->execute([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        redirectTo('login.html?registered=1');
    }

    if ($action === 'login') {
        if ($email === '' || $password === '') {
            redirectTo('login.html?error=missing_fields');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirectTo('login.html?error=invalid_email');
        }

        $userStmt = $pdo->prepare('SELECT id, first_name, last_name, email, password FROM users WHERE email = :email LIMIT 1');
        $userStmt->execute(['email' => $email]);
        $user = $userStmt->fetch();

        if ($user === false || !password_verify($password, (string)($user['password'] ?? ''))) {
            redirectTo('login.html?error=invalid_credentials');
        }

        $_SESSION['user_id'] = (int)($user['id'] ?? 0);
        $_SESSION['user_name'] = (string)($user['first_name'] ?? '') . ' ' . (string)($user['last_name'] ?? '');
        $_SESSION['user_email'] = (string)($user['email'] ?? '');

        redirectTo('create.php');
    }

    redirectTo('login.html?error=invalid_action');
} catch (Throwable $e) {
    if ($action === 'register') {
        redirectTo('signup.html?error=server_error');
    }
    redirectTo('login.html?error=server_error');
}
