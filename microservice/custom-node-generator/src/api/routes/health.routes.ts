import { Router } from "express";

const healthRouter = Router();

healthRouter.get("/" , (_ , res) =>{
    res.json({status:"alive"});
});


healthRouter.get("/ping" , (_ , res) =>{
    res.json({message:"pong"});
});

export default healthRouter;