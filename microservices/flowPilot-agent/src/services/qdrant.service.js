import OpenAI from "openai"
import { env, EMBEDDING_MODEL } from "../config/env.js"
import { log } from "../utils/log.js"
import { analyzeQuestionService } from "./analysis/analyze.service.js"

const openai = new OpenAI({
  apiKey: env.OPENAI_KEY
})

const QDRANT_ENDPOINT = env.QDRANT_URL
const QDRANT_API_KEY = env.QDRANT_API_KEY

async function embedText(text) {
  const model = EMBEDDING_MODEL || "text-embedding-3-large"
  const response = await openai.embeddings.create({
    model,
    input: text
  })
  if (!response?.data?.[0]?.embedding) throw new Error("Embedding response missing vector")
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
    // Provide a clearer message for dimension mismatches
    if (text && text.includes("Vector dimension error")) {
      throw new Error(`Qdrant search failed for ${collection}: ${text}. This typically means your embedding model dimension (${EMBEDDING_MODEL}) does not match the vectors stored in the collection. Ensure you use the same embedding model that was used to index the collection.`)
    }
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

    await log("QDRANT STAGE:", { workflows, nodes, schemas })


    return { workflows, nodes, schemas }
}
