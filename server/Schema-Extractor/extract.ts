import { Project, ts, SyntaxKind } from "ts-morph";
import * as path from "path";
import * as fs from "fs";

// === CONFIG ===
const NODES_BASE_PATH = path.join(__dirname, "n8n-nodes-base", "packages/nodes-base/nodes");
const OUTPUT_FILE = path.join(__dirname, "output.json");

// === UTILS ===
function normalizeKey(name: string): string {
    return name.replace(/[^a-z0-9]/gi, "").toLowerCase();
}

function expandDisplayOptions(displayOptions: any): { resource: string; operation: string }[] {
    if (!displayOptions?.show) return [{ resource: "default", operation: "default" }];
    const resources = Array.isArray(displayOptions.show.resource) ? displayOptions.show.resource : [displayOptions.show.resource ?? "default"];
    const operations = Array.isArray(displayOptions.show.operation) ? displayOptions.show.operation : [displayOptions.show.operation ?? "default"];
    const combinations: { resource: string; operation: string }[] = [];
    for (const r of resources) {
        for (const o of operations) {
            combinations.push({ resource: r, operation: o });
        }
    }
    return combinations;
}

function getLiteralValue(obj: any, propName: string): string | undefined {
    const prop = obj.getProperty(propName);
    if (!prop || !prop.isKind(SyntaxKind.PropertyAssignment)) return undefined;
    const init = prop.getInitializer();
    if (!init) return undefined;
    return init.getText().replace(/['"`]/g, "");
}

// === PROJECT ===
const project = new Project({
    tsConfigFilePath: path.join(__dirname, "tsconfig.json"),
});

// === RECURSIVE WALK ===
function getAllNodeFiles(dir: string): string[] {
    const entries = fs.readdirSync(dir, { withFileTypes: true });
    let files: string[] = [];
    for (const entry of entries) {
        const fullPath = path.join(dir, entry.name);
        if (entry.isDirectory()) {
            files = files.concat(getAllNodeFiles(fullPath));
        } else if (entry.isFile() && entry.name.endsWith(".node.ts")) {
            files.push(fullPath);
        }
    }
    return files;
}

// === PARSE NODE ===
function parseNode(filePath: string) {
    const sourceFile = project.addSourceFileAtPath(filePath);
    const classes = sourceFile.getClasses();
    const schemas: any[] = [];

    for (const cls of classes) {
        const descProp = cls.getProperty("description");
        if (!descProp) continue;

        const initializer = descProp.getInitializerIfKind(SyntaxKind.ObjectLiteralExpression);
        if (!initializer) continue;

        const nodeName = getLiteralValue(initializer, "name");
        if (!nodeName) continue;

        const prop = initializer.getProperty("properties");
        if (!prop || !prop.isKind(SyntaxKind.PropertyAssignment)) continue;

        const propertiesProp = prop.getInitializerIfKind(SyntaxKind.ArrayLiteralExpression);
        if (!propertiesProp) continue;

        const elements = propertiesProp.getElements();

        for (const el of elements) {
            if (el.getKind() === SyntaxKind.SpreadElement) {
                const expr = el.asKindOrThrow(SyntaxKind.SpreadElement).getExpression();
                const symbol = expr.getSymbol();
                if (!symbol) continue;

                for (const decl of symbol.getDeclarations()) {
                    const init = (decl as any).getInitializer?.();
                    if (init && ts.isArrayLiteralExpression(init.compilerNode)) {
                        init.getElements().forEach(item => {
                            const obj = item.asKind(SyntaxKind.ObjectLiteralExpression);
                            if (!obj) return;
                            extractFieldSchemas(obj, nodeName, schemas);
                        });
                    }
                }
            } else {
                const obj = el.asKind(SyntaxKind.ObjectLiteralExpression);
                if (!obj) continue;
                extractFieldSchemas(obj, nodeName, schemas);
            }
        }
    }

    return schemas;
}

// Extract field schema from object literal
function extractFieldSchemas(obj: any, nodeName: string, schemas: any[]) {
    const nameProp = obj.getProperty("name")?.getInitializer()?.getText().replace(/['"`]/g, "");
    if (!nameProp) return;

    const displayProp = obj.getProperty("displayName")?.getInitializer()?.getText().replace(/['"`]/g, "") ?? nameProp;
    const displayOptionsNode = obj.getProperty("displayOptions");

    let options: any = {};
    if (displayOptionsNode && displayOptionsNode.isKind(SyntaxKind.PropertyAssignment)) {
        try {
            const text = displayOptionsNode.getInitializer()?.getText();
            if (text) options = eval("(" + text + ")");
        } catch {}
    }

    expandDisplayOptions(options).forEach(({ resource, operation }) => {
        schemas.push({
            node: nodeName,
            node_normalized: normalizeKey(nodeName),
            resource,
            operation,
            display: displayProp,
            fieldName: nameProp,
        });
    });
}

// === MAIN ===
const nodeFiles = getAllNodeFiles(NODES_BASE_PATH);
console.log(`Found ${nodeFiles.length} .node.ts files.`);

const allSchemas: any[] = [];
for (const file of nodeFiles) {
    const nodeSchemas = parseNode(file);
    allSchemas.push(...nodeSchemas);
}

// Merge duplicate fields per node-resource-operation
const mergedSchemas: any[] = [];
const grouped = new Map<string, any>();

for (const s of allSchemas) {
    const key = `${s.node_normalized}::${s.resource}::${s.operation}`;
    if (!grouped.has(key)) grouped.set(key, { ...s, fields: [] });
    grouped.get(key).fields.push({ name: s.fieldName, display: s.display });
}

for (const value of grouped.values()) {
    mergedSchemas.push(value);
}

fs.writeFileSync(OUTPUT_FILE, JSON.stringify(mergedSchemas, null, 2));
console.log(`Schemas extracted: ${mergedSchemas.length}`);
console.log(`Output written to ${OUTPUT_FILE}`);
