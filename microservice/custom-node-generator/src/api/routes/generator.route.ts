import { Router } from "express";
import { validate } from "../middlwares/validate.middlware";
import { generatorSchema } from "../validation/generator.validation";

const generateCustomNodeRoutes = Router();

generateCustomNodeRoutes.post("/" , validate(generatorSchema) ,  generateCustomNodeRoutes);

export default generateCustomNodeRoutes;