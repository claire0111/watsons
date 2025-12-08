<?php
header('Content-Type: application/json');
session_start();
include("db.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);
$action = $_GET['action'] ?? '';
$body = json_decode(file_get_contents("php://input"), true);


// ----------------------------------------------
// 取得商品
// ----------------------------------------------
if ($action === "products") {

    $res = fetchall(query("SELECT * FROM `product` ORDER BY `product_id` ASC"));

    foreach ($res as $i => $row) {
        // 依你的格式自動加入圖片 URL
        $res[$i]["picture"] = "src/products/{$row['category_id']}/{$row['product_id']}.jpg";
    }

    echo json_encode($res);
    exit;
}


// ----------------------------------------------
// 取得商品分類
// ----------------------------------------------
if ($action === "categories") {

    $res = fetchall(query("SELECT * FROM `category` ORDER BY `category_id` ASC"));

    echo json_encode($res);
    exit;
}



// ----------------------------------------------
// 註冊
// ----------------------------------------------
if ($action === "register") {

    $username = $body['username'] ?? '';
    $email = $body['email'] ?? '';
    $password = $body['password'] ?? '';

    if (!$username || !$email || !$password) {
        echo json_encode(['success' => false, 'msg' => '資料不完整']);
        exit;
    }

    // 檢查 email 是否已存在
    $chk = fetch(query("SELECT * FROM customer WHERE email='{$email}'"));
    if ($chk) {
        echo json_encode(['success' => false, 'msg' => 'Email 已存在']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    query("INSERT INTO `customer`(`customer_id`, `name`, `email`, `password`, `phone`, `address_line1`, `address_line2`, `district`, `city`, `postal_code`, `membership_level_id`, `points`) VALUES
            (null,'{$username}','{$email}','{$hash}','','','','','','','1','0')");



    echo json_encode(['success' => true, 'msg' => '註冊成功']);
    exit;
}



// ----------------------------------------------
// 登入（使用 SESSION）
// ----------------------------------------------
if ($action === "login") {

    $email = $body['email'] ?? '';
    $password = $body['password'] ?? '';

    if (!$email || !$password) {
        echo json_encode(['success' => false, 'msg' => '請輸入帳密']);
        exit;
    }

    $u = fetch(query("SELECT * FROM customer WHERE email='{$email}'"));
    if (!$u) {
        echo json_encode(['success' => false, 'msg' => '帳號不存在']);
        exit;
    }

    if (!password_verify($password, $u['password'])) {
        echo json_encode(['success' => false, 'msg' => '密碼錯誤']);
        exit;
    }

    // 記錄 SESSION
    $_SESSION["login"] = true;
    $_SESSION["user"] = [
        'id' => $u['customer_id'],
        'name' => $u['name'],
        'email' => $u['email']
    ];

    echo json_encode([
        'success' => true,
        'user' => $_SESSION["user"]
    ]);
    exit;
}



// ----------------------------------------------
// 回傳目前 SESSION 使用者資訊
// index.vue mounted() 會用到
// ----------------------------------------------
if ($action === "session") {

    if (!empty($_SESSION["login"]) && !empty($_SESSION["user"])) {
        echo json_encode([
            'logged' => true,
            'user' => $_SESSION["user"]
        ]);
    } else {
        echo json_encode(['logged' => false]);
    }
    exit;
}


// ----------------------------------------------
// 登出
// ----------------------------------------------
if ($action === "logout") {
    session_destroy();
    header('Location:index.php');
    exit;
}



// ----------------------------------------------
// 忘記密碼（示範）
// ----------------------------------------------
// if ($action === "forgot") {
//     $email = $body['email'] ?? '';
//     echo json_encode(['success' => true, 'msg' => "重設密碼連結已寄送至 $email"]);
//     exit;
// }

if ($action === "forgot") {

    // 接收 JSON
    // $body = json_decode(file_get_contents("php://input"), true);

    if (!$body || empty($body["email"])) {
        echo json_encode(["success" => false, "message" => "缺少 email"]);
        exit;
    }

    $email = $body["email"];

    // 查詢 email 是否存在
    $stmt = $pdo->prepare("SELECT customer_id, name FROM customer WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["success" => false, "message" => "此 email 未註冊"]);
        exit;
    }

    $customerId = $user["customer_id"];

    // 產生 token
    $token = bin2hex(random_bytes(32));
    $expires = date("Y-m-d H:i:s", time() + 900); // 15 分鐘有效

    // 寫入資料庫
    $stmt = $pdo->prepare("
        INSERT INTO reset_token (customer_id, token, expiry_time)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$customerId, $token, $expires]);

    // 發送 email
    require "vendor/autoload.php";
    $mail = new PHPMailer\PHPMailer\PHPMailer();

    $mail->isSMTP();
    $mail->Host = "smtp.gmail.com";
    $mail->SMTPAuth = true;
    $mail->Username = "你的gmail帳號";
    $mail->Password = "你的應用程式密碼";
    $mail->SMTPSecure = "tls";
    $mail->Port = 587;

    $mail->setFrom("你的gmail帳號", "Watsons 客服中心");
    $mail->addAddress($email);

    $mail->Subject = "Watsons 密碼重設通知";
    $mail->Body = "請點擊以下連結重設密碼：\n\nhttp://localhost/watsons/reset_password.php?token=$token\n\n連結15分鐘內有效。";

    if ($mail->send()) {
        echo json_encode(["success" => true, "message" => "已寄出重設密碼信"]);
    } else {
        echo json_encode(["success" => false, "message" => "寄信失敗：" . $mail->ErrorInfo]);
    }

    exit;
}



// ----------------------------------------------
// 結帳
// ----------------------------------------------
if ($action === "checkout") {

    if (empty($_SESSION['login'])) {
        echo json_encode(['success' => false, 'msg' => '請先登入']);
        exit;
    }

    $cart = $body['cart'] ?? [];
    $total = $body['total'] ?? 0;

    if (!$cart) {
        echo json_encode(['success' => false, 'msg' => '購物車為空']);
        exit;
    }

    // (可加上寫入資料庫)
    $_SESSION['orders'][] = [
        'user' => $_SESSION['user'],
        'cart' => $cart,
        'total' => $total,
        'created_at' => date("Y-m-d H:i:s")
    ];

    echo json_encode(['success' => true]);
    exit;
}



// ----------------------------------------------
// 取得個人資料
// ----------------------------------------------
if ($action === "getProfile") {

    if (empty($_SESSION['login'])) {
        echo json_encode(['success' => false, 'msg' => '未登入']);
        exit;
    }

    $uid = $_SESSION['user']['id'];

    $u = fetch(query("SELECT * FROM customer WHERE customer_id = {$uid}"));

    echo json_encode([
        'success' => true,
        'profile' => $u
    ]);
    exit;
}



// ----------------------------------------------
// 更新個人資料
// ----------------------------------------------
if ($action === "updateProfile") {

    if (empty($_SESSION['login'])) {
        echo json_encode(['success' => false, 'msg' => '未登入']);
        exit;
    }

    $uid = $_SESSION['user']['id'];

    $name  = $body['name'] ?? '';
    $phone = $body['phone'] ?? '';
    $city  = $body['city'] ?? '';
    $district = $body['district'] ?? '';
    $zip   = $body['postal_code'] ?? '';
    $addr1 = $body['address_line1'] ?? '';
    $addr2 = $body['address_line2'] ?? '';

    query("UPDATE customer SET name='{$name}', phone='{$phone}',city='{$city}',district='{$district}',postal_code='{$zip}',address_line1='{$addr1}',address_line2='{$addr2}' WHERE customer_id = {$uid}");
    // echo "UPDATE customer SET name='{$name}', phone='{$phone}',city='{$city}',district='{$district}',postal_code='{$zip}',address_line1='{$addr1}',address_line2='{$addr2}' WHERE customer_id = {$uid}";
    echo json_encode(['success' => true, 'msg' => '資料已更新']);
    exit;
}



// ----------------------------------------------
// 取得商品資料
// ----------------------------------------------
if ($action === "logproducts") {
    $product_id  = $body['product_id'] ?? '';
    // echo $product_id;
    $res = fetch(query("SELECT * FROM `product` WHERE `product_id`='{$product_id}' ORDER BY `product_id` ASC"));
    $res["picture"] = "src/products/{$res['category_id']}/{$res['product_id']}.jpg";
    echo json_encode([
        'success' => true,
        'product' => $res 
    ]);

    exit;
}



// ----------------------------------------------
// 新增商品到購物車
// ----------------------------------------------
if ($action === 'addToCart') {

    $product_id = $body['product_id'] ?? 0;
    $qty = max(1, intval($body['qty'] ?? 1));

    if (!$product_id) {
        echo json_encode(['success' => false, 'msg' => '商品ID錯誤']);
        exit;
    }

    // 取得商品資訊
    $res = fetch(query("SELECT * FROM `product` WHERE `product_id`='{$product_id}'"));
    if (!$res) {
        echo json_encode(['success' => false, 'msg' => '找不到商品']);
        exit;
    }

    // 初始化購物車
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    // 若已存在購物車就增加數量
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['qty'] += $qty;
    } else {
        $_SESSION['cart'][$product_id] = [
            'product_id' => $res['product_id'],
            'product_name' => $res['product_name'],
            'price' => $res['price'],
            'qty' => $qty,
        ];
    }

    echo json_encode(['success' => true, 'cart' => array_values($_SESSION['cart'])]);
    exit;
}



// ----------------------------------------------
// 取得購物車
// ----------------------------------------------
if ($action === 'getCart') {
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    echo json_encode(['success' => true, 'cart' => array_values($_SESSION['cart'])]);
    exit;
}



// ----------------------------------------------
// 更新購物車數量
// ----------------------------------------------
if ($action === 'updateCart') {
    $product_id = $body['product_id'] ?? 0;
    $qty = intval($body['qty'] ?? 1);

    if (isset($_SESSION['cart'][$product_id])) {
        if ($qty <= 0) {
            unset($_SESSION['cart'][$product_id]); // 刪除
        } else {
            $_SESSION['cart'][$product_id]['qty'] = $qty;
        }
    }

    echo json_encode(['success' => true, 'cart' => array_values($_SESSION['cart'])]);
    exit;
}

// ----------------------------------------------
echo json_encode(['success' => false, 'msg' => 'Unknown Action']);
http_response_code(404);
exit;
