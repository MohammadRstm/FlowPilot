import fetch from "node-fetch"

export async function repairWorkflow(workflow, error) {
    const res = await fetch("http://127.0.0.1:8000/api/workflow/repair", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ workflow: JSON.parse(workflow), error })
    })

    return await res.json()
}
