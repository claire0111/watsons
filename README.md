# 屈臣氏藥妝平台 (Watsons)
## 專案簡介
這是一個簡易的藥妝平台範例系統，支援會員管理、商品管理、訂單管理、購物車與結帳功能。系統同時支援 前台會員操作 與 後台管理員操作。


## 核心功能

- 會員 CRUD：新增、編輯、刪除、查詢會員，顯示會員點數與等級。

- 商品 CRUD：新增、編輯、刪除、查詢商品，支援商品圖片管理。

- 訂單 CRUD：建立訂單、修改訂單狀態、取消訂單。

- 購物車：可加購、刪除、修改商品數量。

- 結帳流程：刷卡/現金付款，點數累積，庫存扣減。

- 會員等級與進度條：銀卡、金卡、白金卡，根據累積消費自動升級。

- 商品評論：支援 MongoDB 儲存與查詢評論。

- 忘記密碼：利用Redis產生Reset Token，用於產生臨時重設密碼連結(限時15分鐘)。

- Session 驗證：SESSION驗證登入狀態，以及資料暫存。


## 系統架構
### 前端：
- HTML + CSS + Vue3 + Axios + JavaScript + Bootstrap 5

### 後端：
- PHP / Node.js (RESTful API)
- Session 驗證
- 評論整合

### 資料庫：
- MySQL (admin、customer、membership_level、category、product、cart_item、order、order_detail、payment、role、role_permission、permission、reset_token)
- MongoDB (reviews)
- Redis (Reset Token)


## 安裝教學
1. 克隆專案
  
   git clone https://github.com/<username>/watsons.git

   cd watsons.git
   

3. 匯入資料庫表

   https://github.com/claire0111/watsons/edit/main/MySQL/127_0_0_1.sql
   

4. MongoDB 建立資料庫 watsons，collection reviews
  
   Redis 安裝並啟動
   

5. 後端設定
  
   PHP: 設定 db.php 連線 MySQL

   Node.js (可選): server.js 連線 MongoDB + Redis
   

7. 前端

   開啟 index.php / information.php / admin_dashboard.php

   確保 PHP Server 或 Apache 運行


## 使用說明
### 前台會員

1. 註冊 / 登入會員

2. 忘記密碼

3. 瀏覽商品並加入購物車

4. 結帳付款（刷卡/現金）

5. 查看會員等級與點數進度

6. 撰寫商品評論

### 後台管理

1. 登入管理員帳號

2. CRUD 操作會員、商品、訂單

3. 管理會員等級與點數

4. 管理訂單狀態

5. 管理後台權限


## 專案結構
```
/__MACOSX
/MySQL              # MySQL檔
/node_modules       # Node.js 依賴套件
/NoSQL              # NoSQL檔
/PHPMailer-master   # 郵件功能的第三方函式庫
/src 
  白金卡.png      # 會員卡圖片
  金卡.png        # 會員卡圖片
  銀卡.png        # 會員卡圖片
  /products       # 商品圖片
/backend
  admin_dashboard.php        # 管理員儀表板
  admin_login.php            # 管理者登入入口
  admin_manage.php           # 
  admin.css                  # 
  db.php                     # MySQL 連線設定
  generate_hash.php          # 
  logout.php                 # 管理者登出
  member_manage.php          # 
  order_manage.php           # 訂單管理功能
  permission.php             # 後台權限管理的核心頁面
  product_manage.php         # 商品管理功能
server.js                 # Node.js server (MongoDB + Redis)
index.php                 # 前台首頁
information.php           # 會員資料頁
api.php                   # PHP API
db.php                    # MySQL 連線設定
product_details.php       # 商品詳細頁面
reset_password.php        # 重設密碼功能
reviewsapi.js             # 處理評論 API 的 JavaScript 檔案，可能連線 MongoDB
server.js                 # Node.js 服務器入口
stock.js                  # 庫存相關邏輯
watsons_redis.js          # 處理 Redis 相關功能的 JavaScript 檔案
backtomysql.js            # 
forgetintegrate.js        # 
README.md                 # 
taiwan_adderss_data.json  # 台灣地址資料
```


## 技術棧

- 前端：HTML, CSS, Vue3, Bootstrap 5, Axios, JavaScript

- 後端：PHP, Node.js

- 資料庫：MySQL, MongoDB, Redis

- 驗證： Session

