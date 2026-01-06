import express from "express"
import { createExecutor } from "../agent/executor.js"

const router = express.Router()

router.post("/build-workflow", async (req, res) => {
  try {
    const { question } = req.body

    if (!question) {
      return res.status(400).json({ error: "Missing question" })
    }

    const executor = await createExecutor()

    const prompt = `
    Build an n8n workflow for: ${question}

    You must:
    1) Search Qdrant for relevant nodes
    2) Get schemas
    3) Generate workflow
    4) Validate it
    5) If validation fails, repair and revalidate

    Return only the final valid workflow JSON.`

    const result = await executor.run(prompt)

    res.json({ result })
  } catch (err) {
    console.error("Workflow build failed:", err)
    res.status(500).json({ error: "Workflow generation failed" })
  }
})

export default router
