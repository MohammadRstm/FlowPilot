import { useMutation } from "@tanstack/react-query";
import type { ConfirmWorkflowPayload, ConfirmWorkflowResponse } from "../../../api/copilot/types";
import { confirmWorkflow } from "../../../api/copilot/confirmWorkflow";

export const useConfirmWorkflowMutation = () => {
  return useMutation<ConfirmWorkflowResponse, unknown, ConfirmWorkflowPayload>({
    mutationFn: (payload: ConfirmWorkflowPayload) => confirmWorkflow(payload),
  });
};
