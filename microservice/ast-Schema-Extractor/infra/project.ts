import { Project } from "ts-morph";
import path from "path";
import { NODES_DIR, CREDS_DIR } from "../config/paths";
import { log } from "./logger";

/**
 * Creates and preloads a ts-morph Project instance.
 * All AST parsing relies on this being fully hydrated.
 */
export function createProject(): Project {
  const project = new Project({
    tsConfigFilePath: "tsconfig.json",
    skipAddingFilesFromTsConfig: false,
  });

  preloadSources(project);
  return project;
}

function preloadSources(project: Project): void {
  const nodePattern = path.join(NODES_DIR, "**", "*.ts");
  const credPattern = path.join(CREDS_DIR, "**", "*.ts");

  log(`Preloading sources:\n  ${nodePattern}\n  ${credPattern}`);

  project.addSourceFilesAtPaths([nodePattern, credPattern]);

  log(`Loaded ${project.getSourceFiles().length} source files`);
}
