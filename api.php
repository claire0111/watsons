<?php
header('Content-Type: application/json');
session_start();
include("db.php");
$p = $_POST;

// 簡單會員資料存 Session（示範用）
// if (!isset($_SESSION['users'])) {
//     $_SESSION['users'] = [
//         ['username' => 'test', 'password' => password_hash('1234', PASSWORD_DEFAULT), 'email' => 'test@test.com', 'token' => '']
//     ];
// }


// 模擬商品資料
// $products = [
//     ['id'=>1,'name'=>'維他命C 500mg 60顆','brand'=>'BrandA','price'=>499,'image'=>'https://via.placeholder.com/240x240?text=VitC'],
//     ['id'=>2,'name'=>'蜂蜜膠囊 30顆','brand'=>'BrandB','price'=>399,'image'=>'https://via.placeholder.com/240x240?text=Honey'],
//     ['id'=>3,'name'=>'兒童綜合維生素','brand'=>'BrandC','price'=>299,'image'=>'https://via.placeholder.com/240x240?text=Kids']
// ];

// switch ($_GET['action']) {
//     case "products": //顯示商品
//         $res = fetchall(query("SELECT * FROM `product` ORDER BY `product`.`product_id` ASC"));
//         foreach ($res as $i => $row) {
//             $res[$i]['picture'] = "src/products/{$row['product_id']}_1.jpg";
//         }
//         echo json_encode($res);
//         break;

//     case "register": // 註冊
//         $username = $body['username'] ?? '';
//         $email = $body['email'] ?? '';
//         $password = $body['password'] ?? '';
//         if (!$username || !$email || !$password) {
//             echo json_encode(['success' => false, 'msg' => '資料不完整']);
//             exit;
//         }
//         foreach ($_SESSION['users'] as $u) {
//             if ($u['username'] === $username || $u['email'] === $email) {
//                 echo json_encode(['success' => false, 'msg' => '帳號或Email已存在']);
//                 exit;
//             }
//         }
//         $_SESSION['users'][] = ['username' => $username, 'email' => $email, 'password' => password_hash($password, PASSWORD_DEFAULT), 'token' => ''];
//         echo json_encode(['success' => true, 'msg' => '註冊成功']);
//         break;
// }




$action = $_GET['action'] ?? '';
$body = json_decode(file_get_contents('php://input'), true);

// 取得商品
if($action==='products'){
    $res = fetchall(query("SELECT * FROM `product` ORDER BY `product`.`product_id` ASC"));
        foreach ($res as $i => $row) {
            $res[$i]['picture'] = "src/products/{$row['product_id']}_1.jpg";
        }
        echo json_encode($res);

    exit;
}

// 註冊
if($action==='register'){
    $username = $body['username'] ?? '';
    $email = $body['email'] ?? '';
    $password = $body['password'] ?? '';
    if(!$username || !$email || !$password){
        echo json_encode(['success'=>false,'msg'=>'資料不完整']);
        exit;
    }
    foreach($_SESSION['users'] as $u){
        $res=fetch(query("SELECT * FROM `customer` WHERE `email`='{$email}'"));
        if($res){
            echo json_encode(['success'=>false,'msg'=>'帳號或Email已存在']);
            exit;
        }
    }
    query("INSERT INTO `customer`(`customer_id`, `name`, `email`, `password`, `phone`, `membership_level_id`, `points`) VALUES (null,'{$username}','{$email}','{$password}',null,null,0)");
    // $_SESSION['users'][]=['username'=>$username,'email'=>$email,'password'=>password_hash($password,PASSWORD_DEFAULT),'token'=>''];
    echo json_encode(['success'=>true,'msg'=>'註冊成功']);
    exit;
}

// 登入
if($action==='login'){
    $email = $body['email'] ?? '';
    $password = $body['password'] ?? '';
    foreach($_SESSION['users'] as &$u){
        if($res=fetch(query("SELECT * FROM `customer` WHERE `email`='{$email}' && `password`='{$password}'"))){
            // $token = bin2hex(random_bytes(16));
            // $u['token']=$token;
            $_SESSION["id"]=$res["customer_id"];
            $_SESSION["name"]=$res["name"];
            $_SESSION["login"]="on";
            echo json_encode(['success'=>true,'user'=>['username'=>$res['name'],'email'=>$u['email']],'token'=>$token]);
            exit;
        }
    }
    echo json_encode(['success'=>false]);
    exit;
}

// 忘記密碼
if($action==='forgot'){
    $email = $body['email'] ?? '';
    echo json_encode(['success'=>true,'msg'=>'重設密碼連結已寄送到 '.$email]);
    exit;
}

// 驗證 Token
if($action==='verify'){
    $token = $body['token'] ?? '';
    foreach($_SESSION['users'] as $u){
        if($u['token']===$token){
            echo json_encode(['success'=>true,'user'=>['username'=>$u['username'],'email'=>$u['email']]]); exit;
        }
    }
    echo json_encode(['success'=>false]);
    exit;
}

// 購物車結帳
if($action==='checkout'){
    $token = $body['token'] ?? '';
    $cart = $body['cart'] ?? [];
    if(!$token || empty($cart)){
        echo json_encode(['success'=>false,'msg'=>'請先登入或購物車為空']);
        exit;
    }
    // 這裡可儲存訂單到 DB，示範用 Session
    $_SESSION['orders'][]=['token'=>$token,'cart'=>$cart,'created_at'=>date('c')];
    echo json_encode(['success'=>true]);
    exit;
}

http_response_code(404);
echo json_encode(['success'=>false,'msg'=>'Not found']);
