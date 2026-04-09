<?php
declare(strict_types=1);

session_start();

function redirectTo(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function usersFilePath(): string
{
    return __DIR__ . DIRECTORY_SEPARATOR . 'users.json';
}

function loadUsers(): array
{
    $path = usersFilePath();
    if (!file_exists($path)) {
        return [];
    }

    $raw = file_get_contents($path);
    if ($raw === false || $raw === '') {
        return [];
    }

    $users = json_decode($raw, true);
    return is_array($users) ? $users : [];
}

function saveUsers(array $users): bool
{
    $json = json_encode($users, JSON_PRETTY_PRINT);
    if ($json === false) {
        return false;
    }
    return file_put_contents(usersFilePath(), $json) !== false;
}

function findUserByEmail(array $users, string $email): ?array
{
    foreach ($users as $user) {
        if ((string)($user['email'] ?? '') === $email) {
            return $user;
        }
    }
    return null;
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
    $users = loadUsers();

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

        if (findUserByEmail($users, $email) !== null) {
            redirectTo('signup.html?error=email_exists');
        }

        $newId = count($users) > 0 ? ((int)$users[count($users) - 1]['id'] + 1) : 1;
        $users[] = [
            'id' => $newId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'created_at' => date('c'),
        ];

        if (!saveUsers($users)) {
            redirectTo('signup.html?error=server_error');
        }

        redirectTo('login.html?registered=1');
    }

    if ($action === 'login') {
        if ($email === '' || $password === '') {
            redirectTo('login.html?error=missing_fields');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirectTo('login.html?error=invalid_email');
        }

        $user = findUserByEmail($users, $email);

        if ($user === null || !password_verify($password, (string)($user['password'] ?? ''))) {
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
