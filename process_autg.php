<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$host = '127.0.0.1';
$port = '3306';
$db   = 'l2mythras';
$user = 'root';
$pass = 'mas3510';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Erro de Conexão MySQL: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ==========================================
    // LOGIN
    // ==========================================
    if ($action === 'login') {
        $login = trim($_POST['login'] ?? '');
        $senha = $_POST['password'] ?? '';

        if (empty($login) || empty($senha)) {
            $_SESSION['error'] = "Preencha todos os campos!";
            header("Location: login.php");
            exit;
        }

        $stmt = $pdo->prepare("SELECT login, password FROM accounts WHERE login = ?");
        $stmt->execute([$login]);
        $conta = $stmt->fetch();

        if ($conta) {
            $senhaBanco = $conta['password'];

            // Vamos testar Whirlpool, Sha512, Sha1 e texto puro
            $hashWhirlpool = base64_encode(hash('whirlpool', $senha, true));
            $hashSha512    = base64_encode(hash('sha512', $senha, true));
            $hashSha1      = base64_encode(sha1($senha, true));
            $hashMd5       = md5($senha);

            if (
                $senhaBanco === $hashWhirlpool ||
                $senhaBanco === $hashSha512 ||
                $senhaBanco === $hashSha1 || 
                $senhaBanco === $hashMd5 || 
                $senhaBanco === $senha
            ) {
                $_SESSION['user_login'] = $conta['login'];
                header("Location: dashboard.php");
                exit;
            }
        }

        $_SESSION['error'] = "Login ou senha incorretos!";
        header("Location: login.php");
        exit;
    }

    // ==========================================
    // CRIAR CONTA (REGISTRAR)
    // ==========================================
    if ($action === 'register') {
        $login    = trim($_POST['login'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $senha    = $_POST['password'] ?? '';
        $senhaCf  = $_POST['password_confirm'] ?? '';

        if (empty($login) || empty($email) || empty($senha)) {
            $_SESSION['error'] = "Preencha todos os campos de registro!";
            header("Location: login.php");
            exit;
        }

        if ($senha !== $senhaCf) {
            $_SESSION['error'] = "As senhas não coincidem!";
            header("Location: login.php");
            exit;
        }

        $stmt = $pdo->prepare("SELECT login FROM accounts WHERE login = ?");
        $stmt->execute([$login]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['error'] = "Este login já está em uso!";
            header("Location: login.php");
            exit;
        }

        // Gera o hash usando Whirlpool (padrão comum em revs L2J modernas para hashes de 88 caracteres)
        $passwordHash = base64_encode(hash('whirlpool', $senha, true));

        $insert = $pdo->prepare("INSERT INTO accounts (login, password, email) VALUES (?, ?, ?)");
        
        if ($insert->execute([$login, $passwordHash, $email])) {
            $_SESSION['user_login'] = $login;
            header("Location: dashboard.php");
            exit;
        } else {
            $_SESSION['error'] = "Erro ao registrar conta no banco.";
            header("Location: login.php");
            exit;
        }
    }

    // ==========================================
    // RECUPERAÇÃO DE SENHA
    // ==========================================
    if ($action === 'recover') {
        $login = trim($_POST['login'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($login) || empty($email)) {
            $_SESSION['error'] = "Informe o login e o e-mail!";
            header("Location: login.php");
            exit;
        }

        $stmt = $pdo->prepare("SELECT login, email FROM accounts WHERE login = ?");
        $stmt->execute([$login]);
        $conta = $stmt->fetch();

        if (!$conta) {
            $_SESSION['error'] = "Esta conta não existe no servidor!";
            header("Location: login.php");
            exit;
        }

        if (empty($conta['email']) || $conta['email'] === '') {
            $updateEmail = $pdo->prepare("UPDATE accounts SET email = ? WHERE login = ?");
            $updateEmail->execute([$email, $login]);
            $conta['email'] = $email;
        }

        if (strtolower(trim($conta['email'])) !== strtolower($email)) {
            $_SESSION['error'] = "O e-mail informado não confere com esta conta!";
            header("Location: login.php");
            exit;
        }

        $novaSenhaTemp = substr(md5(mt_rand()), 0, 8);
        $novoHash = base64_encode(hash('whirlpool', $novaSenhaTemp, true));

        $updatePass = $pdo->prepare("UPDATE accounts SET password = ? WHERE login = ?");
        
        if ($updatePass->execute([$novoHash, $login])) {
            echo "<div style='background:#0a0502;color:#f5e6d3;padding:40px;font-family:Rajdhani,sans-serif;text-align:center;border:2px solid #ff6b35;max-width:500px;margin:80px auto;border-radius:10px;'>";
            echo "<h2 style='font-family:Orbitron;color:#ffd700;margin-bottom:15px;'>Redefinição Concluída!</h2>";
            echo "<p style='margin-bottom:10px;'>Sua nova senha temporária para a conta <b>$login</b> é:</p>";
            echo "<h1 style='color:#fff;background:#1a0b03;padding:15px;border-radius:6px;border:1px solid #ff6b35;font-family:Orbitron;letter-spacing:2px;margin:15px 0;'>$novaSenhaTemp</h1>";
            echo "<p style='color:#a89678;font-size:0.95rem;margin-bottom:20px;'>O e-mail <b>$email</b> foi vinculado a esta conta com sucesso. Anote sua senha temporária e faça o login.</p>";
            echo "<a href='login.php' style='background:linear-gradient(180deg,#ff6b35,#ff3d00);color:#fff;padding:12px 25px;text-decoration:none;border-radius:6px;font-family:Orbitron;font-size:0.85rem;font-weight:bold;'>IR PARA O LOGIN</a>";
            echo "</div>";
            exit;
        } else {
            $_SESSION['error'] = "Erro ao atualizar a senha no banco de dados.";
            header("Location: login.php");
            exit;
        }
    }
}
