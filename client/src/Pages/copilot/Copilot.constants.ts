import type { GenerationStage } from "./Copilot.types";

export const STAGE_LABELS: Record<GenerationStage, string> = {
  idle: "",
  analyzing: "Understanding your request",
  retrieving: "Searching relevant workflows",
  ranking: "Evaluating best solution",
  generating: "Generating workflow",
  validating: "Validating workflow logic",
  done: "",
};