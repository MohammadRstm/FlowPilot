import { STAGE_LABELS } from "../constants";
import type { GenerationStage } from "../types";

export function ProgressMessage({ stage }: { stage: GenerationStage }) {
  if (stage === "idle" || stage === "done") return null;

  return (
    <div className="copilot-progress">
      <span
        key={stage}
        className={`copilot-progress-text shimmer ${stage}`}
      >
        {STAGE_LABELS[stage]}
        <span className="dots">
          <span>.</span>
          <span>.</span>
          <span>.</span>
        </span>
      </span>
    </div>
  );
}
