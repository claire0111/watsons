import express from 'express';
import { MongoClient } from 'mongodb';
import cors from 'cors';

const app = express();
app.use(cors());
const port = 3001;

const client = new MongoClient('mongodb://localhost:27017');
await client.connect();
const db = client.db('watsons');
const reviewsCollection = db.collection('reviews');

app.get('/api/reviews/:productId', async (req, res) => {
  const product_id = parseInt(req.params.productId);
const reviews = await reviewsCollection.find({ product_id: product_id }).toArray();
  res.json(reviews);
});

app.listen(port, () => console.log(`API running at http://localhost:${port}`));
