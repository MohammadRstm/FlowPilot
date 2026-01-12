import { useState } from "react";

export function WorkflowPreview({ workflow }: { workflow: any }) {
  const [open, setOpen] = useState(false);
  if (!workflow) return null;

  const nodes = workflow.nodes || [];
  const triggers = nodes.filter((n: any) => n.type?.includes("trigger"));
  const actions = nodes.filter((n: any) => !n.type?.includes("trigger"));

  return (
    <div className="workflow-preview">
      <div className="workflow-header" onClick={() => setOpen(!open)}>
        <strong>{workflow.name || "Workflow"}</strong>
        <span>{nodes.length} nodes</span>
        <span>{triggers.length} triggers</span>
        <span>{actions.length} actions</span>
      </div>

      {open && <pre className="workflow-json">{JSON.stringify(workflow, null, 2)}</pre>}
    </div>
  );
}
