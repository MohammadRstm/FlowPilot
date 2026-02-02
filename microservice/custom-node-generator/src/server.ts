import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import routes from './api/routes';

dotenv.config();

const app = express();
app.use(cors());
app.use(express.json());

app.use('/api', routes);


app.listen(process.env.PORT || 3001 , ()=>{
    console.log("Custom Node Generator Microservice is running on port " + (process.env.PORT || 3001));
});



