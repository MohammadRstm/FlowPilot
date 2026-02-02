import { Router } from "express";



const routes = Router();


routes.use("/health" , healthRoutes);
routes.use("/generateCustomNode" , generateCustomNodeRoutes);

export default routes;