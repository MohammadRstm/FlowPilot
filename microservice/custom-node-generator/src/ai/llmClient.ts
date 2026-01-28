import { ChatOpenAI } from "@langchain/openai";
import { createAgent, providerStrategy } from "langchain";

import { NodeAnalysisSchema } from "./schemas/nodeAnalysis.schema";
import { NodeSpecSchema } from "../types/nodeSpec";



const baseModel = new ChatOpenAI({
  model: "gpt-5",
  temperature: 0,
});


export const analyzerAgent = createAgent({
  model: baseModel,
  tools: [],
  responseFormat: providerStrategy(NodeAnalysisSchema),
});


export const builderAgent = createAgent({
  model: baseModel,
  tools: [],
  responseFormat: providerStrategy(NodeSpecSchema),
});
