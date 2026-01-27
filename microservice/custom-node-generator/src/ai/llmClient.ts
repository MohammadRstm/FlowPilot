import { providerStrategy } from "langchain";
import { NodeSpecSchema } from "./schemas/nodeSpec.schema";

const agent = createAgent({
  model: "gpt-5",
  tools: [],
  responseFormat: providerStrategy(NodeSpecSchema),
});
