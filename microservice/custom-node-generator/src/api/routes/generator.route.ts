import { Router } from "express";



const generateCustomNodeRoutes = Router();

generateCustomNodeRoutes.post("/" , generateCustomNodeRoutes);

export default generateCustomNodeRoutes;