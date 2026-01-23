import { useEffect, useState } from "react";
import type { FeedbackState } from "../../types";
import { useConfirmWorkflowMutation } from "../data/confirmWorkflow.copilot.mutation.hook";
import type { WorkflowAnswer } from "../data/types";

export function useCopilotFeedback() {
  const [feedback, setFeedback] = useState<FeedbackState | null>(null);
  const confirmWorkflowMutation = useConfirmWorkflowMutation();

    useEffect(() => {
        if (!feedback) return;

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
        f
        ? {
            ...f,
            status: "thanks",
            message: "Thank you!",
            open: true,
            }
        : f
    );

    if (feedback?.question && feedback?.workflow) {
        confirmWorkflowMutation.mutate({
        question: feedback.question,
        workflow: feedback.workflow,
        });
    }

    // auto-close
    setTimeout(() => {
        setFeedback((f) => (f ? { ...f, open: false } : f));
    }, 2000);
  };

  const confirmNo = () => {
    setFeedback((f) =>
        f
        ? {
            ...f,
            status: "sorry",
            message: "Sorry to hear that.",
            open: true,
            }
        : f
    );

    // auto-close
    setTimeout(() => {
        setFeedback((f) => (f ? { ...f, open: false } : f));
    }, 2000);
  };

  return {
    feedback,
    openFeedback,
    confirmYes,
    confirmNo,
  };
}
