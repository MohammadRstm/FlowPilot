import { ChatOpenAI } from "@langchain/openai"
import { buildGeneratePrompt } from "./prompts.js"
import { log } from "../../utils/log.js"

const llm = new ChatOpenAI({
  apiKey: process.env.OPENAI_KEY,
  temperature: 0
})

export async function generateWorkflowService(context) {
  await log("Entered Generation with this context : " , context);
  
  const prompt = buildGeneratePrompt(context)

  const response = await llm.invoke(prompt)

  let json

  try {
    json = JSON.parse(response.content)
  } catch (err) {
    throw new Error("LLM did not return valid JSON")
  }

  await log("GENERATION STAGE:", json)

  return json
}
