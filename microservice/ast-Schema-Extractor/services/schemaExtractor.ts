import { Project } from "ts-morph";
import { parseNode } from "../parsers/nodes";
import { parseCredential } from "../parsers/credentials";
import { pathStartsWith } from "../ast/paths";
import { NODES_DIR, CREDS_DIR } from "../config/paths";
import { generateNodeSummary } from "../domain/summary";
import { ConnectionTypeCache } from "../domain/cache/connectionTypes";
import { createNodeSchema } from "../domain/schema";

/**
 * High-level orchestration service.
 *
 * Responsibilities:
 * - iterate source files
 * - delegate parsing
 * - assemble final domain schemas
 *
 * Does NOT:
 * - inspect AST directly
 * - infer syntax
 * - perform IO
 */
export function extractSchemas(project: Project) {
  const parsedNodes = [];
  const parsedCredentials = [];

  const connectionTypes = new ConnectionTypeCache(project);
  connectionTypes.load();

  // parse phase
  for (const sourceFile of project.getSourceFiles()) {
    const filePath = sourceFile.getFilePath();

    if (pathStartsWith(filePath, NODES_DIR)) {
      const node = parseNode(sourceFile);
      if (node) parsedNodes.push(node);
    }

    if (pathStartsWith(filePath, CREDS_DIR)) {
      const credential = parseCredential(sourceFile);
      if (credential) parsedCredentials.push(credential);
    }
  }

  // assembly phase
  return parsedNodes.map(node =>
    createNodeSchema({
      name: node.name,
      displayName: node.displayName,
      description: node.description,
      file: node.file,
      fields: node.fields,

      credentials: parsedCredentials.filter(
        c =>
          node.name &&
          c.name?.toLowerCase().includes(node.name.toLowerCase())
      ),

      inputs: (node.inputs ?? []).map(name => ({
        name,
        friendly: connectionTypes.resolve(name),
        type: "any",
      })),

      outputs: (node.outputs ?? []).map(name => ({
        name,
        friendly: connectionTypes.resolve(name),
        type: "any",
      })),

      summary: generateNodeSummary(node),
    })
  );
}
