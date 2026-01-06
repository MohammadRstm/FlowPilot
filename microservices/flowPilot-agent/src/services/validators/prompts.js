export function buildValidationPrompt({ workflow, analysis }) {
  return `
    You are an n8n workflow validation engine.

    User intent:
    ${analysis.intent}

    Workflow JSON:
    ${JSON.stringify(workflow, null, 2)}

    Validate the following:
    1. Does the workflow fully satisfy the intent?
    2. Is the data flow logically correct?
    3. Are all branches handled?

    Return ONLY valid JSON:

    {
    "ok": boolean,
    "error": string | null
    }
    `
}
