import express from 'express';
import { MongoClient } from 'mongodb';
import cors from 'cors';

const app = express();
app.use(cors());
app.use(express.json()); // 可以接收 JSON POST

const client = new MongoClient('mongodb://localhost:27017');

// async function testMongo() {
//   try {
//     await client.connect();
//     console.log('✅ MongoDB connected');
//     const db = client.db('watsons');
//     const reviews = await db.collection('reviews').find().toArray();
//     console.log('Reviews count:', reviews.length);
//   } catch (err) {
//     console.error('❌ MongoDB connection error:', err);
//   } finally {
//     await client.close();
//     console.log("1");
//   }
// }

// testMongo();

await client.connect();
console.log('MongoDB connected');
const db = client.db('watsons');
const reviewsCollection = db.collection('reviews');

app.get('/reviews/:productId', async (req, res) => {
  try {
    const product_id = parseInt(req.params.productId);
    const reviews = await reviewsCollection.find({ product_id }).sort({ created_at: -1 }).toArray();
    res.json({ success: true, reviews });
  } catch (err) {
    console.error(err);
    res.status(500).json({ success: false, msg: err.message });
  }
});

app.post('/reviews', async (req, res) => {
  const { product_id, user_id, rating, comment } = req.body;
  if (!user_id || !comment) return res.json({ success: false, msg: '缺少參數' });

  const review = {
    product_id,
    user_id,
    rating: parseInt(rating),
    comment,
    created_at: new Date().toISOString()
  };
  await reviewsCollection.insertOne(review);
  res.json({ success: true, review });
});

app.listen(3001, () => console.log('Reviews API running on http://localhost:3001'));
