import { Node, ArrayLiteralExpression } from "ts-morph";
import { resolveInitializer } from "./resolver";

/**
 * Resolves an array-like AST node into its concrete elements.
 *
 * Handles:
 * - identifiers pointing to arrays
 * - spread elements (...otherArray)
 * - nested indirections
 *
 * Always returns a flat list of Nodes.
 */
export function resolveArrayElements(node?: Node | null): Node[] {
  if (!node) return [];

  const resolved = resolveInitializer(node) ?? node;
  const result: Node[] = [];

  if (Node.isArrayLiteralExpression(resolved)) {
    for (const element of resolved.getElements()) {
      if (Node.isSpreadElement(element)) {
        const inner = resolveInitializer(element.getExpression());
        if (inner) {
          result.push(...resolveArrayElements(inner));
        }
      } else {
        result.push(element);
      }
    }
    return result;
  }

  // Identifier pointing to array defined elsewhere
  if (Node.isIdentifier(resolved) || Node.isPropertyAccessExpression(resolved)) {
    const symbol = resolved.getSymbol?.();
    const declaration = symbol?.getDeclarations()?.[0] as any;
    const init = declaration?.getInitializer?.();

    if (init) return resolveArrayElements(init);
  }

  return result;
}
