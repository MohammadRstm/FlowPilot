import { ChatOpenAI } from "@langchain/openai"
import { buildGeneratePrompt } from "../prompts/generate.prompt.js"

const llm = new ChatOpenAI({
  apiKey: process.env.OPENAI_KEY,
  temperature: 0
})

export async function generateWorkflowService(context) {
  const prompt = buildGeneratePrompt(context)

  const response = await llm.invoke(prompt)

  let json

  try {
    json = JSON.parse(response.content)
  } catch (err) {
    throw new Error("LLM did not return valid JSON")
  }

  return json
}
