
<?php
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=watsons_db;charset=utf8",
        "root",
        ""
    );

    // 啟用 PDO 例外錯誤模式，才會看到詳細錯誤訊息
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("資料庫連線失敗：" . $e->getMessage());
}

function query($query){
    global $pdo;
    return $pdo->query($query);
}
function fetch($res){
    return $res->fetch();
}
function fetchall($res){
    return $res->fetchAll();
}
function rownum($res){
    return $res->rowCount();
}
?>




