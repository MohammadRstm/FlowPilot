import { env } from "../config/env"

const base_url = env.BASE_URL

export async function generateWorkflow(context) {
    const res = await fetch(`${base_url}/workflow/generate`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ context })
    })

    return await res.json()
}
