import { useMutation } from "@tanstack/react-query";
import { sendCopilotQuestion } from "../../../api/copilot.api";
import type { WorkflowAnswer } from "../../../api/copilot.api";
import type { ChatMessage } from "../../../Pages/Copilot";

export const useCopilotMutation = (
  onSuccess?: (answer: WorkflowAnswer) => void
) => {
  return useMutation({
    mutationFn: (question: ChatMessage[]) =>
      sendCopilotQuestion(question),

    onSuccess: (data) => {
      onSuccess?.(data.data.answer);
    },
  });
};
