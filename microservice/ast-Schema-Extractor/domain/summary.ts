import { NodeSchema } from "./schema";

/**
 * Generates a short, human-readable summary of a node.
 *
 * This is deterministic and safe.
 * Can later be replaced with an LLM-powered implementation.Maybe costly though.
 */
export function generateNodeSummary(node: NodeSchema): string {
  const name = node.displayName ?? node.name ?? "This node";
  const fieldCount = node.fields.length;

  const requiredFields = node.fields.filter(f => f.required).length;

  const parts: string[] = [];

  parts.push(`${name} exposes ${fieldCount} configurable field${fieldCount === 1 ? "" : "s"}.`);

  if (requiredFields > 0) {
    parts.push(`${requiredFields} field${requiredFields === 1 ? "" : "s"} ${requiredFields === 1 ? "is" : "are"} required.`);
  }

  if (node.credentials.length > 0) {
    parts.push(`Requires authentication credentials.`);
  }

  if (node.description) {
    parts.push(node.description);
  }

  return parts.join(" ");
}
