import { SourceFile, Node } from "ts-morph";
import { resolveArrayElements } from "../ast/arrays";
import { resolveInitializer } from "../ast/resolver";
import { extractLiteral } from "../ast/literals";
import { getObjectProperty } from "../ast/objects";
import { parseField } from "./fields";
import { createNodeSchema, NodeSchema } from "../domain/schema";

/**
 * Parses a node definition file and extracts schema-relevant data.
 *
 * Responsibilities:
 * - Read node description object
 * - Extract fields, inputs, outputs
 * - Return a domain-aligned NodeSchema
 *
 * Does NOT:
 * - Resolve connection names
 * - Attach credentials
 * - Generate summaries
 */
export function parseNode(sourceFile: SourceFile) {
  const declarations = sourceFile.getVariableDeclarations();
  if (!declarations.length) return null;

  const declaration = declarations[0];
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
    .map(parseField)
    .filter(Boolean);


  const rawInputs = resolveArrayElements(
    getObjectProperty(descriptionNode, "inputs")
  ).map(extractLiteral);

  const rawOutputs = resolveArrayElements(
    getObjectProperty(descriptionNode, "outputs")
  ).map(extractLiteral);


  return createNodeSchema({
    name: extractLiteral(getObjectProperty(descriptionNode, "name")),
    displayName: extractLiteral(
      getObjectProperty(descriptionNode, "displayName")
    ),
    description: extractLiteral(
      getObjectProperty(descriptionNode, "description")
    ),
    fields,
    inputs: rawInputs.filter(Boolean) as string[],
    outputs: rawOutputs.filter(Boolean) as string[],
    file: sourceFile.getFilePath(),
  });
}
