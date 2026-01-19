import { url, type ConfirmWorkflowPayload, type ConfirmWorkflowResponse } from "./types";
import { api } from "../client";

export const confirmWorkflow = async (
  payload: ConfirmWorkflowPayload
): Promise<ConfirmWorkflowResponse> => {
  const response = await api.post<ConfirmWorkflowResponse>(
    `${url}/satisfied`,
    payload
  );

  return response.data;
};
