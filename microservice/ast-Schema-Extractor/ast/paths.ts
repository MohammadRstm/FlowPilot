import path from "path";

/**
 * Cross-platform safe path prefix comparison.
 *
 * Needed because ts-morph returns absolute paths
 * and Windows path separators will break naive checks.
 */
export function pathStartsWith(candidate: string, prefix: string): boolean {
  const a = path.resolve(candidate).split(path.sep);
  const b = path.resolve(prefix).split(path.sep);

  return a.slice(0, b.length).join(path.sep) === b.join(path.sep);
}
