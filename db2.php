<?php
// ==========================================
// 1. MySQL 資料庫連線設定 (讀取商品資訊)
// ==========================================

$servername = "localhost";
$username = "root";   // 請改成您的資料庫帳號
$password = "";       // 請改成您的資料庫密碼
$dbname = "watsons_db"; // 您的資料庫名稱
$dbport = "3307"; // 您的資料庫port

// 建立連線
$conn = new mysqli($servername, $username, $password, $dbname, $dbport);

// 檢查連線
if ($conn->connect_error) {
    die("連線失敗: " . $conn->connect_error);
}
// 設定編碼為 utf8mb4 避免中文亂碼
$conn->set_charset("utf8mb4");
?>