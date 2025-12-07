<?php
include('db2.php');
session_start();

// --- 動態接收網址傳來的 ID ---
$target_id = null;

// 情況 A: 網址有帶 ID (例如 product.php?id=3) -> 優先權最高
if (isset($_POST['product_id']) && !empty($_POST['product_id'])) {
    $target_id = (int)$_POST['product_id'];
    $_SESSION['product_id'] = $target_id; // 更新 Session，記住這個商品
} 
// 情況 B: 網址沒帶 ID，但 Session 裡有紀錄 (例如剛剛看過) -> 優先權次之
elseif (isset($_SESSION['product_id']) && !empty($_SESSION['product_id'])) {
    $target_id = (int)$_SESSION['product_id'];
}
// 情況 C: 網址沒 ID，Session 也沒紀錄 (全新訪客) -> $target_id 維持 null

if ($target_id) {
    // 如果有指定 ID (來自網址或 Session)，就抓那一個
    $sql = "SELECT * FROM product WHERE product_id = $target_id";
} else {
    // 如果都沒有 (情況 C)，就抓資料庫裡的第一個商品
    $sql = "SELECT * FROM product LIMIT 1";
}

$result = $conn->query($sql);

// 初始化商品變數
$product = [
    'name'  => '查無此商品',
    'price' => 0,
    'image' => '' // 您的資料庫無圖片欄位，依指示留白
];

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();

    // 根據您的 watsons_db_值_更2.sql 內容對應欄位
    $product['name']  = $row['product_name']; // 對應 SQL 的 product_name
    $product['price'] = $row['price'];        // 對應 SQL 的 price
    //product['image'] 
} else {
    // 若 ID 存在但資料庫找不到該筆資料
    die("找不到 ID 為 $product_id 的商品。");
}

$conn->close();
// ==========================================
// 2. NoSQL 評論資料與後端運算 (目前留空)
// ==========================================
$all_reviews = [];

$total_reviews = count($all_reviews);
$sum_rating = 0;
// 初始化各星級計數 (5星到1星)
$star_counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

foreach ($all_reviews as $review) {
    $r = $review['rating'];
    if ($r >= 1 && $r <= 5) {
        $star_counts[$r]++;
        $sum_rating += $r;
    }
}

