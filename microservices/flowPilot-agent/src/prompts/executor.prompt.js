
export function getExecutorPrompt(question){
    return `
    Build an n8n workflow for: ${question}

    You must:
    1) Search Qdrant for relevant nodes
    2) Get schemas
    3) Generate workflow
    4) Validate it
    5) If validation fails, repair and revalidate

    Return only the final valid workflow JSON.`;
}
