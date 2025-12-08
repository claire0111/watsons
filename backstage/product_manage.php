<?php
// product_manage.php
session_start();
require_once 'db.php';
require_once 'permission.php';

requirePermission(1); // 1 = manage_products

// 未登入就踢回登入頁
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$message = '';

// ---------- 處理新增 / 更新 ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action       = $_POST['action'] ?? '';
    $product_name = trim($_POST['product_name'] ?? '');
    $price        = floatval($_POST['price'] ?? 0);
    $stock        = intval($_POST['stock'] ?? 0);
    $category_id  = intval($_POST['category_id'] ?? 0);

    if ($product_name === '' || $price < 0 || $stock < 0 || $category_id <= 0) {
        $message = '請確認商品名稱、價格、庫存、分類都有正確填寫。';
    } else {
        if ($action === 'create') {
            $sql = "INSERT INTO product (product_name, price, stock, category_id)
                    VALUES (:name, :price, :stock, :cat)";
            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute([
                ':name'  => $product_name,
                ':price' => $price,
                ':stock' => $stock,
                ':cat'   => $category_id,
            ]);
            $message = $ok ? '商品新增成功！' : '新增失敗';
        } elseif ($action === 'update') {
            $product_id = intval($_POST['product_id'] ?? 0);
            if ($product_id > 0) {
                $sql = "UPDATE product
                        SET product_name = :name,
                            price = :price,
                            stock = :stock,
                            category_id = :cat
                        WHERE product_id = :id";
                $stmt = $pdo->prepare($sql);
                $ok = $stmt->execute([
                    ':name'  => $product_name,
                    ':price' => $price,
                    ':stock' => $stock,
                    ':cat'   => $category_id,
                    ':id'    => $product_id,
                ]);
                $message = $ok ? '商品更新成功！' : '更新失敗';
            } else {
                $message = '找不到要更新的商品。';
            }
        }
    }
}

// ---------- 處理刪除 ----------
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    if ($delete_id > 0) {
        $stmt = $pdo->prepare("DELETE FROM product WHERE product_id = :id");
        $ok   = $stmt->execute([':id' => $delete_id]);
        $message = $ok ? '商品已刪除。' : '刪除失敗。';
    }
}

// ---------- 讀取分類 (下拉選單) ----------
$stmt = $pdo->query("SELECT category_id, category_name FROM category ORDER BY category_name");
$categories = $stmt->fetchAll();

// ---------- 若有要編輯的商品，先撈資料 ----------
$editProduct = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    if ($edit_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM product WHERE product_id = :id");
        $stmt->execute([':id' => $edit_id]);
        $editProduct = $stmt->fetch();
    }
}

// ---------- 讀取商品列表 ----------
$sql = "SELECT p.product_id, p.product_name, p.price, p.stock, c.category_name
        FROM product p
        LEFT JOIN category c ON p.category_id = c.category_id
        ORDER BY p.product_id ASC";
$stmt = $pdo->query($sql);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>後台商品管理 - 屈臣氏藥妝平台</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<header>
    <div class="title">屈臣氏藥妝平台 - 後台商品管理</div>
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
        <h1>後台商品管理</h1>
        <p>目前登入帳號：<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></p>

        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- 新增 / 編輯商品表單 -->
        <div class="form-container">
            <?php if ($editProduct): ?>
                <h2>編輯商品 (#<?php echo $editProduct['product_id']; ?>)</h2>
            <?php else: ?>
                <h2>新增商品</h2>
            <?php endif; ?>

            <form method="post" action="product_manage.php<?php echo $editProduct ? '?edit_id=' . intval($editProduct['product_id']) : ''; ?>">
                <div class="form-row">
                    <label for="product_name">商品名稱：</label>
                    <input type="text" id="product_name" name="product_name" required
                           value="<?php echo $editProduct ? htmlspecialchars($editProduct['product_name']) : ''; ?>">
                </div>
                <div class="form-row">
                    <label for="price">價格：</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required
                           value="<?php echo $editProduct ? htmlspecialchars($editProduct['price']) : ''; ?>">
                </div>
                <div class="form-row">
                    <label for="stock">庫存：</label>
                    <input type="number" id="stock" name="stock" min="0" required
                           value="<?php echo $editProduct ? htmlspecialchars($editProduct['stock']) : ''; ?>">
                </div>
                <div class="form-row">
                    <label for="category_id">分類：</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">--請選擇分類--</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>"
                                <?php if ($editProduct && $editProduct['category_id'] == $cat['category_id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($editProduct): ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="product_id" value="<?php echo $editProduct['product_id']; ?>">
                    <button type="submit">更新商品</button>
                    <a href="product_manage.php" class="btn" style="background:#6b7280; margin-left:8px;">取消編輯</a>
                <?php else: ?>
                    <input type="hidden" name="action" value="create">
                    <button type="submit">新增商品</button>
                <?php endif; ?>
            </form>
        </div>

        <!-- 商品列表 -->
        <h2 class="section-title">商品列表</h2>
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>商品名稱</th>
                <th>分類</th>
                <th>價格</th>
                <th>庫存</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            <?php if (count($products) > 0): ?>
                <?php foreach ($products as $row): ?>
                    <tr>
                        <td><?php echo $row['product_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['category_name'] ?? '未分類'); ?></td>
                        <td><?php echo $row['price']; ?></td>
                        <td><?php echo $row['stock']; ?></td>
                        <td class="actions">
                            <a href="product_manage.php?edit_id=<?php echo $row['product_id']; ?>">編輯</a>
                            <a href="product_manage.php?delete_id=<?php echo $row['product_id']; ?>"
                               onclick="return confirm('確定要刪除這個商品嗎？');">刪除</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6">目前沒有商品資料。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </main>
</div>
</body>
</html>
