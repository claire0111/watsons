<?php
// member_manage.php
session_start();
require_once 'db.php';
require_once 'permission.php';

requirePermission(3); //view member

// 未登入就踢回登入頁
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$message = '';

// 讀取會員等級 (下拉選單用)
$levelStmt = $pdo->query("SELECT level_id, level_name FROM membership_level ORDER BY level_id");
$levels = $levelStmt->fetchAll(PDO::FETCH_ASSOC);

// ---------- 新增 / 更新 會員 ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action'] ?? '';
    $customer_id = intval($_POST['customer_id'] ?? 0);

    $name        = trim($_POST['name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $address1    = trim($_POST['address_line1'] ?? '');
    $address2    = trim($_POST['address_line2'] ?? '');
    $district    = trim($_POST['district'] ?? '');
    $city        = trim($_POST['city'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $level_id    = $_POST['membership_level_id'] !== '' ? intval($_POST['membership_level_id']) : null;
    $points      = intval($_POST['points'] ?? 0);
    $rawPassword = trim($_POST['password'] ?? '');

    if ($name === '' || $email === '' || $address1 === '' || $district === '' || $city === '' || $postal_code === '') {
        $message = '請填寫必填欄位（姓名、Email、主要地址、區、城市、郵遞區號）。';
    } elseif ($points < 0) {
        $message = '點數不可為負數。';
    } elseif ($action === 'create' && $rawPassword === '') {
        $message = '新增會員時請設定密碼。';
    } else {
        // 正式環境請務必使用雜湊儲存密碼
        $hashedPassword = $rawPassword !== '' ? password_hash($rawPassword, PASSWORD_DEFAULT) : null;

        try {
            if ($action === 'create') {
                $sql = "
                    INSERT INTO customer
                        (name, email, password, phone, address_line1, address_line2,
                         district, city, postal_code , points)
                    VALUES
                        (:name, :email, :password, :phone, :addr1, :addr2,
                         :district, :city, :postal_code,  :points)
                ";
                $stmt = $pdo->prepare($sql);
                $ok = $stmt->execute([
                    ':name'        => $name,
                    ':email'       => $email,
                    ':password'    => $hashedPassword, // 使用密碼雜湊
                    ':phone'       => $phone,
                    ':addr1'       => $address1,
                    ':addr2'       => $address2,
                    ':district'    => $district,
                    ':city'        => $city,
                    ':postal_code' => $postal_code,
                    ':points'      => $points,
                ]);
                $message = $ok ? '會員新增成功！' : '新增會員失敗。';
            } elseif ($action === 'update' && $customer_id > 0) {
                // 若密碼欄位有填，就一併更新密碼；沒填就維持原密碼
                if ($hashedPassword) {
                    $sql = "
                        UPDATE customer
                        SET name = :name,
                            email = :email,
                            password = :password,
                            phone = :phone,
                            address_line1 = :addr1,
                            address_line2 = :addr2,
                            district = :district,
                            city = :city,
                            postal_code = :postal_code,
                            points = :points
                        WHERE customer_id = :id
                    ";
                    $params = [
                        ':name'        => $name,
                        ':email'       => $email,
                        ':password'    => $hashedPassword,
                        ':phone'       => $phone,
                        ':addr1'       => $address1,
                        ':addr2'       => $address2,
                        ':district'    => $district,
                        ':city'        => $city,
                        ':postal_code' => $postal_code,
                        ':points'      => $points,
                        ':id'          => $customer_id,
                    ];
                } else {
                    $sql = "
                        UPDATE customer
                        SET name = :name,
                            email = :email,
                            phone = :phone,
                            address_line1 = :addr1,
                            address_line2 = :addr2,
                            district = :district,
                            city = :city,
                            postal_code = :postal_code,
                            points = :points
                        WHERE customer_id = :id
                    ";
                    $params = [
                        ':name'        => $name,
                        ':email'       => $email,
                        ':phone'       => $phone,
                        ':addr1'       => $address1,
                        ':addr2'       => $address2,
                        ':district'    => $district,
                        ':city'        => $city,
                        ':postal_code' => $postal_code,
                        ':points'      => $points,
                        ':id'          => $customer_id,
                    ];
                }

                $stmt = $pdo->prepare($sql);
                $ok = $stmt->execute($params);
                $message = $ok ? '會員資料已更新。' : '更新會員失敗。';
            }
        } catch (PDOException $e) {
            // 例如 Email 重複等錯誤
            $message = '資料庫錯誤：' . $e->getMessage();
        }
    }
}

// ---------- 刪除會員 ----------
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    if ($delete_id > 0) {
        $stmt = $pdo->prepare("DELETE FROM customer WHERE customer_id = :id");
        $ok   = $stmt->execute([':id' => $delete_id]);
        $message = $ok ? '會員已刪除。' : '刪除失敗。';
    }
}

// ---------- 若有要編輯的會員 ----------
$editMember = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    if ($edit_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM customer WHERE customer_id = :id");
        $stmt->execute([':id' => $edit_id]);
        $editMember = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// ---------- 讀取會員列表 ----------
$sql = "
    SELECT c.*, ml.level_name
    FROM customer c
    LEFT JOIN membership_level ml
        ON c.points >= ml.threshold_amount
    WHERE ml.threshold_amount = (
        SELECT MAX(threshold_amount) 
        FROM membership_level 
        WHERE threshold_amount <= c.points
    )
    ORDER BY c.customer_id ASC
";
$members = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>會員管理 - 屈臣氏藥妝平台</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<header>
    <div class="title">屈臣氏藥妝平台 - 會員管理</div>
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
        <h1>會員管理</h1>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <!-- 新增 / 編輯 會員表單 -->
        <div class="form-container">
            <?php if ($editMember): ?>
                <h2>編輯會員 (#<?= $editMember['customer_id']; ?>)</h2>
            <?php else: ?>
                <h2>新增會員</h2>
            <?php endif; ?>

            <form method="post" action="member_manage.php<?= $editMember ? '?edit_id=' . intval($editMember['customer_id']) : ''; ?>">
                <input type="hidden" name="customer_id" value="<?= $editMember['customer_id'] ?? 0 ?>">
                <div class="form-row">
                    <label for="name">姓名：</label>
                    <input type="text" id="name" name="name" required
                           value="<?= $editMember ? htmlspecialchars($editMember['name']) : ''; ?>">
                </div>
                <div class="form-row">
                    <label for="email">Email：</label>
                    <input type="email" id="email" name="email" required
                           value="<?= $editMember ? htmlspecialchars($editMember['email']) : ''; ?>">
                </div>
                <div class="form-row">
                    <label for="phone">電話：</label>
                    <input type="text" id="phone" name="phone"
                           value="<?= $editMember ? htmlspecialchars($editMember['phone']) : ''; ?>">
                </div>
                <div class="form-row">
                    <label for="address_line1">地址一：</label>
                    <input type="text" id="address_line1" name="address_line1" required
                           value="<?= $editMember ? htmlspecialchars($editMember['address_line1']) : ''; ?>">
                </div>
                <div class="form-row">
                    <label for="address_line2">地址二：</label>
                    <input type="text" id="address_line2" name="address_line2"
                           value="<?= $editMember ? htmlspecialchars($editMember['address_line2']) : ''; ?>">
                </div>
                <div class="form-row">
                    <label for="district">區：</label>
                    <input type="text" id="district" name="district" required
                           value="<?= $editMember ? htmlspecialchars($editMember['district']) : ''; ?>">
                </div>
                <div class="form-row">
                    <label for="city">城市：</label>
                    <input type="text" id="city" name="city" required
                           value="<?= $editMember ? htmlspecialchars($editMember['city']) : ''; ?>">
                </div>
                <div class="form-row">
                    <label for="postal_code">郵遞區號：</label>
                    <input type="text" id="postal_code" name="postal_code" required
                           value="<?= $editMember ? htmlspecialchars($editMember['postal_code']) : ''; ?>">
                </div>
                <div class="form-row">
                    <label for="membership_level_id">會員等級：</label>
                    <select id="membership_level_id" name="membership_level_id">
                        <option value="">--不指定--</option>
                        <?php foreach ($levels as $lv): ?>
                            <option value="<?= $lv['level_id']; ?>"
                                <?= $editMember && $editMember['membership_level_id'] == $lv['level_id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($lv['level_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <label for="points">點數：</label>
                    <input type="number" id="points" name="points" min="0"
                           value="<?= $editMember ? htmlspecialchars($editMember['points']) : 0; ?>">
                </div>
                <div class="form-row">
                    <label for="password">密碼：</label>
                    <input type="password" id="password" name="password"
                           placeholder="<?= $editMember ? '留空則不變更' : '新增會員時必填'; ?>">
                </div>

                <?php if ($editMember): ?>
                    <input type="hidden" name="action" value="update">
                    <button type="submit">更新會員</button>
                    <a href="member_manage.php" class="btn" style="background:#6b7280; margin-left:8px;">取消編輯</a>
                <?php else: ?>
                    <input type="hidden" name="action" value="create">
                    <button type="submit">新增會員</button>
                <?php endif; ?>
            </form>
        </div>

        <!-- 會員列表 -->
        <h2 class="section-title">會員列表</h2>
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>姓名</th>
                <th>Email</th>
                <th>電話</th>
                <th>城市 / 區</th>
                <th>地址</th>
                <th>等級</th>
                <th>點數</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            <?php if (count($members) === 0): ?>
                <tr><td colspan="9">目前沒有會員資料。</td></tr>
            <?php else: ?>
                <?php foreach ($members as $m): ?>
                    <tr>
                        <td><?= $m['customer_id']; ?></td>
                        <td><?= htmlspecialchars($m['name']); ?></td>
                        <td><?= htmlspecialchars($m['email']); ?></td>
                        <td><?= htmlspecialchars($m['phone']); ?></td>
                        <td><?= htmlspecialchars($m['city'] . ' / ' . $m['district']); ?></td>
                        <td><?= htmlspecialchars($m['address_line1']); ?></td>
                        <td><?= htmlspecialchars($m['level_name'] ?? '—'); ?></td>
                        <td><?= htmlspecialchars($m['points']); ?></td>
                        <td class="actions">
                            <a href="member_manage.php?edit_id=<?= $m['customer_id']; ?>">編輯</a>
                            <a href="member_manage.php?delete_id=<?= $m['customer_id']; ?>"
                               onclick="return confirm('確定要刪除這位會員嗎？此動作可能會同時刪除關聯訂單／購物車。');">刪除</a>
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
