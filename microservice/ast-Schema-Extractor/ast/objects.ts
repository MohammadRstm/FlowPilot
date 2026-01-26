import { Node, ObjectLiteralExpression } from "ts-morph";

/**
 * Safely retrieves a property initializer from an object literal.
 *
 * Only supports PropertyAssignment.
 * Shorthand and spreads are ignored intentionally.(handled by arrays resolver)
 */
export function getObjectProperty(
  obj: ObjectLiteralExpression,
  name: string
): Node | undefined {
  const prop = obj.getProperty(name);
  if (!prop) return undefined;

  if (Node.isPropertyAssignment(prop)) {
    return prop.getInitializer();
  }

  return undefined;
}
