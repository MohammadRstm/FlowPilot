import express from "express"
import cors from "cors"
import dotenv from "dotenv"

import { ChatOpenAI } from "langchain/chat_models/openai"
import { DynamicTool } from "langchain/tools"
import { searchQdrant } from "./tools/qdrant.js"

// frist agent
import { initializeAgentExecutorWithOptions } from "langchain/agents"


dotenv.config()

const app = express()
app.use(cors())
app.use(express.json())

const llm = new ChatOpenAI({
    openAIApiKey: process.env.OPENAI_KEY,
    temperature: 0
})

const qdrantTool = new DynamicTool({
    name: "search_qdrant",
    description: "Searches workflow, node and schema examples from Qdrant",
    func: async (query) => {
        return JSON.stringify(await searchQdrant(query))
    }
})


app.post("/build-workflow", async (req, res) => {
    const { question, user } = req.body

    const executor = await initializeAgentExecutorWithOptions(
        [qdrantTool],
        llm,
        {
            agentType: "openai-functions",
            verbose: true
        }
    )

    const result = await executor.run(
        `Find relevant n8n nodes and workflows for: ${question}`
    )

    res.json({ result })
})

app.listen(3001, () => {
    console.log("FlowPilot LangChain Agent running on port 3001")
})
