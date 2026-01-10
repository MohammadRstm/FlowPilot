import { useMutation } from "@tanstack/react-query";
import { confirmWorkflow, type ConfirmWorkflowPayload, type ConfirmWorkflowResponse } from "../../../api/copilot.api";

export const useConfirmWorkflowMutation = () => {
  return useMutation<ConfirmWorkflowResponse, unknown, ConfirmWorkflowPayload>({
    mutationFn: (payload: ConfirmWorkflowPayload) => confirmWorkflow(payload),
  });
};
