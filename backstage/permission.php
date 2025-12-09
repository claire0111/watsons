<?php
// permission.php
// session_start();
require_once 'db.php';

function hasPermission($role_id, $permission_id) {
    global $pdo;

    // 查詢 role_permission
    $sql = "SELECT 1 
            FROM role_permission 
            WHERE role_id = :rid AND permission_id = :pid
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':rid' => $role_id,
        ':pid' => $permission_id
    ]);

    return $stmt->fetch() ? true : false;
}

// 用來阻擋無權限訪問的頁面
function requirePermission($permission_id) {
    if (!isset($_SESSION['role_id'])) {
        header("Location: admin_login.php");
        exit;
    }

    $role_id = $_SESSION['role_id'];

    if (!hasPermission($role_id, $permission_id)) {
        // 可自行美化
        echo "<script>alert('{$role_id} {$permission_id}'); window.location='admin_dashboard.php';</script>";
        // echo "<script>alert('您沒有權限進入此頁面！'); window.location='admin_dashboard.php';</script>";
        exit;
    }
}
