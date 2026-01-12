import type { GenerationStage } from "./Copilot.types";

export const STAGE_MAP: Record<string, GenerationStage> = {
  analyzing: "analyzing",
  retrieval: "retrieving",
  ranking: "ranking",
  ranking_found_workflow: "ranking",
  generating: "generating",
  validating: "validating",
};

export const STAGE_LABELS: Record<GenerationStage, string> = {
  idle: "",
  analyzing: "Understanding your request",
  retrieving: "Searching existing workflows",
  ranking: "Evaluating best solution",
  generating: "Building workflow",
  validating: "Validating & repairing",
  done: "",
};

export const TRACE_LABELS: Record<string, string> = {
  "intent analysis": "Intent",
  candidates: "Retrieved candidates",
  genration_plan: "Workflow plan",
  workflow: "Generated workflow",
  judgement: "Validation",
  repaired_workflow: "Repaired workflow",
};

export type PlanNode = {
  name: string;
  role: string;
  from: string | null;
};

