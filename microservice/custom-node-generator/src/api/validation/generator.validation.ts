import z from "zod";


export const generatorSchema = z.object({
    body: z.object({
        question: z.string().min(1, "Question is required"),
    }),
});