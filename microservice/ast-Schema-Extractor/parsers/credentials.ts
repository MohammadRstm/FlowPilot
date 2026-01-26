import { SourceFile, Node, SyntaxKind } from "ts-morph";
import { extractLiteral } from "../ast/literals";
import { getObjectProperty } from "../ast/objects";
import { resolveInitializer } from "../ast/resolver";

/**
 * Parses a credential file and extracts credential metadata.
 *
 * This is intentionally conservative:
 * - Only explicit object literals are parsed
 * - Dynamic values are ignored as n8n handles most of it at runtime
 */
export function parseCredential(sourceFile: SourceFile) {
  const exported = sourceFile.getVariableDeclarationOrThrow(
    sourceFile.getVariableDeclarations()[0]?.getName() ?? ""
  );

  const initializer = resolveInitializer(exported.getInitializer());
  if (!initializer || !Node.isObjectLiteralExpression(initializer)) {
    return null;
  }

  const name = extractLiteral(
    getObjectProperty(initializer, "name")
  );

  const displayName = extractLiteral(
    getObjectProperty(initializer, "displayName")
  );

  return {
    name,
    displayName,
    file: sourceFile.getFilePath(),
  };
}
