import { ChatOpenAI } from "@langchain/openai";
import { createAgent, providerStrategy } from "langchain";
import { z } from "zod";

import { NodeAnalysisSchema } from "./schemas/nodeAnalysis.schema";
import { NodeSpecSchema } from "./schemas/nodeSpec.schema";



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
