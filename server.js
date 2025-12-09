// server.js
import express from 'express';
import cors from 'cors';
import { MongoClient } from 'mongodb';
import Redis from 'ioredis';

const app = express();
const PORT = 3001;

app.use(cors());
app.use(express.json());

// --------------------
// 1. MongoDB 連線
// --------------------
const mongoClient = new MongoClient('mongodb://localhost:27017');
await mongoClient.connect();
const db = mongoClient.db('watsons');
const reviewsCollection = db.collection('reviews');
console.log('✅ MongoDB connected');

// --------------------
// 2. Redis 連線
// --------------------
const redis = new Redis({ host: '127.0.0.1', port: 6379 });
redis.on('connect', () => console.log('✅ Redis connected'));
redis.on('error', (err) => console.error('Redis error', err));

// --------------------
// 3. MongoDB 評論 API
// --------------------

// 取得商品評論
app.get('/reviews/:productId', async (req, res) => {
    try {
        const product_id = parseInt(req.params.productId);
        const reviews = await reviewsCollection.find({ product_id }).sort({ created_at: -1 }).toArray();
        res.json({ success: true, reviews });
    } catch (err) {
        console.error(err);
        res.status(500).json({ success: false, msg: '無法取得評論' });
    }
});

// 新增評論
app.post('/reviews', async (req, res) => {
    try {
        const { product_id, customer_id, rating, comment } = req.body;
        if (!customer_id || !comment) return res.json({ success: false, msg: '缺少參數' });

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

// --------------------
// 4. Redis 庫存 API
// --------------------
app.get('/stock/:productId', async (req, res) => {
    try {
        const productId = req.params.productId;
        const stock = await redis.get(`stock:${productId}`);
        if (stock === null) return res.status(404).json({ success: false, msg: '商品庫存不存在' });

        res.json({ success: true, productId, stock: Number(stock) });
    } catch (err) {
        console.error(err);
        res.status(500).json({ success: false, msg: '無法取得庫存' });
    }
});

// --------------------
// 5. 啟動 Server
// --------------------
app.listen(PORT, () => {
    console.log(`🚀 Server running at http://localhost:${PORT}`);
});
