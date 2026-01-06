import fetch from "node-fetch"

export async function searchQdrant(query) {
    const res = await fetch("http://127.0.0.1:8000/api/rag/search", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ query })
    })

    return await res.json()
}
