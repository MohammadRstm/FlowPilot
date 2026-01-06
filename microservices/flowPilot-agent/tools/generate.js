import fetch from "node-fetch"

export async function generateWorkflow(context) {
    const res = await fetch("http://127.0.0.1:8000/api/workflow/generate", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ context })
    })

    return await res.json()
}
