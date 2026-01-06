export function validateAgainstSchemas(workflow, schemas) {
  for (const node of workflow.nodes) {
    const schema = schemas.find(
      s => s.node === node.type.replace("n8n-nodes-base.", "")
    )

    if (!schema) {
      return `Missing schema for node ${node.type}`
    }

    // Example: required fields
    for (const field of schema.fields ?? []) {
      if (field.required && node.parameters?.[field.name] === undefined) {
        return `Missing required field '${field.name}' on ${node.type}`
      }
    }
  }

  return null
}
