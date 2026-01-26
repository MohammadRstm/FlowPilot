import { Project, SourceFile } from "ts-morph";
import { parseNode } from "../parsers/nodes";
import { parseCredential } from "../parsers/credentials";
import { pathStartsWith } from "../ast/paths";
import { NODES_DIR, CREDS_DIR } from "../config/paths";

/**
 * High-level orchestration service.
 *
 * This is the ONLY place that:
 * - iterates source files
 * - decides what kind of parser to use
 * - assembles final schemas
 */
export function extractSchemas(project: Project) {
  const nodes = [];
  const credentials = [];

  for (const sourceFile of project.getSourceFiles()) {
    const filePath = sourceFile.getFilePath();

    if (pathStartsWith(filePath, NODES_DIR)) {
      const node = parseNode(sourceFile);
      if (node) nodes.push(node);
    }

    if (pathStartsWith(filePath, CREDS_DIR)) {
      const cred = parseCredential(sourceFile);
      if (cred) credentials.push(cred);
    }
  }

  return nodes.map(node => ({
    ...node,
    credentials: credentials.filter(
      c => node.name && c.name?.toLowerCase().includes(node.name.toLowerCase())
    ),
  }));
}
