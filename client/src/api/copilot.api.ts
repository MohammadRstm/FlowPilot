import axios from "axios";

export interface WorkflowAnswer {
  name: string;
  nodes: unknown[];
  connections: unknown;
  settings: unknown[];
  staticData: unknown;
  meta: {
    instanceId: string;
  };
}

export interface CopilotRequest {
  question: string;
}


export interface CopilotResponse {
  message: string;
  data: {
    answer: WorkflowAnswer;
  };
}

export const sendCopilotQuestion = async (
  payload: CopilotRequest
): Promise<CopilotResponse> => {
  const response = await axios.post<CopilotResponse>(
    "/api/copilot", 
    payload
  );

  return response.data;
};
