import express from "express"
import { createExecutor } from "../agent/executor.js"
import { getExecutorPrompt } from "../prompts/executor.prompt.js"
import { createTools } from "../agent/tools.js"

const router = express.Router()

router.post("/build-workflow", async (req, res) => {
  try {
    const { question } = req.body

    if (!question) {
      return res.status(400).json({ error: "Missing question" })
    }
    const tools = createTools()
    const executor = await createExecutor(tools)

    const prompt = getExecutorPrompt(question);
    const result = await executor.call({ input: prompt })

    res.json({ result })
  } catch (err) {
    console.error("Workflow build failed:", err)
    res.status(500).json({ error: "Workflow generation failed" })
  }
})

export default router
