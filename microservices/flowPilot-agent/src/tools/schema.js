import fetch from "node-fetch"

const base_url = env.BASE_URL


export async function getNodeSchema(node) {
    const res = await fetch(`${base_url}/nodes/schema`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ node })
    })

    return await res.json()
}
