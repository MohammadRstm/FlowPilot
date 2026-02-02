import { Router } from "express";



const generateCustomNodeRoutes = Router();

generateCustomNodeRoutes.post("/" , generateCustomNodeController);

export default generateCustomNodeRoutes;