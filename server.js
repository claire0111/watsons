// server.js
import express from 'express';
import cors from 'cors';
import { MongoClient } from 'mongodb';
import Redis from 'ioredis';
import mysql from 'mysql2/promise';

const app = express();
const PORT = 3001;

app.use(cors());
app.use(express.json());

/* --------------------------
   1. MongoDB Connection
--------------------------- */
const mongoClient = new MongoClient('mongodb://localhost:27017');
await mongoClient.connect();
const db = mongoClient.db('watsons');
const reviewsCollection = db.collection('reviews');
console.log('✅ MongoDB connected');

/* --------------------------
   2. Redis Connection
--------------------------- */
const redis = new Redis({ host: '127.0.0.1', port: 6379 });
redis.on('connect', () => console.log('✅ Redis connected'));
redis.on('error', (err) => console.error('Redis error', err));

/* --------------------------
   3. MySQL Connection
--------------------------- */
const mysqlPool = await mysql.createPool({
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'watsons_db',
});
console.log("✅ MySQL connected");

/* --------------------------
   4. 取得評論（含使用者名稱）
--------------------------- */
app.get('/reviews/:productId', async (req, res) => {
    try {
        const product_id = parseInt(req.params.productId);

        // 取 MongoDB 內的評論
        const reviews = await reviewsCollection
            .find({ product_id })
            .sort({ created_at: -1 })
            .toArray();

        if (reviews.length === 0) {
            return res.json({ success: true, reviews: [] });
        }

        // 取所有 customer_id
        const customerIds = reviews.map(r => r.customer_id);

        // 查 MySQL 使用者名稱
        const [users] = await mysqlPool.query(
            `SELECT customer_id, name 
             FROM customer 
             WHERE customer_id IN (?)`,
            [customerIds]
        );

        // 建 user map（快速對照）
        const userMap = {};
        users.forEach(u => {
            userMap[u.customer_id] = u.name;
        });

        // 把 username 加進 review
        const mergedReviews = reviews.map(r => ({
            ...r,
            username: userMap[r.customer_id] ?? "匿名"
        }));

        res.json({ success: true, reviews: mergedReviews });

    } catch (err) {
        console.error(err);
        res.status(500).json({ success: false, msg: "無法取得評論" });
    }
});

/* --------------------------
   5. 新增評論
--------------------------- */
app.post('/reviews', async (req, res) => {
    try {
        const { product_id, customer_id, rating, comment } = req.body;

        if (!customer_id || !comment) {
            return res.json({ success: false, msg: '缺少參數' });
        }

        const review = {
            product_id,
            customer_id,
            rating: parseInt(rating),
            comment,
            created_at: new Date().toISOString()
        };

        await reviewsCollection.insertOne(review);

        res.json({ success: true, review });

    } catch (err) {
        console.error(err);
        res.status(500).json({ success: false, msg: '無法新增評論' });
    }
});

/* --------------------------
   6. Redis 庫存查詢
--------------------------- */
app.get('/stock/:productId', async (req, res) => {
    try {
        const productId = req.params.productId;
        const stock = await redis.get(`stock:${productId}`);

        if (stock === null) {
            return res.status(404).json({ success: false, msg: "商品庫存不存在" });
        }

        res.json({ success: true, productId, stock: Number(stock) });

    } catch (err) {
        console.error(err);
        res.status(500).json({ success: false, msg: "無法取得庫存" });
    }
});

/* --------------------------
   7. 啟動 server
--------------------------- */
app.listen(PORT, () => {
    console.log(`🚀 Server running at http://localhost:${PORT}`);
});
