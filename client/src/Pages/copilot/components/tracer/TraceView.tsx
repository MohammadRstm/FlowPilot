import { useEffect, useState } from "react";
import type { PlanNode } from "../../Copilot.constants";
import { TypedLine } from "./typedLine";
import { KeyValueList } from "./keyValueList";
import { N8nPlan } from "./N8NPlan";
import { WorkflowPreview } from "./WorkflowPreview";
import { typingBarrier } from "./typingBarrier";

export function TraceView({ traces }: { traces: any }) {
  const [showCursor, setShowCursor] = useState(false);

  useEffect(() => {
    typingBarrier.wait().then(() => setShowCursor(false));
    setShowCursor(true);
  }, [traces]);

  if (!traces || Object.keys(traces).length === 0) return null;

  console.log("In chat view " , traces);

  const intentBlock = traces["intent analysis"];
  const candidatesBlock = traces["candidates"]?.candidates;
  const planBlock = traces["genration_plan"];
  const workflowBlock = traces["workflow"]?.workflow;
  const repairedWorkflowBlock = traces["repaired_workflow"]?.workflow;
  const judgementBlock = traces["judgement"];

  const planNodesRaw = planBlock?.connected_nodes;
  const planNodes: PlanNode[] = Array.isArray(planNodesRaw)
    ? planNodesRaw.map((n: any) => ({
        name: n.name ?? n.node ?? "Step",
        role: n.role ?? n.type ?? "step",
        from: n.from ?? null,
      }))
    : [];

  return (
    <div className="trace-panel">
      {intentBlock && (
        <div className="trace-block">
          <div className="trace-title">Understanding your request</div>
          <TypedLine value={`I interpret your goal as: ${intentBlock.intent}`} />
        </div>
      )}

      {candidatesBlock?.nodes?.length > 0 && (
        <div className="trace-block">
            <div className="trace-title">
            Candidate Nodes ({candidatesBlock.nodes.length})
            </div>
            <ul>
            {candidatesBlock.nodes.map((node: string, idx: number) => (
                <li key={idx}>{node}</li>
            ))}
            </ul>
        </div>
        )}


      {planNodes.length > 0 && (
        <div className="trace-block">
          <div className="trace-title">Workflow plan</div>
          <N8nPlan nodes={planNodes} />
        </div>
      )}

      {workflowBlock && <WorkflowPreview workflow={workflowBlock} />}
      {repairedWorkflowBlock && <WorkflowPreview workflow={repairedWorkflowBlock} />}
      {judgementBlock && <KeyValueList data={judgementBlock} />}

      {showCursor && <div className="global-cursor">▌</div>}
    </div>
  );
}
