import { createApp } from "./app.js"
import { env } from "./config/env.js"

const app = createApp()

app.listen(env.PORT, () => {
  console.log(`FlowPilot LangChain Agent running on port ${env.PORT}`)
})