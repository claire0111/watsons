<?php
// admin_dashboard.php
session_start();
require_once 'db.php';

// 未登入就踢回登入頁
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// 簡單統計
$totalProducts  = $pdo->query("SELECT COUNT(*) AS c FROM product")->fetch()['c'] ?? 0;
$totalCustomers = $pdo->query("SELECT COUNT(*) AS c FROM customer")->fetch()['c'] ?? 0;
$totalOrders    = $pdo->query("SELECT COUNT(*) AS c FROM `order`")->fetch()['c'] ?? 0;

// 最新 10 筆訂單（含顧客名稱與付款狀態）
$sql = "
    SELECT o.order_id,
           o.order_date,
           o.total_amount,
           c.name AS customer_name,
           p.status AS payment_status
    FROM `order` o
    JOIN customer c ON o.customer_id = c.customer_id
    LEFT JOIN payment p ON o.payment_id = p.payment_id
    ORDER BY o.order_date DESC
    LIMIT 10
";
$latestOrders = $pdo->query($sql)->fetchAll();

?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>後台儀表板 - 屈臣氏藥妝平台</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<header>
    <div class="title">屈臣氏藥妝平台 - 後台管理儀表板</div>
    <div class="user-info">
        管理員：<?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?>
        （角色：<?= htmlspecialchars($_SESSION['role_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>）
        <a href="logout.php">登出</a>
    </div>
</header>

<div class="container">
    <aside class="sidebar">
        <h2>主選單</h2>
        <a href="admin_dashboard.php" class="nav-link">儀表板</a>
        <a href="product_manage.php" class="nav-link">商品管理</a>
        <a href="order_manage.php" class="nav-link">訂單管理</a>
        <a href="member_manage.php" class="nav-link">會員管理</a>
        <a href="admin_manage.php" class="nav-link">後台帳號與權限</a>
    </aside>

    <main class="content">
        <div class="cards">
            <div class="card">
                <div class="card-title">商品數量</div>
                <div class="card-value"><?= (int)$totalProducts ?></div>
            </div>
            <div class="card">
                <div class="card-title">會員數量</div>
                <div class="card-value"><?= (int)$totalCustomers ?></div>
            </div>
            <div class="card">
                <div class="card-title">訂單總數</div>
                <div class="card-value"><?= (int)$totalOrders ?></div>
            </div>
        </div>

        <h2 class="section-title">最新訂單</h2>
        <table>
            <thead>
            <tr>
                <th>訂單編號</th>
                <th>顧客</th>
                <th>日期</th>
                <th>金額</th>
                <th>付款狀態</th>
            </tr>
            </thead>
            <tbody>
            <?php if (count($latestOrders) === 0): ?>
                <tr>
                    <td colspan="5">目前沒有訂單資料。</td>
                </tr>
            <?php else: ?>
                <?php foreach ($latestOrders as $order): ?>
                    <tr>
                        <td>#<?= htmlspecialchars($order['order_id'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($order['order_date'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>$<?= htmlspecialchars($order['total_amount'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php
                            $status = $order['payment_status'] ?? 'pending';
                            $class  = 'status-pending';
                            if ($status === 'paid')     $class = 'status-paid';
                            elseif ($status === 'failed')   $class = 'status-failed';
                            elseif ($status === 'refunded') $class = 'status-refunded';
                            ?>
                            <span class="status-badge <?= $class ?>">
                                <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </main>
</div>
</body>
</html>
