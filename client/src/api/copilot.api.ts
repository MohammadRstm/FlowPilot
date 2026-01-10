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

export interface CopilotMessage {
  id: number;
  history_id: number;
  user_message: string;
  ai_response: WorkflowAnswer;
  ai_model: string | number | null;
  created_at: string;
  updated_at: string;
}

export interface CopilotHistory {
  id: number;
  user_id: number;
  created_at: string;
  updated_at: string;
  messages: CopilotMessage[];
}

export interface CopilotResponse {
  message: string;
  data: {
    answer: WorkflowAnswer;
    historyId: number;
  };
}

const BASE_URL = import.meta.env.VITE_BASE_URL;
const prefix = "copilot";

const url = BASE_URL + "/" + prefix;

export const sendCopilotQuestion = async (
  messages: ChatMessage[],
  historyId?: number | null
): Promise<CopilotResponse> => {
  const response = await axios.post<CopilotResponse>(
    `${url}/ask`,
    {
      messages,
      history_id: historyId ?? null,
    }
  );
  console.log(response);
  return response.data;
};

export interface CopilotHistoriesResponse {
  message: string;
  data: {
    histories: CopilotHistory[];
  };
}

export const fetchCopilotHistories = async (): Promise<CopilotHistory[]> => {
    const response = await axios.get<CopilotHistoriesResponse>(`${url}/histories`);
    return response.data.data.histories;
};

export const deleteCopilotHistory = async (id: number): Promise<void> => {
  await axios.delete(`${url}/histories/${id}`);
};
