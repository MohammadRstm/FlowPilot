import { SourceFile, Node } from "ts-morph";
import { resolveArrayElements } from "../ast/arrays";
import { resolveInitializer } from "../ast/resolver";
import { extractLiteral } from "../ast/literals";
import { getObjectProperty } from "../ast/objects";
import { parseField } from "./fields";

/**
 * Parses a node definition file and extracts schema-relevant data.
 */
export function parseNode(sourceFile: SourceFile) {
  const exports = sourceFile.getVariableDeclarations();
  if (!exports.length) return null;

  const declaration = exports[0];
  const initializer = resolveInitializer(declaration.getInitializer());

  if (!initializer || !Node.isObjectLiteralExpression(initializer)) {
    return null;
  }

  const descriptionNode = getObjectProperty(initializer, "description");
  if (!descriptionNode || !Node.isObjectLiteralExpression(descriptionNode)) {
    return null;
  }

  const propertiesNode = getObjectProperty(descriptionNode, "properties");
  const propertyElements = resolveArrayElements(propertiesNode);

  const fields = propertyElements
    .map(el => resolveInitializer(el))
    .filter(Node.isObjectLiteralExpression)
    .map(parseField);

  return {
    name: extractLiteral(getObjectProperty(descriptionNode, "name")),
    displayName: extractLiteral(getObjectProperty(descriptionNode, "displayName")),
    description: extractLiteral(getObjectProperty(descriptionNode, "description")),
    fields,
    file: sourceFile.getFilePath(),
  };
}
