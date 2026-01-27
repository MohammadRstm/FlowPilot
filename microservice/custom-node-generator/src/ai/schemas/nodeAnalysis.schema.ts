import { z } from "zod";

export const NodeAnalysisSchema = z.object({
  nodeType: z.enum(["declarative", "programmatic"]),
  reasoning: z.string(),
});

export type NodeAnalysis = z.infer<typeof NodeAnalysisSchema>;
