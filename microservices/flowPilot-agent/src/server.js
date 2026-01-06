import { createApp } from "./app.js"
import { env } from "./config/env.js"
import { log } from "./utils/log.js"

const app = createApp()

app.listen(env.PORT, () => {
  console.log(`FlowPilot LangChain Agent running on port ${env.PORT}`)
})