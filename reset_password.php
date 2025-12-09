<?php
// reset_password.php
require_once 'db.php';

$token   = $_GET['token'] ?? '';
$message = '';
$valid   = false;
$customerId = null;

if ($token !== '') {
    $stmt = $pdo->prepare("
        SELECT rt.customer_id, rt.expiry_time
        FROM reset_token rt
        WHERE rt.token = :token
        LIMIT 1
    ");
    $stmt->execute([':token' => $token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $now = new DateTime();
        $exp = new DateTime($row['expiry_time']);

        if ($exp >= $now) {
            $valid = true;
            $customerId = $row['customer_id'];
        } else {
            $message = '此連結已過期，請重新申請重設密碼。';
        }
    } else {
        $message = '無效的重設密碼連結。';
    }
} else {
    $message = '缺少 token 參數。';
}

// 若 token 有效 且送出表單
if ($valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($password === '' || strlen($password) < 6) {
        $message = '密碼至少 6 碼以上。';
    } elseif ($password !== $password2) {
        $message = '兩次輸入的密碼不一致。';
    } else {
        // 1. hash 密碼
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // 2. 更新 customer 密碼
        $upd = $pdo->prepare("
            UPDATE customer
            SET password = :pwd
            WHERE customer_id = :cid
        ");
        $upd->execute([
            ':pwd' => $hash,
            ':cid' => $customerId
        ]);

        // 3. 刪除 token，避免重複使用
        $del = $pdo->prepare("DELETE FROM reset_token WHERE token = :token");
        $del->execute([':token' => $token]);

        $message = '密碼已重設成功，請回首頁重新登入。';
        $valid = false;  // 不再秀表單
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>重設密碼 - Watsons Demo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <h3 class="card-title mb-3">重設密碼</h3>

          <?php if ($message): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php endif; ?>

          <?php if ($valid): ?>
            <form method="post">
              <div class="mb-3">
                <label class="form-label">新密碼（至少 6 碼）</label>
                <input type="password" name="password" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">再輸入一次新密碼</label>
                <input type="password" name="password2" class="form-control" required>
              </div>
              <button type="submit" class="btn btn-primary w-100">更新密碼</button>
            </form>
          <?php else: ?>
            <a href="index.php" class="btn btn-secondary mt-2">回到首頁</a>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
