import fetch from "node-fetch"

const base_url = process.env.MAIN_SERVER_BASE_URL

export async function generateWorkflow(context) {
    const res = await fetch(`${base_url}/workflow/generate`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ context })
    })

    return await res.json()
}
