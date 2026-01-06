import { initializeAgentExecutorWithOptions } from "langchain/agents"
import { llm } from "../config/llm.js"
import { createTools } from "./tools.js"

export async function createExecutor() {
  return initializeAgentExecutorWithOptions(
    createTools(),
    llm,
    {
      agentType: "openai-functions",
      verbose: true
    }
  )
}
