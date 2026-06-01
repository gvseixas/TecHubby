<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed';
    exit;
}

$email = trim((string)($_POST['email'] ?? ''));
$senha = (string)($_POST['senha'] ?? '');

if ($email === '' || $senha === '') {
    http_response_code(400);
    echo 'Email e senha são obrigatórios.';
    exit;
}

$ok = login_usuario($email, $senha);
if (!$ok) {
    http_response_code(401);
    echo 'Credenciais inválidas.';
    exit;
}

header('Location: ../index.html');
exit;

