import { NodeSpec } from "../types/nodeSpec";

export function generateNodeJson(spec: NodeSpec) {
  return {
    path: `nodes/${spec.nodeName}/${spec.nodeName}.node.json`,
    content: JSON.stringify({
      node: `n8n-nodes-base.${spec.nodeName}`,
      nodeVersion: "1.0",
      codexVersion: "1.0",
      categories: [spec.category || "Miscellaneous"],
      resources: {}
    }, null, 2)
  };
}
