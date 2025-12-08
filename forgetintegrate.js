// forget-password.js
import express from 'express';
import { createClient } from 'redis';
import crypto from 'crypto';
import mysql from 'mysql2/promise';
import bcrypt from 'bcrypt';
import nodemailer from 'nodemailer';

const app = express();
app.use(express.json());

const PORT = 3001;

// ---------------- Redis 連線 ----------------
const redisClient = createClient({ url: 'redis://127.0.0.1:6379' });
redisClient.on('error', (err) => console.log('Redis Client Error', err));
await redisClient.connect();
console.log('Connected to Redis!');

// ---------------- MySQL 連線 ----------------
const conn = await mysql.createConnection({
  host: 'localhost',
  user: 'dbhw',           // 你的 MySQL 帳號
  password: '0502',     // 你的 MySQL 密碼
  database: 'watsons_db'  // 資料庫名稱
});

// ---------------- Nodemailer 設定 ----------------
const transporter = nodemailer.createTransport({
  host: 'smtp.example.com',    // 你的 SMTP 服務
  port: 587,
  secure: false,
  auth: {
    user: '你的email@example.com',
    pass: '授權碼或密碼'
  }
});

// ---------------- 同步 MySQL Email 到 Redis ----------------
async function syncEmailsToRedis() {
  const [rows] = await conn.execute('SELECT customer_id, email FROM customer');
  for (const row of rows) {
    const key = `user_email:${row.customer_id}`;
    await redisClient.set(key, row.email);
    console.log(`Saved to Redis: ${key} → ${row.email}`);
  }
  console.log('All customers saved to Redis!');
}

// 啟動時自動同步
await syncEmailsToRedis();

// ---------------- 生成重置 Token ----------------
async function createResetToken(userId) {
  const token = crypto.randomBytes(16).toString('hex');
  const key = `reset_password:${userId}`;
  await redisClient.set(key, token, { EX: 600 }); // 10 分鐘過期
  return token;
}

// ---------------- 驗證 Token ----------------
async function verifyResetToken(userId, tokenInput) {
  const key = `reset_password:${userId}`;
  const tokenStored = await redisClient.get(key);
  if (!tokenStored || tokenStored !== tokenInput) return false;
  await redisClient.del(key);
  return true;
}

// ---------------- 更新密碼 ----------------
async function resetPassword(userId, tokenInput, newPassword) {
  if (await verifyResetToken(userId, tokenInput)) {
    const hashedPassword = await bcrypt.hash(newPassword, 10);
    await conn.execute(
      'UPDATE customer SET password=? WHERE customer_id=?',
      [hashedPassword, userId]
    );
    return true;
  }
  return false;
}

// ---------------- API ----------------

// 1️⃣ 忘記密碼請求
app.post('/forgot-password', async (req, res) => {
  try {
    const { userId } = req.body;
    const email = await redisClient.get(`user_email:${userId}`);
    if (!email) return res.status(404).json({ message: 'User not found' });

    const token = await createResetToken(userId);

    // 發送 Email
    await transporter.sendMail({
      from: '你的email@example.com',
      to: email,
      subject: '忘記密碼驗證碼',
      text: `你的重置密碼驗證碼: ${token}，10分鐘內有效`
    });

    res.json({ message: 'Reset token sent via email' });
  } catch (err) {
    console.log(err);
    res.status(500).json({ message: 'Server error' });
  }
});

// 2️⃣ 重置密碼
app.post('/reset-password', async (req, res) => {
  try {
    const { userId, token, newPassword } = req.body;
    const success = await resetPassword(userId, token, newPassword);
    res.json({ success });
  } catch (err) {
    console.log(err);
    res.status(500).json({ message: 'Server error' });
  }
});

// ---------------- 啟動伺服器 ----------------
app.listen(PORT, () => console.log(`Server running at http://localhost:${PORT}`));
