import { NodeSpec } from "../types/NodeSpec";

export function generateNodeFile(spec: NodeSpec) {
  const resourceOptions = spec.resources.map(r => `
      {
        name: '${r.displayName}',
        value: '${r.name}',
      }`).join(",");

  const operationsBlocks = spec.resources.map(resource => {
    return `
{
  displayName: 'Operation',
  name: 'operation',
  type: 'options',
  displayOptions: { show: { resource: ['${resource.name}'] } },
  options: [
    ${resource.operations.map(op => `
    {
      name: '${op.displayName}',
      value: '${op.name}',
      action: '${op.action}',
      routing: {
        request: {
          method: '${op.method}',
          url: '${op.path}',
        },
      },
    }`).join(",")}
  ],
  default: '${resource.operations[0].name}',
},`;
  }).join("\n");

  return {
    path: `nodes/${spec.nodeName}/${spec.nodeName}.node.ts`,
    content: `
import { INodeType, INodeTypeDescription } from 'n8n-workflow';

export class ${spec.nodeName} implements INodeType {
  description: INodeTypeDescription = {
    displayName: '${spec.displayName}',
    name: '${spec.nodeName}',
    icon: 'file:${spec.icon || "node.svg"}',
    group: ['transform'],
    version: 1,
    description: '${spec.description}',
    defaults: { name: '${spec.displayName}' },
    inputs: ['main'],
    outputs: ['main'],
    credentials: [{ name: '${spec.credentials.name}', required: true }],
    requestDefaults: {
      baseURL: '${spec.baseUrl}',
      headers: { 'Content-Type': 'application/json' },
    },
    properties: [
      {
        displayName: 'Resource',
        name: 'resource',
        type: 'options',
        options: [${resourceOptions}],
        default: '${spec.resources[0].name}',
      },
      ${operationsBlocks}
    ],
  };
}
`
  };
}
