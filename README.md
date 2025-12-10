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
git clone https://github.com/<username>/watsons-mvp.git
cd watsons-mvp

2. 建立資料庫
MySQL 建立資料庫 watsons
匯入 SQL 表：
CREATE TABLE customer (...);
CREATE TABLE product (...);
CREATE TABLE order (...);
CREATE TABLE order_detail (...);
CREATE TABLE membership_level (...);

3. MongoDB 建立資料庫 watsons，collection reviews
Redis 安裝並啟動

4. 後端設定
PHP: 設定 db.php 連線 MySQL
Node.js (可選): server.js 連線 MongoDB + Redis

5.前端
開啟 index.php / information.php / admin_dashboard.php
確保 PHP Server 或 Apache 運行
