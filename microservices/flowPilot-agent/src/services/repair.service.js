import { ChatOpenAI } from "@langchain/openai"
import { buildRepairPrompt } from "../prompts/repair.prompts.js"
import { log } from "../utils/log.js"

const llm = new ChatOpenAI({
  apiKey: process.env.OPENAI_KEY,
  temperature: 0
})

export async function repairWorkflowService(workflow, error, analysis) {
  const prompt = buildRepairPrompt({ workflow, error, analysis })

  const res = await llm.invoke(prompt)

  const parsed = JSON.parse(res.content)
  await log("REPAIR STAGE:", parsed)

  return parsed
}
