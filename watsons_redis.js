// watsons_redis.js
import mysql from 'mysql2/promise';
import Redis from 'ioredis';

async function main() {
  try {
    // 1) 連接 MySQL
    const db = await mysql.createConnection({
      host: '127.0.0.1',
      user: 'root',
      password: '0502', // 改成你的 MySQL 密碼
      database: 'watsons_db'
    });

    // 2) 連接 Redis
    const redis = new Redis({
      host: '127.0.0.1',
      port: 6379
    });

    // 3) 讀取商品庫存
    const [rows] = await db.execute("SELECT product_id, stock FROM product");

    // 4) 同步到 Redis
    for (const row of rows) {
      const key = `stock:${row.product_id}`;
      await redis.set(key, row.stock, 'EX', 3600); // TTL 1 小時
      console.log(`SET ${key} = ${row.stock}`);
    }

    console.log("同步完成！");
    process.exit(0);

  } catch (err) {
    console.error("同步失敗:", err);
    process.exit(1);
  }
}

main();
