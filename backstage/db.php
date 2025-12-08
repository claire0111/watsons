<?php
// db.php：共用的資料庫連線

$host    = '127.0.0.1';
$db      = 'watsons_db';
$user    = 'root';      // 依你的 XAMPP 設定調整
$pass    = '';          // 若有設定密碼，填在這裡
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    exit('資料庫連線失敗：' . $e->getMessage());
}
