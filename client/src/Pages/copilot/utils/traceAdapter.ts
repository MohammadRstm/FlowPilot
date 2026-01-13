import type { TraceBlock } from "../Copilot.types";

export function applyTrace(
  prev: Record<number | "new", TraceBlock[]>,
  key: number | "new",
  trace: any
) {
  const blocks = prev[key] ?? [];

  let blockId: TraceBlock["type"] | null = null;

  if (trace.type === "intent analysis") blockId = "intent";
  if (trace.type === "candidates") blockId = "candidates";
  if (trace.type === "genration_plan") blockId = "plan";
  if (trace.type === "workflow") blockId = "workflow";
  if (trace.type === "judgement") blockId = "judgement";
  if (trace.type === "repaired_workflow") blockId = "repaired_workflow";

  if (!blockId) return prev;

  const index = blocks.findIndex(b => b.id === blockId);

  const next = [...blocks];

  const upsert = (block: TraceBlock) => {
    if (index === -1) next.push(block);
    else next[index] = block;
  };

  switch (blockId) {
    case "intent":
      if (!trace.payload?.intent) return prev;
      upsert({ id: "intent", type: "intent", intent: trace.payload.intent });
      break;

    case "candidates":
      if (!Array.isArray(trace.payload?.nodes)) return prev;
      upsert({ id: "candidates", type: "candidates", nodes: trace.payload.nodes });
      break;

    case "plan":
      upsert({
        id: "plan",
        type: "plan",
        nodes: trace.payload?.connected_nodes || [],
      });
      break;

    case "workflow":
      if (!trace.payload?.workflow) return prev;
      upsert({
        id: "workflow",
        type: "workflow",
        workflow: trace.payload.workflow,
      });
      break;

    case "judgement":
      upsert({
        id: "judgement",
        type: "judgement",
        ...trace.payload,
      });
      break;

    case "repaired_workflow":
      if (!trace.payload?.workflow) return prev;
      upsert({
        id: "repaired_workflow",
        type: "repaired_workflow",
        workflow: trace.payload.workflow,
      });
      break;
  }

  return { ...prev, [key]: next };
}
