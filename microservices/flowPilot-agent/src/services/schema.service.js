import fetch from "node-fetch"
import { normalizeNodeName } from "../utils/normalizeNode.js"

const QDRANT_URL = process.env.QDRANT_CLUSTER_ENDPOINT
const QDRANT_KEY = process.env.QDRANT_API_KEY

export async function getNodeSchemaService(node) {
  const normalized = normalizeNodeName(node)

  const payload = {
    with_payload: true,
    limit: 50,
    filter: {
      must: [
        {
          key: "node_normalized",
          match: { value: normalized }
        }
      ]
    }
  }

  const res = await fetch(
    `${QDRANT_URL}/collections/n8n_node_schemas/points/scroll`,
    {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "api-key": QDRANT_KEY
      },
      body: JSON.stringify(payload)
    }
  )

  if (!res.ok) {
    throw new Error(`Failed to fetch schema for ${node}`)
  }

  const data = await res.json()

  if (!data.points?.length) {
    return { error: `No schema found for node: ${node}` }
  }

  return data.points.map(p => p.payload)
}
