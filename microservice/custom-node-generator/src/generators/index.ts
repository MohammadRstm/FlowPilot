import { NodeSpec } from "../types/NodeSpec";
import { generatePackageJson } from "./packageJson.generator";
import { generateCredentialsFile } from "./credentials.generator";
import { generateNodeFile } from "./nodeTs.generator";
import { generateNodeJson } from "./nodeJson.generator";
import { generateTsConfig } from "./tsconfig.generator";

export interface GeneratedFile {
  path: string;
  content: string;
}

export function generateNodeProject(spec: NodeSpec): GeneratedFile[] {
  const files: GeneratedFile[] = [];

  files.push(generatePackageJson(spec));
  files.push(generateTsConfig());

  files.push(generateCredentialsFile(spec));

  files.push(generateNodeFile(spec));
  files.push(generateNodeJson(spec));

  return files;
}
