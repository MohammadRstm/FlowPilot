import fs from "fs";
import path from "path";
import { NodeSchema } from "../domain/schema";
import { OUTPUT_FILE } from "../config/paths";
import { log } from "../infra/logger";

/**
 * Writes extracted schemas to disk.
 *
 * This is intentionally dumb:
 * - no filtering
 * - no transformation
 * - no validation
 */
export function writeSchemas(schemas: NodeSchema[]): void {
  const outputPath = path.resolve(OUTPUT_FILE);

  fs.writeFileSync(
    outputPath,
    JSON.stringify(schemas, null, 2),
    "utf-8"
  );

  log(`Schemas written to ${outputPath}`);
}
