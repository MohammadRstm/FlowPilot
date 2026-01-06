import express from "express"
import cors from "cors"
import dotenv from "dotenv"

import { ChatOpenAI } from "langchain/chat_models/openai"
import { DynamicTool } from "langchain/tools"
import { searchQdrant } from "./tools/qdrant.js"
import { getNodeSchema } from "./tools/schema.js"
import { generateWorkflow } from "./tools/generate.js"


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

// QDRANT TOOL
const qdrantTool = new DynamicTool({
    name: "search_qdrant",
    description: "Searches workflow, node and schema examples from Qdrant",
    func: async (query) => {
        return JSON.stringify(await searchQdrant(query))
    }
})

// SCHEMA TOOL
const schemaTool = new DynamicTool({
    name: "get_node_schema",
    description: "Returns the schema of an n8n node including inputs, outputs, fields and credentials",
    func: async (node) => {
        return JSON.stringify(await getNodeSchema(node))
    }
})

// GENERATION TOOL
const generateTool = new DynamicTool({
    name: "generate_workflow",
    description: "Generates an n8n workflow JSON from context including nodes, schemas and intent",
    func: async (context) => {
        return JSON.stringify(await generateWorkflow(context))
    }
})




app.post("/build-workflow", async (req, res) => {
    const { question, user } = req.body

    const executor = await initializeAgentExecutorWithOptions(
        [qdrantTool , schemaTool ,  generateTool],
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
