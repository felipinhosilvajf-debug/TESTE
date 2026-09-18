<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (admin_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = $_SESSION['admin_error'] ?? null;
unset($_SESSION['admin_error']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>L2 Virtual Boost — Administração</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;

            background:
                radial-gradient(
                    circle at top,
                    #121b2b 0%,
                    #080b12 45%,
                    #05070b 100%
                );

            color: #e7edf5;
            font-family: Arial, sans-serif;
        }

        .login-box {
            width: 420px;
            max-width: calc(100% - 30px);

            background: rgba(11, 16, 25, .96);

            border: 1px solid rgba(117, 215, 255, .18);

            box-shadow:
                0 0 40px rgba(55, 150, 255, .12);

            padding: 38px;

            border-radius: 12px;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            margin: 0;
            font-size: 28px;
            letter-spacing: 2px;
        }

        .logo span {
            display: block;
            margin-top: 8px;
            color: #75d7ff;
            font-size: 12px;
            letter-spacing: 3px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #8995a5;
            font-size: 13px;
        }

        input {
            width: 100%;
            padding: 14px;

            background: #080b12;
            border: 1px solid rgba(130, 170, 220, .18);

            color: #fff;

            border-radius: 6px;

            margin-bottom: 18px;

            outline: none;
        }

        input:focus {
            border-color: #4ca8ff;
        }

        button {
            width: 100%;
            padding: 14px;

            border: 0;
            border-radius: 6px;

            background: linear-gradient(
                90deg,
                #1769aa,
                #5634b8
            );

            color: white;

            font-weight: bold;

            cursor: pointer;
        }

        button:hover {
            filter: brightness(1.15);
        }

        .error {
            background: rgba(217, 106, 106, .12);
            border: 1px solid rgba(217, 106, 106, .25);

            color: #ff9b9b;

            padding: 12px;
            margin-bottom: 20px;

            border-radius: 6px;

            font-size: 13px;
        }
    </style>
</head>

<body>

<div class="login-box">

    <div class="logo">
        <h1>L2 VIRTUAL BOOST</h1>
        <span>ADMINISTRATION PANEL</span>
    </div>

    <?php if ($error): ?>
        <div class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="process_login.php">

        <input
            type="hidden"
            name="csrf"
            value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>"
        >

        <label>Administrador</label>

        <input
            type="text"
            name="username"
            required
            autocomplete="username"
        >

        <label>Senha</label>

        <input
            type="password"
            name="password"
            required
            autocomplete="current-password"
        >

        <button type="submit">
            ENTRAR NO PAINEL
        </button>

    </form>

</div>

</body>
</html>