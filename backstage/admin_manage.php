<?php
// admin_manage.php
session_start();
require_once 'db.php';
require_once 'permission.php';

requirePermission(4); // 4 = view_admin_menage

// 未登入就踢回登入頁
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$message = '';

// 讀取角色列表
$roleStmt = $pdo->query("SELECT role_id, role_name FROM role ORDER BY role_id");
$roles = $roleStmt->fetchAll(PDO::FETCH_ASSOC);

// 讀取所有權限
$permStmt = $pdo->query("SELECT permission_id, description FROM permission ORDER BY permission_id");
$permissions = $permStmt->fetchAll(PDO::FETCH_ASSOC);

// 讀取目前 role_permission 對應
$rolePermMap = [];
$rpStmt = $pdo->query("SELECT role_id, permission_id FROM role_permission");
foreach ($rpStmt->fetchAll(PDO::FETCH_ASSOC) as $rp) {
    $r = (int)$rp['role_id'];
    $p = (int)$rp['permission_id'];
    if (!isset($rolePermMap[$r])) {
        $rolePermMap[$r] = [];
    }
    $rolePermMap[$r][$p] = true;
}

// ---------- 處理後台帳號 CRUD ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 共用欄位
    if ($action === 'create_admin' || $action === 'update_admin') {
        $admin_id = intval($_POST['admin_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $rawPassword = trim($_POST['password'] ?? '');
        $role_id  = intval($_POST['role_id'] ?? 0);

        if ($username === '' || $role_id <= 0) {
            $message = '請填寫帳號名稱並選擇角色。';
        } elseif ($action === 'create_admin' && $rawPassword === '') {
            $message = '新增後台帳號時，請設定密碼。';
        } else {
            try {
                if ($action === 'create_admin') {
                    $hashed = password_hash($rawPassword, PASSWORD_DEFAULT);
                    $sql = "INSERT INTO admin (username, password, role_id)
                            VALUES (:u, :p, :r)";
                    $stmt = $pdo->prepare($sql);
                    $ok = $stmt->execute([
                        ':u' => $username,
                        ':p' => $hashed,
                        ':r' => $role_id,
                    ]);
                    $message = $ok ? '後台帳號新增成功！' : '新增失敗。';
                } elseif ($action === 'update_admin' && $admin_id > 0) {
                    if ($rawPassword !== '') {
                        // 更新帳號 + 密碼 + 角色
                        $hashed = password_hash($rawPassword, PASSWORD_DEFAULT);
                        $sql = "UPDATE admin
                                SET username = :u,
                                    password = :p,
                                    role_id  = :r
                                WHERE admin_id = :id";
                        $params = [
                            ':u'  => $username,
                            ':p'  => $hashed,
                            ':r'  => $role_id,
                            ':id' => $admin_id,
                        ];
                    } else {
                        // 不改密碼
                        $sql = "UPDATE admin
                                SET username = :u,
                                    role_id  = :r
                                WHERE admin_id = :id";
                        $params = [
                            ':u'  => $username,
                            ':r'  => $role_id,
                            ':id' => $admin_id,
                        ];
                    }
                    $stmt = $pdo->prepare($sql);
                    $ok = $stmt->execute($params);
                    $message = $ok ? '後台帳號已更新。' : '更新失敗。';
                }
            } catch (PDOException $e) {
                $message = '資料庫錯誤：' . $e->getMessage();
            }
        }
    }

    // 更新角色的權限設定
    if ($action === 'update_role_perm') {
        $role_id = intval($_POST['role_id'] ?? 0);
        $permIds = $_POST['permissions'] ?? [];

        if ($role_id <= 0) {
            $message = '找不到要更新的角色。';
        } else {
            // 轉成 int + 過濾
            $permIds = array_filter(array_map('intval', $permIds), fn($v) => $v > 0);

            $pdo->beginTransaction();
            try {
                // 清掉原本的該角色權限
                $del = $pdo->prepare("DELETE FROM role_permission WHERE role_id = :rid");
                $del->execute([':rid' => $role_id]);

                // 重建新的權限
                if (!empty($permIds)) {
                    $ins = $pdo->prepare("
                        INSERT INTO role_permission (role_id, permission_id)
                        VALUES (:rid, :pid)
                    ");
                    foreach ($permIds as $pid) {
                        $ins->execute([
                            ':rid' => $role_id,
                            ':pid' => $pid,
                        ]);
                    }
                }

                $pdo->commit();
                $message = '角色權限已更新。';
            } catch (PDOException $e) {
                $pdo->rollBack();
                $message = '更新權限失敗：' . $e->getMessage();
            }
        }
    }
}

// ---------- 刪除後台帳號 ----------
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    if ($delete_id > 0) {
        // 可選：避免自己刪除自己
        if ($delete_id == $_SESSION['admin_id']) {
            $message = '不能刪除目前登入中的帳號。';
        } else {
            $stmt = $pdo->prepare("DELETE FROM admin WHERE admin_id = :id");
            $ok   = $stmt->execute([':id' => $delete_id]);
            $message = $ok ? '後台帳號已刪除。' : '刪除失敗。';
        }
    }
}

// ---------- 若有要編輯的後台帳號 ----------
$editAdmin = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    if ($edit_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE admin_id = :id");
        $stmt->execute([':id' => $edit_id]);
        $editAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// ---------- 讀取全部後台帳號列表 ----------
$sql = "
    SELECT a.admin_id, a.username, a.role_id, r.role_name
    FROM admin a
    JOIN role r ON a.role_id = r.role_id
    ORDER BY a.admin_id ASC
";
$admins = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>後台帳號與權限 - 屈臣氏藥妝平台</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<header>
    <div class="title">屈臣氏藥妝平台 - 後台帳號與權限</div>
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
        <h1>後台帳號與權限管理</h1>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <!-- 後台帳號表單 -->
        <div class="form-container">
            <?php if ($editAdmin): ?>
                <h2>編輯後台帳號 (#<?= $editAdmin['admin_id']; ?>)</h2>
            <?php else: ?>
                <h2>新增後台帳號</h2>
            <?php endif; ?>

            <form method="post" action="admin_manage.php<?= $editAdmin ? '?edit_id=' . intval($editAdmin['admin_id']) : ''; ?>">
                <input type="hidden" name="admin_id" value="<?= $editAdmin['admin_id'] ?? 0 ?>">

                <div class="form-row">
                    <label for="username">帳號：</label>
                    <input type="text" id="username" name="username" required
                           value="<?= $editAdmin ? htmlspecialchars($editAdmin['username']) : ''; ?>">
                </div>

                <div class="form-row">
                    <label for="password">密碼：</label>
                    <input type="password" id="password" name="password"
                           placeholder="<?= $editAdmin ? '留空則不變更' : '新增帳號時必填'; ?>">
                </div>

                <div class="form-row">
                    <label for="role_id">角色：</label>
                    <select id="role_id" name="role_id" required>
                        <option value="">--請選擇角色--</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['role_id']; ?>"
                                <?= $editAdmin && $editAdmin['role_id'] == $r['role_id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($r['role_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($editAdmin): ?>
                    <input type="hidden" name="action" value="update_admin">
                    <button type="submit">更新帳號</button>
                    <a href="admin_manage.php" class="btn" style="background:#6b7280; margin-left:8px;">取消編輯</a>
                <?php else: ?>
                    <input type="hidden" name="action" value="create_admin">
                    <button type="submit">新增帳號</button>
                <?php endif; ?>
            </form>
        </div>

        <!-- 後台帳號列表 -->
        <h2 class="section-title">後台帳號列表</h2>
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>帳號</th>
                <th>角色</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            <?php if (count($admins) === 0): ?>
                <tr><td colspan="4">目前沒有後台帳號。</td></tr>
            <?php else: ?>
                <?php foreach ($admins as $a): ?>
                    <tr>
                        <td><?= $a['admin_id']; ?></td>
                        <td><?= htmlspecialchars($a['username']); ?></td>
                        <td><?= htmlspecialchars($a['role_name']); ?></td>
                        <td class="actions">
                            <a href="admin_manage.php?edit_id=<?= $a['admin_id']; ?>">編輯</a>
                            <a href="admin_manage.php?delete_id=<?= $a['admin_id']; ?>"
                               onclick="return confirm('確定要刪除這個後台帳號嗎？');">刪除</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- 角色權限設定 -->
        <h2 class="section-title" style="margin-top:24px;">角色權限設定</h2>

        <?php foreach ($roles as $r): ?>
            <div class="form-container" style="margin-top:12px;">
                <h3>角色：<?= htmlspecialchars($r['role_name']); ?> (ID: <?= $r['role_id']; ?>)</h3>
                <form method="post" action="admin_manage.php">
                    <input type="hidden" name="action" value="update_role_perm">
                    <input type="hidden" name="role_id" value="<?= $r['role_id']; ?>">

                    <?php foreach ($permissions as $p): ?>
                        <?php
                        $pid = (int)$p['permission_id'];
                        $checked = isset($rolePermMap[$r['role_id']][$pid]);
                        ?>
                        <label style="display:inline-block; margin-right:16px; margin-top:6px;">
                            <input type="checkbox" name="permissions[]" value="<?= $pid; ?>"
                                <?= $checked ? 'checked' : ''; ?>>
                            <?= htmlspecialchars($p['description']); ?> (ID: <?= $pid; ?>)
                        </label>
                    <?php endforeach; ?>

                    <div style="margin-top:10px;">
                        <button type="submit">更新此角色的權限</button>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>

    </main>
</div>
</body>
</html>
