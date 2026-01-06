import { AgentExecutor, createToolCallingAgent } from "@langchain/classic/agents"
import { llm } from "../config/llm.js"
import { AGENT_SYSTEM_PROMPT } from "./system.prompt.js"
import { ChatPromptTemplate } from "@langchain/core/prompts"
import { RunnablePassthrough, RunnableSequence } from "@langchain/core/runnables"

function trimChatHistory(chat_history, maxMessages = 10) {
  if (!Array.isArray(chat_history)) return chat_history
  return chat_history.slice(-maxMessages)
}

function trimAgentScratchpad(agent_scratchpad, maxSteps = 6) {
  if (!Array.isArray(agent_scratchpad)) return agent_scratchpad
  return agent_scratchpad.slice(-maxSteps)
}

export async function createExecutor(tools) {
  const prompt = ChatPromptTemplate.fromMessages([
    ["system", AGENT_SYSTEM_PROMPT],
    ["placeholder", "{chat_history}"],
    ["human", "{input}"],
    ["placeholder", "{agent_scratchpad}"]
  ])

  const baseAgent = createToolCallingAgent({
    llm,
    tools,
    prompt,
    verbose: false
  })

  // Wrap agent with a small pre-processing runnable that trims history/scratchpad
  const wrappedAgent = RunnableSequence.from([
    RunnablePassthrough.assign({
      chat_history: (input) => trimChatHistory(input.chat_history, 8),
      agent_scratchpad: (input) => trimAgentScratchpad(input.agent_scratchpad, 4)
    }),
    baseAgent
  ])

  const executor = new AgentExecutor({
    agent: wrappedAgent,
    tools,
    verbose: true,
    maxIterations: 2
  })

  return executor
}
