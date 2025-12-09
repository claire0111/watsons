<?php
// order_manage.php
session_start();
require_once 'db.php';
require_once 'permission.php';

requirePermission(2); // 2 = view_orders

// 未登入就踢回登入頁
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$message = '';

// 允許的付款狀態（對應 payment.status enum）
$allowedStatuses = ['pending', 'paid', 'failed', 'refunded'];

// ---------- 處理付款狀態更新 ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action      = $_POST['action'] ?? '';
    if ($action === 'update_status') {
        $order_id   = intval($_POST['order_id'] ?? 0);
        $payment_id = intval($_POST['payment_id'] ?? 0);
        $status     = $_POST['status'] ?? '';

        if ($order_id <= 0 || $payment_id <= 0) {
            $message = '找不到對應的訂單或付款紀錄。';
        } elseif (!in_array($status, $allowedStatuses, true)) {
            $message = '無效的付款狀態。';
        } else {
            $stmt = $pdo->prepare("UPDATE payment SET status = :status WHERE payment_id = :pid");
            $ok   = $stmt->execute([
                ':status' => $status,
                ':pid'    => $payment_id,
            ]);
            $message = $ok ? '付款狀態已更新。' : '更新失敗。';
        }
    }
}

// ---------- 讀取訂單列表 ----------
$sql = "
    SELECT 
        o.order_id,
        o.order_date,
        o.total_amount,
        o.point_add,
        c.name AS customer_name,
        p.payment_id,
        p.payment_method,
        p.status AS payment_status
    FROM `order` o
    JOIN customer c ON o.customer_id = c.customer_id
    LEFT JOIN payment p ON o.payment_id = p.payment_id
    ORDER BY o.order_date DESC
";
$orders = $pdo->query($sql)->fetchAll();

// ---------- 若有指定查看明細的訂單 ----------
$viewOrder      = null;
$orderItems     = [];
if (isset($_GET['view_id'])) {
    $view_id = intval($_GET['view_id']);
    if ($view_id > 0) {
        // 訂單基本資訊
        $stmt = $pdo->prepare("
            SELECT 
                o.*,
                c.name AS customer_name,
                c.email,
                c.phone,
                p.payment_method,
                p.status AS payment_status
            FROM `order` o
            JOIN customer c ON o.customer_id = c.customer_id
            LEFT JOIN payment p ON o.payment_id = p.payment_id
            WHERE o.order_id = :id
        ");
        $stmt->execute([':id' => $view_id]);
        $viewOrder = $stmt->fetch();

        // 訂單商品明細
        if ($viewOrder) {
            $stmt = $pdo->prepare("
                SELECT 
                    od.order_detail_id,
                    od.product_id,
                    od.quantity,
                    od.unit_price,
                    p.product_name
                FROM order_detail od
                JOIN product p ON od.product_id = p.product_id
                WHERE od.order_id = :id
            ");
            $stmt->execute([':id' => $view_id]);
            $orderItems = $stmt->fetchAll();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>訂單管理 - 屈臣氏藥妝平台</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<header>
    <div class="title">屈臣氏藥妝平台 - 訂單管理</div>
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
        <h1>訂單管理</h1>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <!-- 訂單列表 -->
        <h2 class="section-title">全部訂單</h2>
        <table>
            <thead>
            <tr>
                <th>訂單編號</th>
                <th>顧客</th>
                <th>日期</th>
                <th>金額</th>
                <th>使用點數</th>
                <th>付款方式</th>
                <th>付款狀態</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            <?php if (count($orders) === 0): ?>
                <tr><td colspan="8">目前沒有訂單資料。</td></tr>
            <?php else: ?>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td>#<?= htmlspecialchars($o['order_id'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($o['customer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($o['order_date'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>$<?= htmlspecialchars($o['total_amount'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($o['point_used'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($o['payment_method'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php
                            $status = $o['payment_status'] ?? 'pending';
                            $class  = 'status-pending';
                            if     ($status === 'paid')     $class = 'status-paid';
                            elseif ($status === 'failed')   $class = 'status-failed';
                            elseif ($status === 'refunded') $class = 'status-refunded';
                            ?>
                            <?php if ($o['payment_id']): ?>
                                <span class="status-badge <?= $class ?>">
                                    <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            <?php else: ?>
                                <span class="status-badge status-pending">未建立</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <!-- 檢視明細 -->
                            <a href="order_manage.php?view_id=<?= (int)$o['order_id'] ?>">檢視明細</a>

                            <!-- 更新付款狀態（若有付款紀錄才顯示） -->
                            <?php if ($o['payment_id']): ?>
                                <form method="post" action="order_manage.php" style="display:inline-block; margin-left:4px;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?= (int)$o['order_id'] ?>">
                                    <input type="hidden" name="payment_id" value="<?= (int)$o['payment_id'] ?>">
                                    <select name="status" style="font-size:12px;">
                                        <?php foreach ($allowedStatuses as $st): ?>
                                            <option value="<?= $st ?>"
                                                <?= $st === $status ? 'selected' : '' ?>>
                                                <?= $st ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" style="font-size:12px; padding:3px 8px;">更新</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- 訂單明細區塊 -->
        <?php if ($viewOrder): ?>
            <div class="form-container" style="margin-top:24px;">
                <h2>訂單明細：#<?= htmlspecialchars($viewOrder['order_id'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p>
                    顧客：<?= htmlspecialchars($viewOrder['customer_name'], ENT_QUOTES, 'UTF-8') ?>
                    （Email：<?= htmlspecialchars($viewOrder['email'], ENT_QUOTES, 'UTF-8') ?>，
                    電話：<?= htmlspecialchars($viewOrder['phone'], ENT_QUOTES, 'UTF-8') ?>）
                </p>
                <p>
                    日期：<?= htmlspecialchars($viewOrder['order_date'], ENT_QUOTES, 'UTF-8') ?><br>
                    訂單金額：$<?= htmlspecialchars($viewOrder['total_amount'], ENT_QUOTES, 'UTF-8') ?><br>
                    使用點數：<?= htmlspecialchars($viewOrder['point_used'], ENT_QUOTES, 'UTF-8') ?><br>
                    付款方式：<?= htmlspecialchars($viewOrder['payment_method'] ?? '—', ENT_QUOTES, 'UTF-8') ?><br>
                    付款狀態：
                    <?php
                    $status = $viewOrder['payment_status'] ?? 'pending';
                    $class  = 'status-pending';
                    if     ($status === 'paid')     $class = 'status-paid';
                    elseif ($status === 'failed')   $class = 'status-failed';
                    elseif ($status === 'refunded') $class = 'status-refunded';
                    ?>
                    <span class="status-badge <?= $class ?>">
                        <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </p>

                <h3 style="margin-top:16px;">商品項目</h3>
                <table>
                    <thead>
                    <tr>
                        <th>品項編號</th>
                        <th>商品名稱</th>
                        <th>商品 ID</th>
                        <th>單價</th>
                        <th>數量</th>
                        <th>小計</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($orderItems) === 0): ?>
                        <tr><td colspan="6">此訂單目前沒有明細資料。</td></tr>
                    <?php else: ?>
                        <?php foreach ($orderItems as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['order_detail_id'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($item['product_id'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>$<?= htmlspecialchars($item['unit_price'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($item['quantity'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>$<?= htmlspecialchars($item['unit_price'] * $item['quantity'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
