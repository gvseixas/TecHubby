<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed';
    exit;
}

$nome = trim((string)($_POST['nome'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$senha = (string)($_POST['senha'] ?? '');
$senha2 = (string)($_POST['senha_confirmar'] ?? '');

if ($nome === '' || $email === '' || $senha === '' || $senha2 === '') {
    http_response_code(400);
    echo 'Todos os campos são obrigatórios.';
    exit;
}

if ($senha !== $senha2) {
    http_response_code(400);
    echo 'As senhas não coincidem.';
    exit;
}

// Validação simples de duplicidade (evita violar constraint única)
$stmt = db()->prepare('SELECT id_usuario FROM usuarios WHERE email = :email LIMIT 1');
$stmt->execute([':email' => $email]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo 'E-mail já cadastrado.';
    exit;
}

$id = cadastrar_usuario($nome, $email, $senha);

header('Location: ../index.html');
exit;

