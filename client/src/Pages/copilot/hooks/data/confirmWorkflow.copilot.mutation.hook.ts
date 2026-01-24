import { useMutation } from "@tanstack/react-query";
import { api } from "../../../../api/client";
import type { ConfirmWorkflowPayload, ConfirmWorkflowResponse } from "./types";

export const useConfirmWorkflowMutation = () => {
  return useMutation<ConfirmWorkflowResponse, unknown, ConfirmWorkflowPayload>({
    mutationFn: (payload: ConfirmWorkflowPayload) => confirmWorkflow(payload),
  });
};


const confirmWorkflow = async (
  payload: ConfirmWorkflowPayload
): Promise<ConfirmWorkflowResponse> => {
  const response = await api.post<ConfirmWorkflowResponse>(
    `auth/copilot/satisfied`,
    payload
  );

  return response.data;
};
