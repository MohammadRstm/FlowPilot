import { ChatOpenAI } from "@langchain/openai"
import { AgentExecutor, createOpenAIFunctionsAgent } from "langchain/agents"
import { AGENT_SYSTEM_PROMPT } from "./system.prompt.js"
import { createTools } from "./tools.js"

export async function createAgentExecutor() {
  const llm = new ChatOpenAI({
    apiKey: process.env.OPENAI_KEY,
    temperature: 0
  })

  const tools = createTools()

  const agent = await createOpenAIFunctionsAgent({
    llm,
    tools,
    systemMessage: AGENT_SYSTEM_PROMPT
  })

  return new AgentExecutor({
    agent,
    tools,
    verbose: true,
    maxIterations: 10 // safety net
  })
}
