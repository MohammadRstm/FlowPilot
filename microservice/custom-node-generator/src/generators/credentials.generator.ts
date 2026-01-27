import { NodeSpec } from "../types/NodeSpec";

export function generateCredentialsFile(spec: NodeSpec) {
  return {
    path: `credentials/${spec.credentials.name}.credentials.ts`,
    content: `
    import {
    IAuthenticateGeneric,
    ICredentialType,
    INodeProperties,
    } from 'n8n-workflow';

    export class ${spec.credentials.name} implements ICredentialType {
    name = '${spec.credentials.name}';
    displayName = '${spec.credentials.displayName}';

    properties: INodeProperties[] = [
        {
        displayName: 'API Key',
        name: 'apiKey',
        type: 'string',
        default: '',
        },
    ];

    authenticate = {
        type: 'generic',
        properties: {
        ${spec.credentials.authLocation === "header"
            ? `headers: { '${spec.credentials.authHeaderName}': '={{$credentials.apiKey}}' }`
            : `qs: { '${spec.credentials.authQueryName}': '={{$credentials.apiKey}}' }`
        }
        },
    } as IAuthenticateGeneric;
    }
`
};
}
