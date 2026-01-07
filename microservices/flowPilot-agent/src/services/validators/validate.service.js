import { ChatOpenAI } from "@langchain/openai"
import { buildValidationPrompt } from "./prompts.js"
import { validateStructure } from "./validateStructure.validator.js"
import { validateAgainstSchemas } from "./schema.validator.js"
import { log } from "../../utils/log.js"

const llm = new ChatOpenAI({
  apiKey: process.env.OPENAI_KEY,
  temperature: 0
})

export async function validateWorkflowService({ workflow, analysis, schemas }) {
  const structureError = validateStructure(workflow)
  if (structureError) {
    log("INVALID STRUCTURE");
    return { ok: false, stage: "structure", error: structureError }
  }

  const schemaError = validateAgainstSchemas(workflow, schemas)
  if (schemaError) {
    log("INVALID SCHEMA");
    return { ok: false, stage: "schema", error: schemaError }
  }

  const prompt = buildValidationPrompt({ workflow, analysis })
  const res = await llm.invoke(prompt)

  const result = JSON.parse(res.content)

  if (!result.ok) {
    log("INVALID LOGIC");
    return { ok: false, stage: "semantic", error: result.error }
  }

  log("VALID WORKFLOW" , workflow);

  return { ok: true }
}
