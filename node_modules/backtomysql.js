import mysql from 'mysql2/promise';
import Redis from 'ioredis';

async function syncBackToMySQL() {
  try {
    // 1) 連線 MySQL
    const db = await mysql.createConnection({
      host: '127.0.0.1',
      user: 'root',
      password: '0502', // 改成你的 MySQL 密碼
      database: 'watsons_db'
    });

    // 2) 連線 Redis
    const redis = new Redis({
      host: '127.0.0.1',
      port: 6379
    });

    // 3) 取得 Redis 所有商品庫存
    // 這裡假設你有 104 個商品
    for (let productId = 1; productId <= 104; productId++) {
      const key = `stock:${productId}`;
      const stock = await redis.get(key);

      if (stock !== null) {
        // 4) 更新回 MySQL
        await db.execute('UPDATE product SET stock = ? WHERE product_id = ?', [parseInt(stock), productId]);
        console.log(`同步商品 ${productId} 庫存回 MySQL: ${stock}`);
      }
    }

    console.log("全部同步完成！");
    await db.end();
    redis.disconnect();

  } catch (err) {
    console.error("同步失敗:", err);
  }
}

syncBackToMySQL();
