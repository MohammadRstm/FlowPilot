import { useEffect, useState } from "react";
import type { FeedbackState } from "../Copilot.types";
import type { WorkflowAnswer } from "../../../api/copilot.api";
import { useConfirmWorkflowMutation } from "../../../hooks/mutations/Copilot/confirmWorkflow.copilot.mutation.hook";

export function useCopilotFeedback() {
  const [feedback, setFeedback] = useState<FeedbackState | null>(null);
  const confirmWorkflowMutation = useConfirmWorkflowMutation();

  useEffect(() => {
    if (!feedback) return;

    if (feedback.open && feedback.status === "pending") {
      const t = setTimeout(
        () => setFeedback((f) => (f ? { ...f, open: false } : f)),
        10_000
      );
      return () => clearTimeout(t);
    }

    if (!feedback.open) {
      const t = setTimeout(() => setFeedback(null), 400);
      return () => clearTimeout(t);
    }
  }, [feedback]);

  const openFeedback = (
    question: string,
    workflow: WorkflowAnswer
  ) => {
    setFeedback({
      open: true,
      status: "pending",
      message: "Are you satisfied with the generated workflow?",
      question,
      workflow,
    });
  };

  const confirmYes = () => {
    setFeedback((f) =>
      f ? { ...f, status: "thanks", message: "Thank you!" } : f
    );

    if (feedback?.question && feedback?.workflow) {
      confirmWorkflowMutation.mutate({
        question: feedback.question,
        workflow: feedback.workflow,
      });
    }
  };

  const confirmNo = () => {
    setFeedback((f) =>
      f ? { ...f, status: "sorry", message: "Sorry to hear that." } : f
    );
  };

  return {
    feedback,
    openFeedback,
    confirmYes,
    confirmNo,
  };
}
