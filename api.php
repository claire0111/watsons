<?php
header('Content-Type: application/json');
session_start();
include("db.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);
$action = $_GET['action'] ?? '';
$body = json_decode(file_get_contents("php://input"), true);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 匯入 PHPMailer (你下載的版本目錄)
require __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require __DIR__ . '/PHPMailer-master/src/SMTP.php';
require __DIR__ . '/PHPMailer-master/src/Exception.php';

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
// 忘記密碼：產生 token 寄信
if ($action === 'forgot') {
    // 讀取 Axios 傳來的 JSON
    $body  = json_decode(file_get_contents('php://input'), true);
    $email = trim($body['email'] ?? '');

    if ($email === '') {
        echo json_encode([
            'success' => false,
            'message' => '請輸入 Email'
        ]);
        exit;
    }

    // 看這信箱有沒有註冊過
    $stmt = $pdo->prepare("SELECT customer_id, name FROM customer WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    // 為了安全：即使查無此信箱，也回同一句話
    $genericMessage = '如果此 Email 有註冊，我們已寄出重設密碼信件，請檢查信箱。';

    if (!$customer) {
        echo json_encode([
            'success' => true,
            'message' => $genericMessage
        ]);
        exit;
    }

    $customerId   = $customer['customer_id'];
    $customerName = $customer['name'] ?? '會員';

    // 刪掉舊 token
    $del = $pdo->prepare("DELETE FROM reset_token WHERE customer_id = :cid");
    $del->execute([':cid' => $customerId]);

    // 產生新 token
    $token  = bin2hex(random_bytes(32));
    $expiry = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

    $ins = $pdo->prepare("
        INSERT INTO reset_token (customer_id, token, expiry_time)
        VALUES (:cid, :token, :exp)
    ");
    $ins->execute([
        ':cid'   => $customerId,
        ':token' => $token,
        ':exp'   => $expiry
    ]);

    // 這個連結你之前已經測試過可用
    $resetLink = "http://localhost/watsons/reset_password.php?token=" . urlencode($token);
    // ====== 使用 PHPMailer 寄信 ======
    $mail = new PHPMailer(true);

    try {
        // SMTP 伺服器設定（以 Gmail 為例）
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'liucindy058@gmail.com';   // TODO：改成你自己的 Gmail
        $mail->Password   = 'hnfp evgh bbvr lxir';       // TODO：改成 16 碼 App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // 編碼
        $mail->CharSet = 'UTF-8';

        // 寄件人 / 收件人
        $mail->setFrom('liucindy058@gmail.com', 'Watsons Demo'); // 寄件人
        $mail->addAddress($email, $customerName);              // 收件人

        // 內容
        $mail->isHTML(true);
        $mail->Subject = '屈臣氏購物平台 - 重設密碼連結';

        $mail->Body = "
            <p>{$customerName} 您好：</p>
            <p>請點擊以下連結重設您的密碼（1 小時內有效）：</p>
            <p><a href='{$resetLink}' target='_blank'>{$resetLink}</a></p>
            <p>如果您沒有申請重設密碼，請忽略此封信。</p>
        ";

        $mail->AltBody = "您好：\n\n請點擊以下連結重設您的密碼（1 小時內有效）：\n{$resetLink}\n\n如果您沒有申請重設密碼，請忽略此封信。";

        // 若想查看詳細錯誤，可暫時開啟：
        // $mail->SMTPDebug = 2;
        // $mail->Debugoutput = 'error_log';

        $mail->send();
    } catch (Exception $e) {
        // 不回傳給前端，但記錄伺服器 log，方便 debug
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);
        // 就算寄信失敗，也不要暴露給使用者細節
    }

    echo json_encode([
        'success' => true,
        'message' => $genericMessage
    ]);
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


    $user_id = $_SESSION['user']['id'];
    $cart = $body['cart'] ?? [];
    $total = $body['total'] ?? 0;
    $payment_id = $body['payment_id'] ?? 1; // 1=刷卡, 2=現金
    $point_used = floor($total / 100);

    if (!$cart) {
        echo json_encode(['success' => false, 'msg' => '購物車為空']);
        exit;
    }


    try {
        $pdo->beginTransaction();


        // 1️⃣ 建立訂單
        $stmt = $pdo->prepare("INSERT INTO `order`(`customer_id`, `order_date`, `total_amount`, `payment_id`, `point_used`) 
                          VALUES (?, NOW(), ?, ?, ?)");
        $stmt->execute([$user_id, $total, $payment_id, $point_used]);
        $order_id = $pdo->lastInsertId();

        // 2️⃣ 建立訂單明細 & 扣庫存
        $stmtDetail = $pdo->prepare("INSERT INTO `order_detail`(`order_id`,`product_id`,`quantity`,`unit_price`) VALUES (?,?,?,?)");
        $stmtStock = $pdo->prepare("UPDATE `product` SET stock = stock - ? WHERE product_id = ? AND stock >= ?");

        foreach ($cart as $item) {
            $stmtDetail->execute([$order_id, $item['product_id'], $item['qty'], $item['price']]);

            // 扣庫存
            $stmtStock->execute([$item['qty'], $item['product_id'], $item['qty']]);
            if ($stmtStock->rowCount() === 0) {
                throw new Exception("商品 {$item['product_name']} 庫存不足");
            }
        }

        // 3️⃣ 增加會員點數 (假設 1 元 = 1 點)
        $pointsEarned = intval($total + $point_used);
        $stmtPoints = $pdo->prepare("UPDATE `customer` SET points = points + ? WHERE customer_id = ?");
        $stmtPoints->execute([$pointsEarned, $user_id]);

        // 3. 付款驗證 (簡單範例)
        if ($payment_id == 1) {
            // 模擬刷卡驗證
            $paid = true; // 假設刷卡成功
            if (!$paid) {
                throw new Exception("刷卡失敗");
            }
        }

        $pdo->commit();


        // 清空 SESSION 購物車
        $_SESSION['cart'] = [];

        echo json_encode([
            'success' => true,
            'order_id' => $order_id,
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'msg' => $e->getMessage()
        ]);
    }

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
