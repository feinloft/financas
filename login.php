<?php
/**
 * Página de Login
 * Sistema de Gestão Financeira (SGF)
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Se o usuário já estiver logado, redireciona para o dashboard
if (estaLogado()) {
    header("Location: index.php");
    exit;
}

$erro = "";
$usuario_input = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_input = trim($_POST['usuario'] ?? '');
    $senha_input = $_POST['senha'] ?? '';
    $token_csrf = $_POST['csrf_token'] ?? '';

    if (!validarCSRF($token_csrf)) {
        $erro = "Sessão inválida. Tente novamente.";
    } elseif (empty($usuario_input) || empty($senha_input)) {
        $erro = "Preencha todos os campos.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? AND status = 'ativo'");
        $stmt->execute([$usuario_input]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha_input, $usuario['senha'])) {
            // Login bem-sucedido
            regenerarSessao();
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_cargo'] = $usuario['cargo'];
            $_SESSION['usuario_grupo_id'] = $usuario['grupo_id'];
            $_SESSION['primeiro_acesso'] = $usuario['primeiro_acesso'];

            // Se for primeiro acesso, redireciona para troca de senha (opcional, mas solicitado pelo flag)
            // Por simplicidade, trataremos isso na index ou em uma página específica.
            // O requisito diz "admin / Admin@123 — force password change on first login flag"
            
            header("Location: index.php" . ($_SESSION['primeiro_acesso'] ? "?page=perfil&msg=trocar_senha" : ""));
            exit;
        } else {
            $erro = "Usuário ou senha incorretos ou conta desativada.";
        }
    }
}

// Gera CSRF para o formulário
$csrf_token = gerarCSRF();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGF - Login</title>
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #34495e;
            --accent: #3498db;
            --danger: #e74c3c;
            --bg: #f5f6fa;
            --card-bg: #ffffff;
            --text: #2f3640;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        body {
            background-color: var(--bg);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: var(--text);
        }

        .login-container {
            background-color: var(--card-bg);
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h1 {
            color: var(--primary);
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
        }

        input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #dcdde1;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        input:focus {
            outline: none;
            border-color: var(--accent);
        }

        button {
            width: 100%;
            padding: 0.8rem;
            background-color: var(--accent);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        button:hover {
            background-color: #2980b9;
        }

        .alert {
            padding: 0.8rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            text-align: center;
        }

        .alert-danger {
            background-color: #fab1a0;
            color: #d63031;
            border: 1px solid #ff7675;
        }

        .footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.8rem;
            color: #95a5a6;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>SGF</h1>
            <p>Sistema de Gestão Financeira</p>
        </div>

        <?php if ($erro): ?>
            <div class="alert alert-danger">
                <?= s($erro) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            
            <div class="form-group">
                <label for="usuario">Usuário</label>
                <input type="text" id="usuario" name="usuario" value="<?= s($usuario_input) ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required>
            </div>

            <button type="submit">Entrar</button>
        </form>

        <div class="footer">
            &copy; <?= date('Y') ?> - Gestão Financeira Pessoal
        </div>
    </div>
</body>
</html>
