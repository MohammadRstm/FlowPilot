import { DynamicTool } from "@langchain/core/tools"


import { searchQdrantService } from "../services/qdrant.service.js"
import { getNodeSchemaService } from "../services/schema.service.js"
import { generateWorkflowService } from "../services/generate/generate.service.js"
import { validateWorkflowService } from "../services/validators/validate.service.js"
import { repairWorkflowService } from "../services/repair.service.js"

export function createTools() {
  return [
    new DynamicTool({
      name: "search_qdrant",
      description: "Search workflow, node and schema examples from Qdrant.This tool MUST only be called at its correct step in the pipeline.",
      func: async (query) =>
       JSON.stringify(await searchQdrantService(query))
    }),

    new DynamicTool({
    name: "get_node_schema",
    description:
        "Return the schema of an n8n node including inputs, outputs, fields and credentials.This tool MUST only be called at its correct step in the pipeline.",
    func: async (node) =>
        JSON.stringify(await getNodeSchemaService(node))
    }),
    
    new DynamicTool({
    name: "generate_workflow",
    description:
        "Generate a valid n8n workflow JSON using intent, examples, and schemas.This tool MUST only be called at its correct step in the pipeline.",
    func: async (context) =>
        JSON.stringify(await generateWorkflowService(JSON.parse(context)))
    }),

    new DynamicTool({
      name: "validate_workflow",
      description: "Validate an n8n workflow.This tool MUST only be called at its correct step in the pipeline.",
      func: async (workflow) =>
        JSON.stringify(await validateWorkflowService(workflow))
    }),

    new DynamicTool({
      name: "repair_workflow",
      description: "Repair a workflow using validation errors.This tool MUST only be called at its correct step in the pipeline.",
      func: async (input) => {
        const { workflow, error } = JSON.parse(input)
        return JSON.stringify(
          await repairWorkflowService(workflow, error)
        )
      }
    })
  ]
}
