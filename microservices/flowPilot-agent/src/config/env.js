import "dotenv/config"

export const env = {
  PORT: process.env.PORT || 3001,
  OPENAI_KEY: process.env.OPENAI_KEY
}

if (!env.OPENAI_KEY) {
  throw new Error("Missing OPENAI_KEY in environment")
}
