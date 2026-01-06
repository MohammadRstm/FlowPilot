import fetch from "node-fetch"
import { normalizeNodeName } from "../utils/normalizeNode.js"
import { log } from "../utils/log.js"

const QDRANT_URL = process.env.QDRANT_CLUSTER_ENDPOINT
const QDRANT_KEY = process.env.QDRANT_API_KEY

export async function getNodeSchemaService(node) {
  const normalized = normalizeNodeName(node)

  const payload = {
    with_payload: true,
    limit: 50,
    filter: {
      should: [
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

  const schemas = data.points?.length
  ? data.points.map(p => p.payload)
  : [{ node: normalized, fields: [], warning: "schema not found, using fallback" }];


  await log("SCHEMA STAGE:", data.points.map(p => p.payload))

  return schemas

}
