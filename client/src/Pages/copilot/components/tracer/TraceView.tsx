import type { TraceBlock } from "../../Copilot.types";
import { N8nPlan } from "./N8NPlan";
import { TypedLine } from "./typedLine";
import { TypedList } from "./TypedList";
import { WorkflowPreview } from "./WorkflowPreview";

export function TraceView({ traces }: { traces: TraceBlock[] }) {
  return (
    <div className="trace-panel">
      {traces.map(block => {
        switch (block.type) {
          case "intent":
            return (
              <div key={block.id} className="trace-block">
                <strong><TypedLine value={`I interpret your goal as:`} /></strong><br />
                <TypedLine value={block.intent} />
              </div>
            );

          case "candidates":
            return (
              <div key={block.id} className="trace-block">
                <strong><TypedLine value={`Candidate Nodes (${block.nodes.length})`} /></strong>
                <TypedList
                  items={block.nodes}
                  renderItem={n => <TypedLine value={n} />}
                />
              </div>
            );

         case "plan":
            return (
                <div key={block.id} className="trace-block">
                <strong><TypedLine value="Execution Plan" /></strong><br />
                <N8nPlan nodes={block.nodes} />
                </div>
            );

          case "workflow":
            return (
                <div key={block.id} className="trace-block">
                <strong><TypedLine value="Workflow ready. You can inspect or download it below." /></strong><br />
                <WorkflowPreview workflow={block.workflow} />
                </div>
            );
            case "judgement":
                return (
                <div key={block.id} className="trace-block judgement-block">
                    <strong><TypedLine value="Judgement Report" /></strong>
                    
                    {block.capabilities?.length > 0 && (
                    <>
                        <TypedLine value="Capabilities:" />
                        <TypedList
                        items={block.capabilities}
                        renderItem={c => <TypedLine value={`[${c.id}] ${c.description}`} />}
                        />
                    </>
                    )}

                    {block.errors?.length > 0 && (
                    <>
                        <TypedLine value="Errors:" />
                        <TypedList
                        items={block.errors}
                        renderItem={e => <TypedLine value={`[${e.severity}] ${e.message}`} />}
                        />
                    </>
                    )}

                    {block.requirements?.length > 0 && (
                    <>
                        <TypedLine value="Requirements:" />
                        <TypedList
                        items={block.requirements}
                        renderItem={r => <TypedLine value={`[${r.id}] ${r.description}`} />}
                        />
                    </>
                    )}
                </div>
                );
                case "repaired_workflow":
                return (
                    <div key={block.id} className="trace-block">
                    <strong><TypedLine value="Workflow was repaired. Updated version below:" /></strong><br />
                    <WorkflowPreview workflow={block.workflow} />
                    </div>
                );
            default:
                return null;
        }
      })}
    </div>
  );
}
