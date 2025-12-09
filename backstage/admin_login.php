<?php
// admin_login.php
session_start();
require_once 'db.php';

// 如果已登入，直接丟去儀表板
if (isset($_SESSION['admin_id'])) {
    header('Location: admin_dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = '請輸入帳號與密碼';
    } else {
        $sql = "
            SELECT a.admin_id, a.username, a.password, a.role_id, r.role_name
            FROM admin a
            JOIN role r ON a.role_id = r.role_id
            WHERE a.username = :username
            LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':username' => $username]);
        $admin = $stmt->fetch();

        if ($admin) {
            // 若你的密碼目前是純文字，可以暫時改成: if ($password === $admin['password']) { ... }
            if (password_verify($password, $admin['password'])) {
                // 登入成功
                $_SESSION['admin_id']   = $admin['admin_id'];
                $_SESSION['username']   = $admin['username'];
                $_SESSION['role_id']    = $admin['role_id'];
                $_SESSION['role_name']  = $admin['role_name'];

                header('Location: admin_dashboard.php');
                exit;
            } else {
                $error = '帳號或密碼錯誤';
            }
        } else {
            $error = '帳號或密碼錯誤';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>後台登入 - 屈臣氏藥妝平台</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .login-container {
            background: #fff;
            padding: 32px 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
            width: 360px;
        }

        h1 {
            margin-top: 0;
            margin-bottom: 16px;
            font-size: 22px;
            text-align: center;
        }

        .field {
            margin-bottom: 16px;
        }

        label {
            display: block;
            margin-bottom: 4px;
            font-size: 14px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 8px 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
            box-sizing: border-box;
        }

        button {
            width: 45%;
            padding: 10px;
            border-radius: 6px;
            border: none;
            font-size: 15px;
            cursor: pointer;
            background: #0080ff;
            color: #fff;
        }

        .error {
            margin-bottom: 12px;
            padding: 8px;
            background: #ffe6e6;
            color: #c00;
            border-radius: 6px;
            font-size: 13px;
        }

        .hint {
            margin-top: 12px;
            font-size: 12px;
            color: #777;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <h1>後台管理登入</h1>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="field">
                <label for="username">帳號</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="field">
                <label for="password">密碼</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div align="center">
                <button type="button" onclick="location.href='../index.php'">回商店頁</button>&emsp;<button type="submit">登入</button>
            </div>



        </form>

        <div class="hint">
            提示：請先在 admin 資料表設定好 username / password（hash）。
        </div>
    </div>
</body>

</html>