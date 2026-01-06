import { AgentExecutor , createToolCallingAgent  } from "@langchain/classic/agents"
import { llm } from "../config/llm.js"

export async function createExecutor(tools) {

  const agent = createToolCallingAgent({
    llm,
    tools,
    verbose: true
  })

  const executor = new AgentExecutor({
    agent,
    tools,
    verbose: true
  })

  return executor
}
