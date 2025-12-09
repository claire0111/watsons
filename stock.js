// stock.js
import express from 'express';
import Redis from 'ioredis';

const router = express.Router();
const redis = new Redis({ host: '127.0.0.1', port: 6379 });

// 查庫存
router.get('/stock/:productId', async (req, res) => {
  const productId = req.params.productId;
  try {
    const stock = await redis.get(`stock:${productId}`);
    if (stock === null) {
      return res.status(404).json({ success: false, message: '無庫存資料' });
    }

    res.json({
      success: true,
      productId,
      stock: Number(stock),
      canOrder: Number(stock) > 0
    });

  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

export default router;
