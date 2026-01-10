import { useMutation } from "@tanstack/react-query";
import { sendCopilotQuestion } from "../../../api/copilot.api";
import type { WorkflowAnswer, CopilotResponse } from "../../../api/copilot.api";
import type { ChatMessage } from "../../../Pages/Copilot";

export interface CopilotMutationPayload {
  messages: ChatMessage[];
  historyId?: number | null;
}

export const useCopilotMutation = (
  onSuccess?: (answer: WorkflowAnswer, historyId: number) => void
) => {
  return useMutation<CopilotResponse, unknown, CopilotMutationPayload>({
    mutationFn: ({ messages, historyId }) =>
      sendCopilotQuestion(messages, historyId),

    onSuccess: (data) => {
      onSuccess?.(data.data.answer, data.data.historyId);
    },
  });
};
