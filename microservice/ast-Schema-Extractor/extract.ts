import { Project, SyntaxKind, Node, SourceFile, ArrayLiteralExpression, ObjectLiteralExpression, PropertyAssignment } from "ts-morph";
import * as fs from "fs";
import * as path from "path";


const ROOT = path.resolve("./n8n-nodes-base/packages/nodes-base");
const NODES_DIR = path.join(ROOT, "nodes");
const CREDS_DIR = path.join(ROOT, "credentials");
const OUTPUT = "n8n_node_schemas.json";
const LOG = "harvest.log";


/* ================= LOGGER ================= */

function log(msg: string) {
  const ts = new Date().toISOString();
  fs.appendFileSync(LOG, `[${ts}] ${msg}\n`);
  console.log(msg);
}

/* ================= PROJECT ================= */

const project = new Project({
  tsConfigFilePath: "tsconfig.json",
  skipAddingFilesFromTsConfig: false,
});

// preload all .ts files under nodes + credentials for reliable symbol resolution
function preloadSourceFiles(){
  const nodePattern = path.join(NODES_DIR, "**", "*.ts");
  const credPattern = path.join(CREDS_DIR, "**", "*.ts");

  log(`Preloading source files from patterns:\n  ${nodePattern}\n  ${credPattern}`);
  project.addSourceFilesAtPaths([nodePattern, credPattern]);
  const count = project.getSourceFiles().length;
  log(`Project source files loaded: ${count}`);
}
preloadSourceFiles();

/* ================= UTIL ================= */

/**
 * Safe resolver: if node is an Identifier or PropertyAccessExpression, find its declaration initializer (following imports across files).
 * If node is itself an ObjectLiteralExpression or ArrayLiteralExpression, return as-is.
 */
function resolveInitializer(node?: Node | null): Node | undefined {
  if (!node) return undefined;

  if (Node.isIdentifier(node) || Node.isPropertyAccessExpression(node)) {
    const symbol = node.getSymbol?.();
    if (symbol) {
      const decl = symbol.getDeclarations()?.[0];
      if (!decl) return undefined;
      // variable declarations / exported consts usually have getInitializer
      if ("getInitializer" in decl && typeof (decl as any).getInitializer === "function") {
        const init = (decl as any).getInitializer();
        return init ?? decl;
      }
      if (Node.isVariableDeclaration(decl)) {
        return decl.getInitializer ? decl.getInitializer() : decl;
      }
      // If it's exported const in another file, try to get its initializer from parent nodes
      // last resort: return the declaration node
      return decl;
    }
    return undefined;
  }
  return node;
}

