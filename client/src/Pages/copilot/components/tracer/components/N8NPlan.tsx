import { useEffect, useState } from "react";
import type { PlanNode } from "../../../Copilot.constants";

function PlanRow({ node }: { node: PlanNode }) {
  return (
    <div className="plan-row">
      <div className={`n8n-node ${node.role}`}>
        {node.name}
        <span className="node-role">{node.role}</span>
      </div>
      {node.from && <div className="plan-connection">← from <strong>{node.from}</strong></div>}
    </div>
  );
}

export function N8nPlan({ nodes }: { nodes: PlanNode[] }) {
  const [visible, setVisible] = useState<PlanNode[]>([]);

  useEffect(() => {
    if (!nodes?.length) return;

    setVisible([]);

    let i = 0;
    const timer = setInterval(() => {
      setVisible(prev => {
        if (i >= nodes.length) {
          clearInterval(timer);
          return prev;
        }
        return [...prev, nodes[i++]];
      });
    }, 350);

    return () => {
      clearInterval(timer);
    };
  }, [nodes]);

  return (
    <div className="n8n-plan">
      {visible.map((n, i) => <PlanRow key={i} node={n} />)}
    </div>
  );
}
