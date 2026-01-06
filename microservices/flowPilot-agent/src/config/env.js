import "dotenv/config"

export const env = {
  PORT: process.env.PORT || 3001,
  OPENAI_KEY: process.env.OPENAI_KEY,
  BASE_URL : process.env.MAIN_SERVER_BASE_URL
}

if (!env.OPENAI_KEY) {
  throw new Error("Missing OPENAI_KEY in environment")
}
