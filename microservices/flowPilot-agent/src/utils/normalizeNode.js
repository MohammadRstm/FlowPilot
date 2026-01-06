export function normalizeNodeName(node) {
  return node
    .toLowerCase()
    .replace(/^n8n-nodes-base\./, "")
    .replace(/[^a-z0-9]/g, "")
}
