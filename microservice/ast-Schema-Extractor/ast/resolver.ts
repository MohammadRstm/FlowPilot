import { Node, SyntaxKind } from "ts-morph";

/**
 * Resolves a node to its actual initializer if it is:
 * - an Identifier
 * - a PropertyAccessExpression
 *
 * This follows imports and exported constants across files.
 *
 * If the node is already a literal/object/array, it is returned as-is.
 *
 */
export function resolveInitializer(node?: Node | null): Node | undefined {
  if (!node) return undefined;

  // Identifiers or property accesses may point to imported constants
  if (Node.isIdentifier(node) || Node.isPropertyAccessExpression(node)) {
    const symbol = node.getSymbol?.();
    if (!symbol) return undefined;

    const declaration = symbol.getDeclarations()?.[0];
    if (!declaration) return undefined;

    // Variable declarations usually have initializers
    if (
      "getInitializer" in declaration &&
      typeof (declaration as any).getInitializer === "function"
    ) {
      return (declaration as any).getInitializer() ?? declaration;
    }

    // Fallback: return declaration itself
    return declaration;
  }

  // Already resolved
  return node;
}
