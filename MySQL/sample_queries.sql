-- 列出商品名稱及價格
SELECT product_name, price FROM product;

-- 查詢所有客戶的名稱及其對應的會員等級名稱
SELECT c.*, ml.level_name
    FROM customer c
    LEFT JOIN membership_level ml
        ON c.points >= ml.threshold_amount
    WHERE ml.threshold_amount = (
        SELECT MAX(threshold_amount) 
        FROM membership_level 
        WHERE threshold_amount <= c.points
    )
    ORDER BY c.customer_id ASC;

-- 價格介於 100 元到 200 元之間，且屬於 '清潔' 分類  (category_id = 1) 的商品
SELECT product_name AS '商品名稱', price AS '價格', stock AS '庫存', category_id AS '分類' 
FROM product
WHERE category_id = 1
AND price >= 100.00
AND price <= 200.00
ORDER BY price DESC;

-- 每個商品分類的商品總數和平均價格。篩選出平均價格超過 300 元的分類，並依平均價格由高到低排序
SELECT cat.category_id AS '分類 ID', cat.category_name AS '分類名稱',
COUNT(p.product_id) AS '商品總數', ROUND(AVG(p.price), 2) AS '平均價格'
FROM product p
JOIN category cat ON p.category_id = cat.category_id
GROUP BY cat.category_id, cat.category_name
HAVING AVG(p.price) > 300.00
ORDER BY AVG(p.price) DESC;

-- INSERT 新商品
INSERT INTO product (product_id, product_name, category_id)
VALUES (999, 'test_product', 1);

-- UPDATE 新增的商品
UPDATE product
SET product_name = 'updated_test_product'
WHERE product_id = 999;

-- DELETE 剛剛新增且更新過的同一筆資料
DELETE FROM product
WHERE product_id = 999;


-- 索引
CREATE TABLE `admin` (
  `admin_id` bigint NOT NULL AUTO_INCREMENT,
  `username` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_id` int NOT NULL,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `username` (`username`),
  KEY `fk_admin_role` (`role_id`),
  CONSTRAINT `fk_admin_role` FOREIGN KEY (`role_id`) REFERENCES `role` (`role_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) 