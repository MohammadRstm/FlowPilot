import fetch from "node-fetch"

const base_url = process.env.MAIN_SERVER_BASE_URL


export async function repairWorkflow(workflow, error) {
    const res = await fetch(`${base_url}/workflow/repair`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ workflow: JSON.parse(workflow), error })
    })

    return await res.json()
}
