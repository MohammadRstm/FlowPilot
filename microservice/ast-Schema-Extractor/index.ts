import { log } from "./infra/logger";
import { createProject } from "./infra/project";
import { extractSchemas } from "./services/schemaExtractor";
import { writeSchemas } from "./output/writer";

async function main() {
  log("Starting n8n node schema extraction");

  const project = createProject();
  const schemas = extractSchemas(project);

  writeSchemas(schemas);

  log(`Extraction complete — ${schemas.length} schemas written`);
}

main().catch(err => {
  log(`Fatal error: ${err.message}`);
  process.exit(1);
});
