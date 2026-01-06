import express from "express"
import cors from "cors"
import dotenv from "dotenv"

import { ChatOpenAI } from "langchain/chat_models/openai"
import { DynamicTool } from "langchain/tools"
import { searchQdrant } from "./tools/qdrant.js"
import { getNodeSchema } from "./tools/schema.js"
import { generateWorkflow } from "./tools/generate.js"
import { validateWorkflow } from "./tools/validate.js"
import { repairWorkflow } from "./tools/repair.js"

// AI agent
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

// VALIDATION TOOL
const validateTool = new DynamicTool({
    name: "validate_workflow",
    description: "Validates an n8n workflow and returns errors or ok",
    func: async (workflow) => {
        return JSON.stringify(await validateWorkflow(workflow))
    }
})

// REPAIR TOOL
const repairTool = new DynamicTool({
    name: "repair_workflow",
    description: "Repairs a broken workflow based on validation error",
    func: async (input) => {
        const { workflow, error } = JSON.parse(input)
        return JSON.stringify(await repairWorkflow(workflow, error))
    }
})

// ENDPOINT
app.post("/build-workflow", async (req, res) => {
    const { question, user } = req.body

    const executor = await initializeAgentExecutorWithOptions(
        [qdrantTool , schemaTool ,  generateTool , validateTool , repairTool],
        llm,
        {
            agentType: "openai-functions",
            verbose: true
        }
    )

    const result = await executor.run(
        `Build an n8n workflow for: ${question}
        You must:
        1) Search Qdrant for relevant nodes
        2) Get schemas
        3) Generate workflow
        4) Validate it
        5) If validation fails, repair and revalidate
        Return only the final valid workflow JSON`
    )

    res.json({ result })
})

// LISTENER
app.listen(3001, () => {
    console.log("FlowPilot LangChain Agent running on port 3001")
})
