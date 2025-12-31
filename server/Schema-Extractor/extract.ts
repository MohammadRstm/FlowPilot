import { Project, ts, SyntaxKind } from "ts-morph";
import * as path from "path";
import * as fs from "fs";

// === CONFIG ===
const ROOT_NODES_PATH = path.join(__dirname, "n8n-nodes-base/packages/nodes-base/nodes");
const OUTPUT_FILE = path.join(__dirname, "output.json");

// === UTILS ===
function normalizeKey(name: string): string {
  return name.replace(/[^a-z0-9]/gi, "").toLowerCase();
}
function getStringFromInitializer(init: any): string | undefined {
  if (!init) return undefined;
  if (init.getText) return init.getText().replace(/['"`]/g, "");
  const lit = init.asKind?.(SyntaxKind.StringLiteral);
  if (lit) return lit.getLiteralValue();
  return undefined;
}
function getPropertyInitializer(obj: any, name: string): any {
  if (!obj || !obj.getProperty) return undefined;
  const prop = obj.getProperty(name);
  if (!prop) return undefined;
  if (typeof (prop as any).getInitializer === "function") return (prop as any).getInitializer();
  return undefined;
}
function extractDisplayOptionsSafe(displayOptionsNode: any): { resource: string[]; operation: string[] } {
  const result = { resource: [] as string[], operation: [] as string[] };
  if (!displayOptionsNode) return result;
  if (displayOptionsNode.isKind?.(SyntaxKind.ObjectLiteralExpression)) {
    const showInit = getPropertyInitializer(displayOptionsNode, "show");
    if (!showInit) return result;
    const res = getPropertyInitializer(showInit, "resource");
    const ope = getPropertyInitializer(showInit, "operation");
    if (res?.isKind?.(SyntaxKind.ArrayLiteralExpression))
      result.resource = res.getElements().map((e: any) => e.getText().replace(/['"`]/g, ""));
    else if (res) result.resource = [res.getText().replace(/['"`]/g, "")];
    if (ope?.isKind?.(SyntaxKind.ArrayLiteralExpression))
      result.operation = ope.getElements().map((e: any) => e.getText().replace(/['"`]/g, ""));
    else if (ope) result.operation = [ope.getText().replace(/['"`]/g, "")];
  }
  return result;
}

// === PROJECT ===
const project = new Project({ tsConfigFilePath: path.join(__dirname, "tsconfig.json") });

// === RECURSIVE WALK ===
function getAllNodeFiles(dir: string): string[] {
  let results: string[] = [];
  const entries = fs.readdirSync(dir, { withFileTypes: true });
  for (const entry of entries) {
    const fullPath = path.join(dir, entry.name);
    if (entry.isDirectory()) results = results.concat(getAllNodeFiles(fullPath));
    else if (entry.isFile() && entry.name.endsWith(".node.ts")) results.push(fullPath);
  }
  return results;
}

// === EXTRACT FIELDS, TYPES, REQUIRED, DESCRIPTION ===
function extractFieldSchemasSafe(obj: any, nodeName: string, schemas: any[]) {
  const name = getStringFromInitializer(getPropertyInitializer(obj, "name"));
  if (!name) return;
  const display = getStringFromInitializer(getPropertyInitializer(obj, "displayName")) || name;
  const type = getStringFromInitializer(getPropertyInitializer(obj, "type")) || "string";
  const required = getPropertyInitializer(obj, "required")?.getText() === "true";
  const description = getStringFromInitializer(getPropertyInitializer(obj, "description")) || "";

  const displayOptionsNode = getPropertyInitializer(obj, "displayOptions");
  const combos = expandDisplayOptionsSafe(displayOptionsNode);

  for (const { resource, operation } of combos) {
    schemas.push({
      node: nodeName,
      node_normalized: normalizeKey(nodeName),
      resource,
      operation,
      display,
      fieldName: name,
      type,
      required,
      description,
      inputs: [
        {
          name: "Previous Node Output",
          type: "JSON",
          required: false,
          description: "Optional input from a previous node",
        },
      ],
      outputs: [
        {
          name: "Output",
          type: "JSON",
          description: "Generic JSON output; expand with specific fields if available",
          fields: [],
        },
      ],
    });
  }
}

function expandDisplayOptionsSafe(displayOptionsNode: any): { resource: string; operation: string }[] {
  const res = extractDisplayOptionsSafe(displayOptionsNode);
  const resources = res.resource.length ? res.resource : ["default"];
  const operations = res.operation.length ? res.operation : ["default"];
  const combos: { resource: string; operation: string }[] = [];
  for (const r of resources) for (const o of operations) combos.push({ resource: r, operation: o });
  return combos;
}

// === PARSE NODE FILE ===
function parseNode(filePath: string) {
  const sourceFile = project.addSourceFileAtPath(filePath);
  const classes = sourceFile.getClasses();
  const schemas: any[] = [];

  for (const cls of classes) {
    const descProp = cls.getProperty("description");
    if (!descProp) continue;
    let initializer: any = typeof descProp.getInitializer === "function" ? descProp.getInitializer() : undefined;
    if (!initializer) continue;
    if (initializer.getKindName?.() === "Identifier") {
      const decl = sourceFile.getVariableDeclaration(initializer.getText());
      if (decl) initializer = decl.getInitializer();
    }
    if (!initializer || !initializer.isKind?.(SyntaxKind.ObjectLiteralExpression)) continue;
    const nodeName = getStringFromInitializer(getPropertyInitializer(initializer, "name"))
      || getStringFromInitializer(getPropertyInitializer(initializer, "displayName"));
    if (!nodeName) continue;

    const propsInit = getPropertyInitializer(initializer, "properties");
    if (!propsInit) continue;
    let propertiesArray: any[] = [];
    if (propsInit.isKind?.(SyntaxKind.ArrayLiteralExpression)) propertiesArray = propsInit.getElements();
    else if (propsInit.getText?.() && propsInit.isKind?.(SyntaxKind.Identifier)) {
      const decl = sourceFile.getVariableDeclaration(propsInit.getText());
      if (decl) {
        const init = decl.getInitializer();
        if (init?.isKind?.(SyntaxKind.ArrayLiteralExpression)) propertiesArray = init.getElements();
      }
    }

    for (const el of propertiesArray) {
      const obj = el.asKind?.(SyntaxKind.ObjectLiteralExpression);
      if (!obj) continue;
      extractFieldSchemasSafe(obj, nodeName, schemas);
    }
  }

  return schemas;
}

// === MAIN ===
function discoverAllNodeSchemas(): { path: string; schemas: any[] }[] {
  const files = getAllNodeFiles(ROOT_NODES_PATH);
  console.log(`Found ${files.length} .node.ts files`);
  const perFile: { path: string; schemas: any[] }[] = [];
  for (const file of files) {
    const schemas = parseNode(file);
    if (schemas.length) perFile.push({ path: file, schemas });
    else console.log(`No schemas in: ${file}`);
  }
  return perFile;
}

const results = discoverAllNodeSchemas();
fs.writeFileSync(OUTPUT_FILE, JSON.stringify(results.flatMap(r => r.schemas), null, 2));
console.log(`Output written to ${OUTPUT_FILE}`);
