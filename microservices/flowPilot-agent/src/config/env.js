import "dotenv/config"

export const env = {
  PORT: process.env.PORT || 3001,
  OPENAI_KEY: process.env.OPENAI_KEY,
  BASE_URL : process.env.MAIN_SERVER_BASE_URL,
  QDRANT_URL: process.env.QDRANT_CLUSTER_ENDPOINT,
  QDRANT_API_KEY : process.env.QDRANT_API_KEY
}

if (!env.OPENAI_KEY) {
  throw new Error("Missing OPENAI_KEY in environment")
}

export const EMBEDDING_MODEL = process.env.EMBEDDING_MODEL || "text-embedding-3-large"
