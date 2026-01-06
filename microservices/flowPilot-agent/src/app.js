import express from "express"
import cors from "cors"

import workflowRoutes from "./routes/workflow.routes.js"

export function createApp() {
  const app = express()

  app.use(cors())
  app.use(express.json())

  app.use("/", workflowRoutes)

  return app
}
