import { DECLARATIVE_BUILD_SYSTEM_PROMPT } from "../../ai/prompts/buildDeclarativeNode.prompt";
import { PROGRAMMATIC_BUILD_SYSTEM_PROMPT } from "../../ai/prompts/buildProgrammaticNode.prompt";
import { ANALYZE_SYSTEM_PROMPT } from "../../ai/prompts/nodeAnalysis.prompt";


export async function planAndGenerateNode(userRequest: string) {
  // STEP 1 — Analyze
  const analysis = await analyzerAgent.invoke({
    messages: [
      { role: "system", content: ANALYZE_SYSTEM_PROMPT },
      { role: "user", content: userRequest },
    ],
  });

  const nodeType = analysis.structuredResponse.nodeType;

  // STEP 2 — Build
  const systemPrompt =
    nodeType === "declarative"
      ? DECLARATIVE_BUILD_SYSTEM_PROMPT
      : PROGRAMMATIC_BUILD_SYSTEM_PROMPT;

  const nodeSpecResult = await builderAgent.invoke({
    messages: [
      { role: "system", content: systemPrompt },
      { role: "user", content: userRequest },
    ],
  });

  return nodeSpecResult.structuredResponse;
}
