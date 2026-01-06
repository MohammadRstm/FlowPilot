import { ChatOpenAI } from "@langchain/openai"
import { env } from "./env.js"

export const llm = new ChatOpenAI({
  openAIApiKey: env.OPENAI_KEY,
  temperature: 0
})
