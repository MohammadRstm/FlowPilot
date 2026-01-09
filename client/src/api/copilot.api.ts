import axios from "axios";
import type { ChatMessage } from "../Pages/Copilot";

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


export interface CopilotResponse {
  message: string;
  data: {
    answer: WorkflowAnswer;
  };
}

const BASE_URL = import.meta.env.VITE_BASE_URL;
const prefix = 'copilot';

const url = BASE_URL + '/' + prefix;

export const sendCopilotQuestion = async (
  payload: ChatMessage[]
): Promise<CopilotResponse> => {
  const response = await axios.post<CopilotResponse>(
    `${url}/ask`, 
    {
        messages : payload
    }
  );

  return response.data;
};
