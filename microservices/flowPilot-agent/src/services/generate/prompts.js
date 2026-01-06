export function buildGeneratePrompt({
  analysis,
  examples
}) {
  return `
    You are an expert n8n workflow generator.

    Your task:
    - Generate a COMPLETE n8n workflow JSON
    - Use ONLY valid n8n nodes
    - The workflow MUST be executable
    - The workflow MUST start with the correct trigger

    Hard rules:
    - Output ONLY raw JSON
    - NO markdown
    - NO explanations
    - NO comments
    - NO trailing text

    Workflow intent:
    ${analysis.intent}

    Trigger:
    ${analysis.trigger}

    Required nodes:
    ${analysis.nodes.join(", ")}

    Relevant workflow examples:
    ${JSON.stringify(examples.workflows, null, 2)}

    Relevant node examples:
    ${JSON.stringify(examples.nodes, null, 2)}

    Node schemas:
    ${JSON.stringify(examples.schemas, null, 2)}

    YOU MUST OUTPUT A VALID N8N WORKFLOW JSON
    `
}
