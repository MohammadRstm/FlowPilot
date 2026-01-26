import { Project } from "ts-morph";

/**
 * Cache for NodeConnectionTypes → friendly name mapping.
 *
 * This is loaded once per process and reused.
 *
 * Example:
 *   NodeConnectionTypes.Main → "Main"
 */
export class ConnectionTypeCache {
  private readonly map: Record<string, string> = {};
  private loaded = false;

  constructor(private readonly project: Project) {}

  /**
   * Loads NodeConnectionTypes from the project AST.
   * Safe to call multiple times.
   */
  load(): void {
    if (this.loaded) return;

    for (const sourceFile of this.project.getSourceFiles()) {
      if (!sourceFile.getFullText().includes("NodeConnectionTypes")) continue;

      // Prefer enum definition
      const enums = sourceFile
        .getEnums()
        .filter(e => e.getName() === "NodeConnectionTypes");

      if (enums.length) {
        for (const member of enums[0].getMembers()) {
          const name = member.getName();
          this.map[`NodeConnectionTypes.${name}`] = name;
        }
        this.loaded = true;
        return;
      }

      // Fallback: const object literal
      const text = sourceFile.getFullText();
      const match = text.match(/NodeConnectionTypes\s*=\s*\{([\s\S]*?)\}/m);
      if (match) {
        for (const line of match[1].split("\n")) {
          const m = line.match(/(\w+)\s*:/);
          if (m) {
            this.map[`NodeConnectionTypes.${m[1]}`] = m[1];
          }
        }
        this.loaded = true;
        return;
      }
    }

    this.loaded = true;
  }

  /**
   * Resolves a raw connection type value into a friendly name.
   */
  resolve(raw: string): string {
    return this.map[raw] ?? raw.replace(/^NodeConnectionTypes\./, "");
  }
}
