
<?php
$pdo=new PDO("mysql:host=localhost;dbname=watsons_db;chatset=utf8","root","");

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




