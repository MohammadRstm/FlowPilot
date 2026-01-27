import { createAgent, providerStrategy } from "langchain";
import { NodeSpecSchema } from "../schemas/nodeSpec.schema";
import { z } from "zod";

export type NodeSpec = z.infer<typeof NodeSpecSchema>;

const agent = createAgent({
  model: "gpt-5",
  tools: [],
  responseFormat: providerStrategy(NodeSpecSchema),
});

export async function generateNodeSpec(userRequest: string): Promise<NodeSpec> {
  const result = await agent.invoke({
    messages: [
      {
        role: "system",
        content:`You are an expert n8n node architect.
        Your job is to design declarative-style n8n nodes that integrate with REST APIs.

        You must ONLY return data that matches the provided NodeSpec schema.

        Rules:
        - nodeName must be PascalCase and contain no spaces
        - displayName is human friendly
        - baseUrl must be the API base URL
        - Each resource represents an API entity (e.g. "contact", "order", "payment")
        - Each operation must represent a single REST endpoint
        - Use proper HTTP methods (GET, POST, PUT, DELETE)
        - operation.path must be the endpoint path starting with /

        Do not include explanations.
        Do not include markdown.
        Only return structured data.`.trim(),
      },
      {
        role: "user",
        content: userRequest,
      },
    ],
  });

  if (!result.structuredResponse) {
    throw new Error("AI did not return a structured NodeSpec.");
  }

  return result.structuredResponse;
}
