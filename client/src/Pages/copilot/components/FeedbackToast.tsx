import type { FeedbackState } from "../types";

interface FeedbackToastProps {
  feedback: FeedbackState;
  onYes: () => void;
  onNo: () => void;
}

export function FeedbackToast({
  feedback,
  onYes,
  onNo,
}: FeedbackToastProps) {
  return (
    <div
      className={`copilot-feedback ${
        feedback.open
          ? "copilot-feedback--visible"
          : "copilot-feedback--hide"
      }`}
    >
      <div className="copilot-feedback-body">
        <p>{feedback.message}</p>

        {feedback.status === "pending" && (
          <div className="copilot-feedback-actions">
            <button type="button" onClick={onYes}>
              Yes
            </button>
            <button type="button" onClick={onNo}>
              No
            </button>
          </div>
        )}
      </div>
    </div>
  );
}