$average_rating = ($total_reviews > 0) ? round($sum_rating / $total_reviews, 1) : 0.0;
// 輔助函式：產生星星 HTML (例如 4 分顯示 ★★★★☆)
function renderStars($rating) {
    $full_stars = floor($rating);
    $output = '';
    for ($i = 0; $i < $full_stars; $i++) { $output .= '★'; }
    for ($i = $full_stars; $i < 5; $i++) { $output .= '☆'; }
    return $output;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - 商品評論</title>
    <style>
        body {
            background: #f6f6f6;
            font-family: "Noto Sans TC", sans-serif;
            margin: 0;
        }

        .watsons-header {
            background: #00a0af;
            color: white;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo-text { font-size: 24px; font-weight: bold; }
        .header-actions { display: flex; gap: 10px; }

        .page-content {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* 商品資訊區塊 */
        .product-main-info {
            display: flex;
            gap: 30px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        .product-image-wrapper { 
            width: 250px; 
            height: 250px; 
            border: 1px solid #eee; 
            overflow: hidden; 
            flex-shrink: 0; 
            background-color: #f0f0f0; /* 空白圖片的背景色 */
        }
        .product-image { width: 100%; height: 100%; object-fit: cover; }
        .product-details { display: flex; flex-direction: column; justify-content: center; }
        .product-name { font-size: 28px; font-weight: bold; margin-bottom: 15px; color: #333; }
        .product-price { font-size: 22px; color: #c00; font-weight: bold; }

        /* 評論區塊 */
        .review-section {
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 0;
            position: relative;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .review-header {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .review-content { display: flex; align-items: flex-start; }
        
        .average-rating { flex-shrink: 0; width: 150px; text-align: center; }
        .rating-number { font-size: 56px; font-weight: 500; margin-bottom: 5px; color: #333; }
        .empty-stars { font-size: 24px; color: #ccc; letter-spacing: 2px; }
        
        .rating-breakdown { flex-grow: 1; padding-left: 30px; }
        .rating-row { display: flex; align-items: center; margin-bottom: 6px; font-size: 14px; }
        .rating-stars { width: 70px; color: #ffc107; flex-shrink: 0; font-size: 18px; }
        .rating-bar-wrapper {  width: 800px; height: 8px; background-color: #f0f0f0; border-radius: 4px; margin: 0 30px; overflow: hidden; }
        .rating-bar { height: 100%; background-color: #ffc107; width: 0%; transition: width 0.5s ease; }
        .rating-count { width: 20px; text-align: right; color: #555; font-size: 16px; }

        .write-review-button {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: #343a40;
            color: white;
            border: none;
            padding: 7px 12px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .separator-line {
            height: 1px;
            background-color: #e0e0e0;
            margin: 20px 0;
        }

        .all-reviews-container {
            margin-top: 20px;
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 40px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .single-review { padding: 15px 0; border-bottom: 1px solid #f0f0f0; }
        .single-review:last-child { border-bottom: none; }
        
        .review-user-info { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .review-user-id { font-weight: bold; color: #343a40; font-size: 16px; }
        .review-stars { color: #ffc107; font-size: 18px; }
        .review-comment { color: #555; line-height: 1.6; }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #fefefe;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .modal-title { font-size: 20px; font-weight: bold; color: #343a40; }
        .modal-close { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
        .modal-label { font-weight: bold; color: #555; margin-top: 15px; margin-bottom: 8px; display: block; }
        .modal-rating-stars { font-size: 32px; color: #ffc107; cursor: pointer; }
        .modal-rating-stars span { margin-right: 5px; }
        
        .modal-textarea {
            width: 95%;
            min-height: 100px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            font-size: 14px;
            box-sizing: border-box;
        }
        .modal-submit-button {
            width: 100%;
            background-color: #6495ed;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 4px;
            margin-top: 20px;
            font-size: 16px;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <header class="watsons-header">
        <div class="logo-text">Watsons 屈臣氏</div>
        <div class="header-actions"></div>
    </header>
    <div class="page-content">
        <div class="product-main-info">
            <div class="product-image-wrapper">
                <img src="" alt="商品圖片" class="product-image">
            </div>
            
            <div class="product-details">
                <div class="product-name">
                    <?php echo htmlspecialchars($product['name']); ?>
                </div>
                <div class="product-price">
                    NT$ <?php echo number_format($product['price']); ?>
                </div>
            </div>
        </div>
        <div class="review-section">
            <div class="review-header">商品評論</div>
            <button class="write-review-button" onclick="openReviewModal()">撰寫評論</button>

            <div class="review-content">
                <div class="average-rating">
                    <div class="rating-number"><?php echo $average_rating; ?></div>
                    <div class="empty-stars" style="color: #ffc107;">
                        <?php echo renderStars($average_rating); ?>
                    </div>
                </div>

                <div class="rating-breakdown">
                    <?php 
                    for ($star = 5; $star >= 1; $star--) {
                        $count = $star_counts[$star];
                        $percentage = ($total_reviews > 0) ? ($count / $total_reviews) * 100 : 0;
                    ?>
                    <div class="rating-row">
                        <span class="rating-stars">
                            <?php echo renderStars($star); ?>
                        </span>
                        <div class="rating-bar-wrapper">
                            <div class="rating-bar" style="width: <?php echo $percentage; ?>%;"></div>
                        </div>
                        <span class="rating-count"><?php echo $count; ?></span>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <div class="all-reviews-container">
            <h3>全部評論 (共 <?php echo $total_reviews; ?> 則)</h3>
            
            <?php if (empty($all_reviews)): ?>
                <div class="single-review" style="text-align: center; color: #999;">目前尚無評論。</div>
            <?php else: ?>
                <?php foreach ($all_reviews as $review): ?>
                <div class="single-review">
                    <div class="review-user-info">
                        <span class="review-user-id"><?php echo htmlspecialchars($review['user_id']); ?></span>
                        <span class="review-stars">
                            <?php echo renderStars($review['rating']); ?>
                        </span>
                    </div>
                    <div class="review-comment">
                        評語：<?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div id="reviewModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">撰寫評論</div>
                <span class="modal-close" onclick="closeReviewModal()">&times;</span>
            </div>
            
            <form action="#" method="POST">
                <label class="modal-label">您的評分:</label>
                <input type="hidden" name="rating" id="ratingInput" value="5">
                
                <div class="modal-rating-stars">
                    <span data-value="1">★</span>
                    <span data-value="2">★</span>
                    <span data-value="3">★</span>
                    <span data-value="4">★</span>
                    <span data-value="5">★</span>
                </div>

                <label class="modal-label">您的心得:</label>
                <textarea class="modal-textarea" name="comment" placeholder="請輸入評語"></textarea>

                <button type="submit" class="modal-submit-button">提交評論</button>
            </form>
        </div>
    </div>
    <script>
        function openReviewModal() { document.getElementById('reviewModal').style.display = 'flex'; }
        function closeReviewModal() { document.getElementById('reviewModal').style.display = 'none'; }
        window.onclick = function(event) {
            if (event.target === document.getElementById('reviewModal')) closeReviewModal();
        }
        
        document.querySelectorAll('.modal-rating-stars span').forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.getAttribute('data-value'));
                document.getElementById('ratingInput').value = rating;
                document.querySelectorAll('.modal-rating-stars span').forEach(s => {
                    s.style.color = (parseInt(s.getAttribute('data-value')) <= rating) ? '#ffc107' : '#ccc';
                });
            });
        });
    </script>
</body>
</html>