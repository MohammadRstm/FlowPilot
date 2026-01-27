import path from "path";

/**
 * Centralized filesystem paths.
 * Keeps environment-specific concerns out of logic.
 */
export const ROOT = path.resolve("./n8n-nodes-base/packages/nodes-base");

export const NODES_DIR = path.join(ROOT, "nodes");
export const CREDS_DIR = path.join(ROOT, "credentials");

export const OUTPUT_FILE = "n8n_node_schemas.json";
export const LOG_FILE = "harvest.log";
