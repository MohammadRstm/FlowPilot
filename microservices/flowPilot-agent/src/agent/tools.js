import { DynamicTool } from "@langchain/core/tools"

import
import { searchQdrantService } from "../services/qdrant.service.js"
import { getNodeSchemaService } from "../services/schema.service.js"
import { generateWorkflowService } from "../services/generate.service.js"
import { validateWorkflowService } from "../services/validate.service.js"
import { repairWorkflowService } from "../services/repair.service.js"

export function createTools() {
  return [

    new DynamicTool({
    name: "analyze_question",
    description: "Analyze user's question into intent, trigger, nodes, and embedding query",
    func: async (question) =>
        JSON.stringify(await analyzeQuestionService(question))
    }),

    new DynamicTool({
      name: "search_qdrant",
      description: "Search workflow, node and schema examples from Qdrant",
      func: async (query) =>
       JSON.stringify(await searchQdrantService(query))
    }),

    new DynamicTool({
      name: "get_node_schema",
      description: "Return the schema of an n8n node",
      func: async (node) =>
        JSON.stringify(await getNodeSchemaService(node))
    }),

    new DynamicTool({
      name: "generate_workflow",
      description: "Generate an n8n workflow JSON from context",
      func: async (context) =>
        JSON.stringify(await generateWorkflowService(context))
    }),

    new DynamicTool({
      name: "validate_workflow",
      description: "Validate an n8n workflow",
      func: async (workflow) =>
        JSON.stringify(await validateWorkflowService(workflow))
    }),

    new DynamicTool({
      name: "repair_workflow",
      description: "Repair a workflow using validation errors",
      func: async (input) => {
        const { workflow, error } = JSON.parse(input)
        return JSON.stringify(
          await repairWorkflowService(workflow, error)
        )
      }
    })
  ]
}
