import express from 'express';
import { createClient } from 'redis';
import cors from 'cors';

const app = express();
const port = 3000;

app.use(cors());

const client = createClient({
  url: 'redis://127.0.0.1:6379'
});

await client.connect();

client.on('connect', () => console.log('Connected to Redis'));
client.on('error', (err) => console.error('Redis error', err));

app.get('/api/stock/:productId', async (req, res) => {
  const productId = req.params.productId;
  try {
    const stock = await client.get(`product:${productId}:stock`);
    if (stock === null) {
      return res.status(404).json({ message: 'Stock not found' });
    }
    res.json({ productId, stock: Number(stock) });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.listen(port, () => {
  console.log(`Server running at http://localhost:${port}`);
});
