import fetch from "node-fetch"

export async function validateWorkflow(workflow) {
    const res = await fetch("http://127.0.0.1:8000/api/workflow/validate", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ workflow: JSON.parse(workflow) })
    })

    return await res.json()
}
