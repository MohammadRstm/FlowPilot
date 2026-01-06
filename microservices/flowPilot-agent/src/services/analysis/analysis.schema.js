import { z } from "zod"

export const AnalysisSchema = z.object({
  intent: z.string().min(10),
  trigger: z.string().min(1),
  nodes: z.array(z.string()).min(1),
  min_nodes: z.number().int().positive(),
  category: z.string().min(1),
  embedding_query: z.string().min(20)
})
