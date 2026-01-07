import { log } from "../../utils/log.js"

export function validateStructure(workflow) {
    log("Attempting structure validation" , workflow);
    if (!workflow || typeof workflow !== "object") {
        return "Workflow is not an object"
    }

    if (!Array.isArray(workflow.nodes)) {
        return "Workflow nodes must be an array"
    }

    if (!workflow.connections || typeof workflow.connections !== "object") {
        return "Workflow connections missing or invalid"
    }

    return null
}
