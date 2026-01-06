import { searchQdrant } from "../tools/qdrant.js"

export async function searchQdrantService(query) {
  return searchQdrant(query)
}
