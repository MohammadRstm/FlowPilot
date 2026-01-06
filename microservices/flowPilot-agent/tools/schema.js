import fetch from "node-fetch"

export async function getNodeSchema(node) {
    const res = await fetch("http://127.0.0.1:8000/api/nodes/schema", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ node })
    })

    return await res.json()
}
