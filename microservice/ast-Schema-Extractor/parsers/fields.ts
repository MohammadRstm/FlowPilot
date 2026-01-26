import { Node, ObjectLiteralExpression } from "ts-morph";
import { extractLiteral } from "../ast/literals";
import { getObjectProperty } from "../ast/objects";
import { createFieldSchema } from "../schemas/fieldSchema";

/**
 * Parses a single field definition inside `properties`.
 *
 * This does NOT recurse — recursion is handled at a higher level.
 */
export function parseField(fieldNode: ObjectLiteralExpression) {
  return createFieldSchema({
    name: extractLiteral(getObjectProperty(fieldNode, "name")),
    displayName: extractLiteral(getObjectProperty(fieldNode, "displayName")),
    type: extractLiteral(getObjectProperty(fieldNode, "type")),
    required: extractLiteral(getObjectProperty(fieldNode, "required")) ?? false,
    description: extractLiteral(getObjectProperty(fieldNode, "description")),
  });
}
