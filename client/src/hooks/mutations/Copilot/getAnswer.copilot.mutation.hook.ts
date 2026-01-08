import { useMutation } from "@tanstack/react-query";
import { sendCopilotQuestion } from "../../../api/copilot.api";
import type { WorkflowAnswer } from "../../../api/copilot.api";

export const useCopilotMutation = (
  onSuccess?: (answer: WorkflowAnswer) => void
) => {
  return useMutation({
    mutationFn: (question: string) =>
      sendCopilotQuestion({ question }),

    onSuccess: (data) => {
      onSuccess?.(data.data.answer);
    },
  });
};
