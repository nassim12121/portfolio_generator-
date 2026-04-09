<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json');
echo json_encode(['loggedIn' => isset($_SESSION['user_id'])]);
