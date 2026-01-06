import OpenAI from "openai"
import { env } from "../config/env"
import { analyzeQuestionService } from "./analysis/analyze.service"

const openai = new OpenAI({
  apiKey: env.OPENAI_KEY
})

const QDRANT_ENDPOINT = env.QDRANT_URL
const QDRANT_API_KEY = env.QDRANT_API_KEY

async function embedText(text) {
  const response = await openai.embeddings.create({
    model: "text-embedding-3-small",
    input: text
  })
  return response.data[0].embedding
}

function buildNodeEmbeddingQuery({ trigger, nodes }) {
  const parts = []
  if (trigger) parts.push(`n8n trigger ${trigger}`)
  if (nodes?.length) parts.push(...nodes.map(n => `n8n node ${n}`))
  return parts.join(" ")
}

async function queryQdrant(collection, denseVector, sparseVector, limit = 50) {
  const payload = {
    limit,
    with_payload: true,
    vector: {
      name: "dense-vector",
      vector: denseVector
    },
    sparse_vector: {
      name: "text-sparse",
      vector: sparseVector
    },
    score_threshold: 0.1
  }

  const res = await fetch(`${QDRANT_ENDPOINT}/collections/${collection}/points/search`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "api-key": QDRANT_API_KEY
    },
    body: JSON.stringify(payload)
  })

  if (!res.ok) {
    const text = await res.text()
    throw new Error(`Qdrant search failed for ${collection}: ${text}`)
  }

  const data = await res.json()
  return data.result
}

export async function searchQdrantService(question) {
    const analysis = await analyzeQuestionService(question);
    const workflowDense = await embedText(analysis.embedding_query)
    const nodeDense = await embedText(buildNodeEmbeddingQuery(analysis))

    const workflowSparse = await embedText(analysis.intent)
    const nodeSparse = await embedText(
        `${analysis.intent} ${(analysis.nodes || []).join(" ")} ${analysis.trigger || ""}`
    )

    const workflows = await queryQdrant("n8n_workflows", workflowDense, workflowSparse, 50)
    const nodes = await queryQdrant("n8n_catalog", nodeDense, nodeSparse, 30)
    const schemas = await queryQdrant("n8n_node_schemas", nodeDense, nodeSparse, 50)

    return { workflows, nodes, schemas }
}
