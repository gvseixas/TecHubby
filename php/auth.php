<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function ensure_session_started(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_COOKIE_NAME);
        session_start();
    }
}

function login_usuario(string $email, string $senha): bool {
    ensure_session_started();

    $stmt = db()->prepare('SELECT id_usuario, senha_hash FROM usuarios WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user || empty($user['senha_hash'])) {
        return false;
    }

    if (!password_verify($senha, $user['senha_hash'])) {
        return false;
    }

    $_SESSION['user_id'] = (int)$user['id_usuario'];
    return true;
}

function cadastrar_usuario(string $nome, string $email, string $senha): int {
    ensure_session_started();

    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    $stmt = db()->prepare('INSERT INTO usuarios (nome, email, senha_hash, google_id, tipo_usuario) VALUES (:nome, :email, :senha_hash, NULL, :tipo)');
    // Por padrão, novo usuário será cliente. Se quiser cadastrar vendedor depois, ajustamos.
    $stmt->execute([
        ':nome' => $nome,
        ':email' => $email,
        ':senha_hash' => $senhaHash,
        ':tipo' => 'cliente',
    ]);

    $id = (int)db()->lastInsertId();
    $_SESSION['user_id'] = $id;

    return $id;
}

function logout_usuario(): void {
    ensure_session_started();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

function usuario_logado_id(): ?int {
    ensure_session_started();
    if (!isset($_SESSION['user_id'])) return null;
    return (int)$_SESSION['user_id'];
}

function usuario_tipo(): ?string {
    ensure_session_started();
    $id = $_SESSION['user_id'] ?? null;
    if (!$id) return null;

    $stmt = db()->prepare('SELECT tipo_usuario FROM usuarios WHERE id_usuario = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row['tipo_usuario'] ?? null;
}

function loja_do_usuario_logado_id(): ?int {
    ensure_session_started();
    $id = $_SESSION['user_id'] ?? null;
    if (!$id) return null;

    $stmt = db()->prepare('SELECT id_loja FROM lojas WHERE id_usuario = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return isset($row['id_loja']) ? (int)$row['id_loja'] : null;
}

