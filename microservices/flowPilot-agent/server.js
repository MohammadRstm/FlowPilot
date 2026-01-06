import express from "express"
import cors from "cors"
import dotenv from "dotenv"

dotenv.config()

const app = express()
app.use(cors())
app.use(express.json())

app.post("/build-workflow", async (req, res) => {
    const { question, user } = req.body

    console.log("Question:", question)

    // Temporary stub — no AI yet
    res.json({
        status: "ok",
        message: "LangChain agent is alive",
        received: question
    })
})

app.listen(3001, () => {
    console.log("FlowPilot LangChain Agent running on port 3001")
})
