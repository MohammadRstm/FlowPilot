import { Router } from "express";
import healthRouter from "./health.routes";
import generateCustomNodeRoutes from "./generator.route";


const routes = Router();

routes.use("/health" , healthRouter);
routes.use("/generateCustomNode" , generateCustomNodeRoutes);

export default routes;