<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed';
    exit;
}

$userId = usuario_logado_id();
if (!$userId) {
    http_response_code(401);
    echo 'Faça login para cadastrar produto.';
    exit;
}

$tipo = usuario_tipo();
$lojaId = loja_do_usuario_logado_id();
if (!$lojaId) {
    http_response_code(403);
    echo 'Usuário não possui loja cadastrada.';
    exit;
}

$nome = trim((string)($_POST['nome'] ?? ''));
$marca = trim((string)($_POST['marca'] ?? ''));
$modelo = trim((string)($_POST['modelo'] ?? ''));
$preco = (string)($_POST['preco'] ?? '');
$estoque = (string)($_POST['estoque_atual'] ?? '0');
$descricao = trim((string)($_POST['descricao'] ?? ''));
$specJson = (string)($_POST['especificacoes_tecnicas'] ?? '');

if ($nome === '' || $marca === '' || $modelo === '' || $preco === '') {
    http_response_code(400);
    echo 'Nome, marca, modelo e preço são obrigatórios.';
    exit;
}

$precoNum = (float)str_replace(',', '.', $preco);
$estoqueNum = (int)$estoque;

// Especificações: aceitar JSON vazio
$spec = null;
if ($specJson !== '') {
    $decoded = json_decode($specJson, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo 'especificacoes_tecnicas deve ser um JSON válido.';
        exit;
    }
    $spec = $decoded;
}

$stmt = db()->prepare('INSERT INTO produtos (id_loja, nome, marca, modelo, descricao, especificacoes_tecnicas, preco, estoque_atual) VALUES (:id_loja, :nome, :marca, :modelo, :descricao, :spec, :preco, :estoque)');
$stmt->execute([
    ':id_loja' => $lojaId,
    ':nome' => $nome,
    ':marca' => $marca,
    ':modelo' => $modelo,
    ':descricao' => $descricao !== '' ? $descricao : null,
    ':spec' => $spec !== null ? json_encode($spec, JSON_UNESCAPED_UNICODE) : null,
    ':preco' => $precoNum,
    ':estoque' => $estoqueNum,
]);

header('Location: ../index.html');
exit;

