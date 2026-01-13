import axios from "axios";
import { url, type ConfirmWorkflowPayload, type ConfirmWorkflowResponse } from "./types";

export const confirmWorkflow = async (
  payload: ConfirmWorkflowPayload
): Promise<ConfirmWorkflowResponse> => {
  const response = await axios.post<ConfirmWorkflowResponse>(
    `${url}/satisfied`,
    payload
  );

  return response.data;
};
