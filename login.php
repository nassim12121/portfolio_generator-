<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (isset($_SESSION['user_id'])) {
        header('Location: create.php');
        exit;
    }
    header('Location: login.html');
    exit;
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
            header('Location: signup.html?error=missing_fields');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: signup.html?error=invalid_email');
            exit;
        }

        if (strlen($password) < 8) {
            header('Location: signup.html?error=password_too_short');
            exit;
        }

        $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $checkStmt->execute(['email' => $email]);
        if ($checkStmt->fetch()) {
            header('Location: signup.html?error=email_exists');
            exit;
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

        header('Location: create.php');
        exit;
    }

    if ($action === 'login') {
        if ($email === '' || $password === '') {
            header('Location: login.html?error=missing_fields');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: login.html?error=invalid_email');
            exit;
        }

        $userStmt = $pdo->prepare(
            'SELECT id, first_name, last_name, email, password FROM users WHERE email = :email LIMIT 1'
        );
        $userStmt->execute(['email' => $email]);
        $user = $userStmt->fetch();

        if (!$user || !password_verify($password, (string)$user['password'])) {
            header('Location: login.html?error=invalid_credentials');
            exit;
        }

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_email'] = $user['email'];

        header('Location: create.php');
        exit;
    }

    header('Location: login.html?error=invalid_action');
    exit;
} catch (Throwable $e) {
    if ($action === 'register') {
        header('Location: signup.html?error=server_error');
        exit;
    }
    header('Location: login.html?error=server_error');
    exit;
}
