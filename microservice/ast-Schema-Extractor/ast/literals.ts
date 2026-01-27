import { Node, SyntaxKind } from "ts-morph";

/**
 * Extracts a primitive value from an AST node.
 *
 * Supported:
 * - string
 * - number
 * - boolean
 *
 * Anything else falls back to text representation.
 */
export function extractLiteral(node?: Node | null): any {
  if (!node) return undefined;

  if (node.getKind() === SyntaxKind.StringLiteral) {
    const text = node.getText();
    return text.slice(1, -1); // remove quotes
  }

  if (node.getKind() === SyntaxKind.NumericLiteral) {
    return Number(node.getText());
  }

  if (node.getKind() === SyntaxKind.TrueKeyword) return true;
  if (node.getKind() === SyntaxKind.FalseKeyword) return false;

  // Fallback: return sanitized text
  return node.getText
    ? node.getText().replace(/['"`]/g, "")
    : undefined;
}