/** Extract simple literal value or fallback to text */
function extractLiteral(node?: Node | null): any {
  if (!node) return undefined;
  if (Node.isStringLiteral(node) || node.getKind() === SyntaxKind.StringLiteral) {
    // remove surrounding quotes
    const txt = node.getText();
    return txt.startsWith("'") || txt.startsWith('"') ? txt.slice(1, -1) : txt;
  }
  if (Node.isNumericLiteral(node)) return Number(node.getText());
  if (node.getKind() === SyntaxKind.TrueKeyword) return true;
  if (node.getKind() === SyntaxKind.FalseKeyword) return false;
  // For object/array expression return its node text -- caller should inspect further
  return node.getText ? node.getText().replace(/['"`]/g, "") : undefined;
}

/** Resolve array-like expression into elements (resolving spreads and identifier indirections) */
function resolveArrayElements(node?: Node | null): Node[] {
  if (!node) return [];
  let resolved = resolveInitializer(node) ?? node;
  const out: Node[] = [];
  if (Node.isArrayLiteralExpression(resolved)) {
    for (const el of resolved.getElements()) {
      if (Node.isSpreadElement(el)) {
        const inner = resolveInitializer(el.getExpression());
        if (inner) {
          out.push(...resolveArrayElements(inner));
        } else {
          // include expression text as fallback
          out.push(el.getExpression());
        }
      } else {
        out.push(el);
      }
    }
    return out;
  }
  // If it's an identifier that points to an array declared elsewhere
  if (Node.isIdentifier(resolved) || Node.isPropertyAccessExpression(resolved)) {
    const sym = resolved.getSymbol?.();
    if (sym) {
      const decl = sym.getDeclarations()?.[0];
      if (decl) {
        const init = (decl as any).getInitializer?.();
        if (init && Node.isArrayLiteralExpression(init)) {
          return (init as ArrayLiteralExpression).getElements();
        }
        // if init is another identifier, follow recursively
        const deeper = resolveInitializer(init);
        if (deeper) return resolveArrayElements(deeper);
      }
    }
  }
  return out;
}

/** Helper to get property value initializer node from object literal safely */
function getObjectProperty(obj: ObjectLiteralExpression, name: string): Node | undefined {
  const prop = obj.getProperty(name);
  if (!prop) return undefined;
  if (Node.isPropertyAssignment(prop)) {
    return prop.getInitializer();
  }
  // handle shorthand, spread & other cases conservatively
  return undefined;
}

/* ================= CONNECTION TYPES CACHE ================= */

/**
 * Find first declaration of NodeConnectionTypes (enum or const) in the loaded project and build a simple map
 * e.g. NodeConnectionTypes.Main -> "Main"
 */
function loadConnectionTypes(): Record<string, string> {
  const map: Record<string, string> = {};
  const sfs = project.getSourceFiles();
  for (const sf of sfs) {
    const txt = sf.getFullText();
    if (!txt.includes("NodeConnectionTypes")) continue;
    try {
      // naive parse: look for "export enum NodeConnectionTypes { ... }"
      const enums = sf.getEnums().filter(e => e.getName() === "NodeConnectionTypes");
      if (enums.length) {
        const members = enums[0].getMembers();
        for (const m of members) {
          const name = m.getName();
          map[`NodeConnectionTypes.${name}`] = name;
        }
        log(`Loaded NodeConnectionTypes from ${sf.getFilePath()}`);
        return map;
      }
      // fallback: search for const object
      const matches = txt.match(/NodeConnectionTypes\s*=\s*\{([\s\S]*?)\}/m);
      if (matches) {
        const body = matches[1];
        const lines = body.split(/\n/);
        for (const l of lines) {
          const m = l.match(/(\w+)\s*:/);
          if (m) map[`NodeConnectionTypes.${m[1]}`] = m[1];
        }
        log(`Parsed NodeConnectionTypes object from ${sf.getFilePath()}`);
        return map;
      }
    } catch (err) {
      // ignore and continue
    }
  }
  log("No NodeConnectionTypes declaration found in project; outputs will keep raw names.");
  return map;
}

const CONNECTION_TYPE_CACHE = loadConnectionTypes();

/* ================= CREDENTIALS PARSING ================= */

/**
 * Parse credentials files to extract credential definitions and their properties.
 * Returns map { credentialName -> { file, className, properties: [{name,type,default,description}], raw, extends } }
 */
function parseAllCredentials(): Record<string, any> {
  const map: Record<string, any> = {};
  const files = project.getSourceFiles().filter(sf => pathStartsWith(sf.getFilePath(), path.resolve(CREDS_DIR)));
  log(`Parsing ${files.length} credential source files...`);
  for (const sf of files) {
    try {
      const classes = sf.getClasses();
      for (const cls of classes) {
        // property 'name' initializer
        const nameProp = cls.getProperty("name")?.getInitializer();
        const className = cls.getName();
        const credName = nameProp ? extractLiteral(nameProp) : className ? className.charAt(0).toLowerCase() + className.slice(1) : undefined;
        if (!credName) continue;

        // parse properties[] array if exists
        const propsNode = cls.getProperty("properties")?.getInitializer();
        const props: any[] = [];
        if (propsNode) {
          const elems = resolveArrayElements(propsNode);
          for (const el of elems) {
            if (Node.isObjectLiteralExpression(el)) {
              const n = extractLiteral(getObjectProperty(el as ObjectLiteralExpression, "name"))
                ?? extractLiteral(getObjectProperty(el as ObjectLiteralExpression, "displayName"));
              const t = extractLiteral(getObjectProperty(el as ObjectLiteralExpression, "type")) ?? "string";
              const d = extractLiteral(getObjectProperty(el as ObjectLiteralExpression, "default")) ?? null;
              props.push({ name: n, type: t, default: d });
            } else {
              // fallback: text
              props.push({ name: el.getText(), type: "unknown" });
            }
          }
        }

        // extends property if present
        const extendsNode = cls.getProperty("extends")?.getInitializer();
        let extendsList: string[] = [];
        if (extendsNode) {
          const els = resolveArrayElements(extendsNode);
          extendsList = els.map(e => extractLiteral(e)).filter(Boolean);
        }

        map[credName] = {
          file: sf.getFilePath(),
          className,
          properties: props,
          extends: extendsList,
          raw: sf.getFullText(),
        };
        log(` parsed credential: ${credName} (${className})`);
      }
    } catch (err) {
      log(`warning: failed to parse credentials file ${sf.getFilePath()}: ${(err as Error).message}`);
    }
  }
  log(`Credentials parsed: ${Object.keys(map).length}`);
  return map;
}

/* ================= FIELD EXTRACTION ================= */

/**
 * Extract a field descriptor object from a property (object literal node).
 * Handles collection / fixedCollection nested fields by extracting subfields recursively.
 */
function extractFieldFromProp(propNode: Node, sf: SourceFile): any | null {
  // propNode expected to be ObjectLiteralExpression or an Identifier (resolve)
  const resolved = resolveInitializer(propNode) ?? propNode;
  if (!Node.isObjectLiteralExpression(resolved)) {
    // maybe it's a simple identifier reused - try to resolve further
    return null;
  }
  const obj = resolved as ObjectLiteralExpression;
  const name = extractLiteral(getObjectProperty(obj, "name")) ?? extractLiteral(getObjectProperty(obj, "displayName")) ?? undefined;
  if (!name) return null;
  const type = extractLiteral(getObjectProperty(obj, "type")) ?? "string";
  const required = !!extractLiteral(getObjectProperty(obj, "required"));
  const description = extractLiteral(getObjectProperty(obj, "description")) ?? "";

  const field: any = {
    name,
    type,
    required,
    description,
  };

  // handle options for option types
  const optionsNode = getObjectProperty(obj, "options");
  if (optionsNode) {
    const elems = resolveArrayElements(optionsNode);
    field.options = elems.map(el => {
      if (Node.isObjectLiteralExpression(el)) {
        return {
          name: extractLiteral(getObjectProperty(el as ObjectLiteralExpression, "name")),
          value: extractLiteral(getObjectProperty(el as ObjectLiteralExpression, "value")),
          displayOptions: !!getObjectProperty(el as ObjectLiteralExpression, "displayOptions"),
        };
      }
      return { raw: extractLiteral(el) ?? el.getText() };
    });
  }

  // handle fixedCollection / collection nested 'options' or 'values'
  if (type === "fixedCollection" || type === "collection") {
    const valuesNode = getObjectProperty(obj, "options") ?? getObjectProperty(obj, "typeOptions") ?? undefined;
    if (valuesNode) {
      const optionElems = resolveArrayElements(valuesNode);
      const nested: any[] = [];
      for (const oe of optionElems) {
        const oResolved = resolveInitializer(oe) ?? oe;
        if (!Node.isObjectLiteralExpression(oResolved)) continue;
        const optName = extractLiteral(getObjectProperty(oResolved as ObjectLiteralExpression, "name")) ?? extractLiteral(getObjectProperty(oResolved as ObjectLiteralExpression, "displayName"));
        const valsNode = getObjectProperty(oResolved as ObjectLiteralExpression, "values") ?? getObjectProperty(oResolved as ObjectLiteralExpression, "options");
        const subfields: any[] = [];
        if (valsNode) {
          const vEls = resolveArrayElements(valsNode);
          for (const v of vEls) {
            const sub = extractFieldFromProp(v, sf);
            if (sub) subfields.push(sub);
          }
        }
        nested.push({ name: optName, fields: subfields });
      }
      field.collection = nested;
    } else {
      const opts = resolveArrayElements(getObjectProperty(obj, "options") ?? null);
      if (opts.length) {
        field.collection = opts.map(o => ({ raw: extractLiteral(o) ?? o.getText() }));
      }
    }
  }

  return field;
}

/* ================= NODE PARSING ================= */

/** Normalize key for node-identification */
function normalizeKey(name: string) {
  return name.replace(/[^a-z0-9]/gi, "").toLowerCase();
}

/** Build an AI-friendly summary when missing: deterministic sentence using node/op/resource and top fields */
function buildAiSummary(nodeDisplay: string, resource: string, operation: string, fields: any[]): string {
  const top = fields.slice(0, 6).map(f => f.name).join(", ");
  const target = resource && resource !== "default" ? `${resource}` : "the node";
  const action = operation && operation !== "default" ? `${operation}` : "operate";
  const fieldPart = top ? ` It accepts fields: ${top}.` : "";
  return `${nodeDisplay} - ${action} on ${target}.${fieldPart} Use the listed fields to configure the ${nodeDisplay} ${operation} operation.`;
}

/* helper to check prefix in a robust cross-platform way */
function pathStartsWith(candidate: string, prefix: string) {
  const a = path.resolve(candidate);
  const b = path.resolve(prefix);
  const aParts = a.split(path.sep);
  const bParts = b.split(path.sep);
  return aParts.slice(0, bParts.length).join(path.sep) === bParts.join(path.sep);
}

/* ================= MAIN ================= */

log("Starting n8n node schema extraction...");

// parse credentials first (use resolved CREDS_DIR)
const RESOLVED_CREDS_DIR = path.resolve(CREDS_DIR);
const credentialsMap = parseAllCredentials();

// collect all .node.ts files by comparing resolved absolute paths (platform-safe)
const allSourceFiles = project.getSourceFiles();
log(`Total project source files: ${allSourceFiles.length}`);

const RESOLVED_NODES_DIR = path.resolve(NODES_DIR);
const nodeSourceFiles = allSourceFiles.filter(sf => pathStartsWith(sf.getFilePath(), RESOLVED_NODES_DIR));
log(`Found ${nodeSourceFiles.length} node source files in project (filtered).`);

// If zero found, print sample file paths for debugging
if (nodeSourceFiles.length === 0) {
  log("==== Debug: sample project files (first 20) ====");
  allSourceFiles.slice(0, 20).forEach((sf, i) => log(`${i}: ${sf.getFilePath()}`));
  log("==== end sample ====");
}

// We will produce one schema per node/resource/operation
const schemaMap: Record<string, any> = {};

for (const sf of nodeSourceFiles) {
  try {
    const filePath = sf.getFilePath();
    log(`\nParsing file: ${filePath}`);

    const classes = sf.getClasses();
    for (const cls of classes) {
      const descInit = cls.getProperty("description")?.getInitializer();
      if (!descInit) continue;
      const desc = resolveInitializer(descInit) ?? descInit;
      if (!Node.isObjectLiteralExpression(desc)) continue;

      const nodeName = extractLiteral(getObjectProperty(desc, "name")) ?? undefined;
      const displayName = extractLiteral(getObjectProperty(desc, "displayName")) ?? nodeName;
      if (!nodeName) continue;
      log(` Node found: ${nodeName} (${displayName})`);

      // credentials attached at description level
      const credsNodes = getObjectProperty(desc, "credentials");
      const nodeCreds: string[] = [];
      if (credsNodes) {
        const credElems = resolveArrayElements(credsNodes);
        for (const ce of credElems) {
          if (Node.isObjectLiteralExpression(ce)) {
            const credName = extractLiteral(getObjectProperty(ce as ObjectLiteralExpression, "name"));
            if (credName) nodeCreds.push(credName);
          } else {
            const txt = extractLiteral(ce) ?? ce.getText();
            nodeCreds.push(txt);
          }
        }
      }

      // properties initializer (can be identifier referencing import or array literal)
      const propsInit = getObjectProperty(desc, "properties");
      if (!propsInit) {
        log(`  warning: no properties for node ${nodeName}`);
        continue;
      }

      // resolve properties array elements (resolving spreads & identifiers)
      const propsElements = resolveArrayElements(propsInit);

      // find resource options if present
      let resourceOptions: string[] = ["default"];
      try {
        const resourceProp = propsElements.find(p => {
          const name = extractLiteral(getObjectProperty(p as ObjectLiteralExpression, "name"));
          return name === "resource";
        });
        if (resourceProp) {
          const optsNode = getObjectProperty(resourceProp as ObjectLiteralExpression, "options");
          if (optsNode) {
            const optEls = resolveArrayElements(optsNode);
            const res = optEls.map(o => {
              if (Node.isObjectLiteralExpression(o)) {
                return extractLiteral(getObjectProperty(o as ObjectLiteralExpression, "value")) ?? extractLiteral(getObjectProperty(o as ObjectLiteralExpression, "name"));
              }
              // if element is string literal
              return extractLiteral(o);
            }).filter(Boolean) as string[];
            if (res.length) resourceOptions = res;
          }
        }
      } catch (e) {
        log(`  debug: resource options parse failed for ${nodeName}: ${(e as Error).message}`);
      }

      // find global operations if present (operation property may be defined in an imported array)
      let globalOperations: string[] = ["default"];
      try {
        const operationProp = propsElements.find(p => {
          const name = extractLiteral(getObjectProperty(p as ObjectLiteralExpression, "name"));
          return name === "operation";
        });
        if (operationProp) {
          const optsNode = getObjectProperty(operationProp as ObjectLiteralExpression, "options");
          if (optsNode) {
            const optEls = resolveArrayElements(optsNode);
            const ops = optEls.map(o => {
              if (Node.isObjectLiteralExpression(o)) {
                return extractLiteral(getObjectProperty(o as ObjectLiteralExpression, "value")) ?? extractLiteral(getObjectProperty(o as ObjectLiteralExpression, "name"));
              }
              return extractLiteral(o);
            }).filter(Boolean) as string[];
            if (ops.length) globalOperations = ops;
          }
        }
      } catch (e) {
        log(`  debug: operation options parse failed for ${nodeName}: ${(e as Error).message}`);
      }

      // iterate over props and extract fields, taking into account displayOptions -> show.resource/show.operation
      for (const propEl of propsElements) {
        const propResolved = resolveInitializer(propEl) ?? propEl;
        if (!Node.isObjectLiteralExpression(propResolved)) continue;

        const propObj = propResolved as ObjectLiteralExpression;
        const propName = extractLiteral(getObjectProperty(propObj, "name"));
        if (!propName) continue;

        // if this is the main 'operation' property, we don't add it as a field; operations were captured above
        if (propName === "resource" || propName === "operation") continue;

        // determine displayOptions
        let resourcesForThisProp = resourceOptions.slice();
        let operationsForThisProp = globalOperations.slice();
        const displayOptionsNode = getObjectProperty(propObj, "displayOptions");
        if (displayOptionsNode && Node.isObjectLiteralExpression(displayOptionsNode)) {
          const showNode = getObjectProperty(displayOptionsNode as ObjectLiteralExpression, "show");
          if (showNode && Node.isObjectLiteralExpression(showNode)) {
            const rNode = getObjectProperty(showNode as ObjectLiteralExpression, "resource");
            const oNode = getObjectProperty(showNode as ObjectLiteralExpression, "operation");
            if (rNode) {
              resourcesForThisProp = resolveArrayElements(rNode).map(n => extractLiteral(n)).filter(Boolean) as string[];
            }
            if (oNode) {
              operationsForThisProp = resolveArrayElements(oNode).map(n => extractLiteral(n)).filter(Boolean) as string[];
            }
          }
        }

        // extract field details (including nested collection fields)
        const field = extractFieldFromProp(propObj, sf);
        if (!field) continue;

        // attach field to each (resource, operation) pair
        for (const r of resourcesForThisProp) {
          for (const o of operationsForThisProp) {
            const key = `${nodeName}::${r}::${o}`;
            if (!schemaMap[key]) {
              schemaMap[key] = {
                node: nodeName,
                node_normalized: normalizeKey(nodeName),
                displayName,
                resource: r,
                operation: o,
                credentials: nodeCreds.slice(),
                fields: [],
                inputs: [],
                outputs: [],
                description: "", // may be filled later from property or docstrings
                ai_summary: "",
                raw_sources: [filePath],
              };
            } else {
              // keep track of source files
              if (!schemaMap[key].raw_sources.includes(filePath)) schemaMap[key].raw_sources.push(filePath);
            }
            // avoid duplicate fields by name
            if (!schemaMap[key].fields.some((f: any) => f.name === field.name)) {
              schemaMap[key].fields.push(field);
            }
          }
        }
      } // end propsElements loop

      // try fill inputs/outputs from description (and map friendly names if possible)
      try {
        const inputsNode = getObjectProperty(desc as ObjectLiteralExpression, "inputs");
        const outputsNode = getObjectProperty(desc as ObjectLiteralExpression, "outputs");
        if (inputsNode) {
          const ins = resolveArrayElements(inputsNode).map(n => extractLiteral(n));
          for (const key of Object.keys(schemaMap)) {
            if (key.startsWith(nodeName + "::")) {
              schemaMap[key].inputs = (ins as any[]).map(i => {
                const rawName = String(i);
                return {
                  name: rawName,
                  friendly: CONNECTION_TYPE_CACHE[rawName] ?? rawName.replace(/^NodeConnectionTypes\./, ""),
                  type: "any"
                };
              });
            }
          }
        }
        if (outputsNode) {
          const outs = resolveArrayElements(outputsNode).map(n => extractLiteral(n));
          for (const key of Object.keys(schemaMap)) {
            if (key.startsWith(nodeName + "::")) {
              schemaMap[key].outputs = (outs as any[]).map(o => {
                const rawName = String(o);
                return {
                  name: rawName,
                  friendly: CONNECTION_TYPE_CACHE[rawName] ?? rawName.replace(/^NodeConnectionTypes\./, ""),
                  type: "any"
                };
              });
            }
          }
        }
      } catch (e) {
        // ignore
      }

      // attempt to get a description at node-level or per-property (use node-level fallback)
      const nodeDescText = extractLiteral(getObjectProperty(desc as ObjectLiteralExpression, "description")) ?? "";

      // fill descriptions and ai_summary per schema entry for this node
      for (const key of Object.keys(schemaMap)) {
        if (!key.startsWith(nodeName + "::")) continue;
        if (!schemaMap[key].description || schemaMap[key].description === "") {
          schemaMap[key].description = nodeDescText || "";
        }
        if (!schemaMap[key].ai_summary || schemaMap[key].ai_summary === "") {
          schemaMap[key].ai_summary = buildAiSummary(schemaMap[key].displayName, schemaMap[key].resource, schemaMap[key].operation, schemaMap[key].fields);
        }
        // attach credential detailed info from credentialsMap
        schemaMap[key].credentials_details = (schemaMap[key].credentials || [])
          .map((c: string) => credentialsMapLookup(credentialsMap, c))
          .filter(Boolean);
      }

    } // end classes
  } catch (err) {
    log(`Error parsing source file ${sf.getFilePath()}: ${(err as Error).message}`);
  }
}

// helper to lookup credentials map safely
function credentialsMapLookup(map: Record<string, any>, credName: string) {
  if (!credName) return null;
  const key = Object.keys(map).find(k => k.toLowerCase() === credName.toLowerCase());
  return key ? map[key] : null;
}

/* ================= FINALIZE ================= */

// Convert schemaMap to array and ensure one schema per node/resource/operation
const finalSchemas = Object.values(schemaMap).map((s: any) => {
  // normalize minimal fields for Qdrant
  return {
    node: s.node,
    node_normalized: s.node_normalized,
    displayName: s.displayName,
    resource: s.resource,
    operation: s.operation,
    credentials: s.credentials,
    credentials_details: s.credentials_details,
    description: s.description,
    ai_summary: s.ai_summary,
    fields: s.fields,
    inputs: s.inputs,
    outputs: s.outputs,
    raw_sources: s.raw_sources,
  };
});

fs.writeFileSync(OUTPUT, JSON.stringify(finalSchemas, null, 2), { encoding: "utf8" });
log(`\n✔ Extraction complete — wrote ${finalSchemas.length} operation schemas to ${OUTPUT}`);
