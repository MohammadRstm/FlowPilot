import { Request, Response } from "express";

export const validate = (schema: any) =>
(req : Request , res : Response , next : Function) => {
    try{
        const validatedData =  schema.parse({
            body : req.body,
            query : req.query,
            params : req.params
        });

        req.body = validatedData.body;
        req.query = validatedData.query;
        req.params = validatedData.params;

        next();
    }catch(error : any){
        return res.status(400).json({
            message: "Validation failed",
            errors: error.errors,
        });
    }
}