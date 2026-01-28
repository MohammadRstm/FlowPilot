import { NodeSpec } from "../types/nodeSpec";

export function generatePackageJson(spec: NodeSpec) {
  return {
    path: "package.json",
    content: JSON.stringify({
      name: `n8n-nodes-${spec.nodeName.toLowerCase()}`,
      version: "0.1.0",
      description: spec.description,
      keywords: ["n8n-community-node-package"],
      license: "MIT",
      main: "index.js",
      files: ["dist"],
      n8n: {
        n8nNodesApiVersion: 1,
        credentials: [`dist/credentials/${spec.credentials.name}.credentials.js`],
        nodes: [`dist/nodes/${spec.nodeName}/${spec.nodeName}.node.js`]
      }
    }, null, 2)
  };
}
