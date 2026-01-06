import { ChatOpenAI } from "@langchain/openai"
import { AnalysisSchema } from "./analysis.schema.js"
import { buildAnalysisPrompt } from "./analysis.prompt.js"
import { log } from "../../utils/log.js"

const llm = new ChatOpenAI({
  apiKey: process.env.OPENAI_KEY,
  temperature: 0
})

export async function analyzeQuestionService(question) {
  const prompt = buildAnalysisPrompt(question)

  const response = await llm.invoke([
    { role: "system", content: prompt.system },
    { role: "user", content: prompt.user }
  ])

  let parsed
  try {
    parsed = JSON.parse(response.content)
  } catch {
    throw new Error("Analysis LLM returned invalid JSON")
  }

  const validated = AnalysisSchema.parse(parsed)

  await log("ANALYSIS STAGE:", validated)

  return validated
}
