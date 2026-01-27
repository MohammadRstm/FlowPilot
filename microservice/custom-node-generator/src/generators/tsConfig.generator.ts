export function generateTsConfig() {
  return {
    path: "tsconfig.json",
    content: JSON.stringify({
      compilerOptions: {
        module: "commonjs",
        target: "es2019",
        outDir: "dist",
        rootDir: ".",
        strict: true
      }
    }, null, 2)
  };
}
