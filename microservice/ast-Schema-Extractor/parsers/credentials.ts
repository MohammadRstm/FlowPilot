import { SourceFile, Node } from "ts-morph";
import { extractLiteral } from "../ast/literals";
import { getObjectProperty } from "../ast/objects";
import { resolveInitializer } from "../ast/resolver";
import { createCredentialSchema } from "../domain/schema";

/**
 * Parses a credential file and extracts credential metadata.
 *
 * Intentionally conservative:
 * - Only explicit object literals
 * - Dynamic runtime values are ignored
 */
export function parseCredential(sourceFile: SourceFile) {
  const declarations = sourceFile.getVariableDeclarations();
  if (!declarations.length) return null;

  const exported = declarations[0];
  const initializer = resolveInitializer(exported.getInitializer());

  if (!initializer || !Node.isObjectLiteralExpression(initializer)) {
    return null;
  }

  return createCredentialSchema({
    name: extractLiteral(getObjectProperty(initializer, "name")),
    displayName: extractLiteral(
      getObjectProperty(initializer, "displayName")
    ),
    file: sourceFile.getFilePath(),
  });
}
