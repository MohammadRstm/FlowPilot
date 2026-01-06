import fetch from "node-fetch"

const base_url = env.BASE_URL


export async function searchQdrant(query) {
    const res = await fetch(`${base_url}/rag/search`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ query })
    })

    return await res.json()
}
