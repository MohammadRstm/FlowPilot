import express from 'express';
import cors from 'cors';



const app = express();
app.use(cors());
app.use(express.json());

app.get("/helth" , (req , res) =>{
    res.json({status:"alive"});
});


app.listen(PORT , ()=>{
    console.log("Custom Node Generator Microservice is running on port " + PORT);
});



