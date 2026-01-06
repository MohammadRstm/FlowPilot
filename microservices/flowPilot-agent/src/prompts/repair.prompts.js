export function buildRepairPrompt({ workflow, analysis, error }) {
  return `
    You are an n8n workflow repair engine.

    User intent:
    ${analysis.intent}

    Validation error:
    ${error.stage}: ${error.message}

    Current workflow:
    ${JSON.stringify(workflow, null, 2)}

    Rules:
    - Fix ONLY what is necessary
    - Do NOT introduce new services unless required
    - Preserve working parts
    - Output ONLY valid JSON

    Return the repaired workflow JSON.
    `
}
