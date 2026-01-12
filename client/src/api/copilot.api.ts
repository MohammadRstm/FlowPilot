import axios from "axios";
import type { ChatMessage } from "../Pages/copilot/Copilot.types";

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

export const streamCopilotQuestion = (// calls streaming endpoint
  messages: ChatMessage[],
  historyId?: number | null,
  onStage?: (stage: string) => void,
  onTrace?: (trace: any) => void,
  onResult?: (answer: WorkflowAnswer, historyId: number) => void// known as onComplete in other files
) => {
  // sending query params (GET)
  const params = new URLSearchParams();
  params.append("messages", JSON.stringify(messages));
  if (historyId) params.append("history_id", historyId.toString());

  const evt = new EventSource(`${url}/ask-stream?${params}`);// EventSource is a browser API used for SSE connections,Opens a long-lived HTTP connection to your Laravel backend /ask-stream
  
  // handle stage events
  evt.addEventListener("stage", (e) =>{
    onStage?.(e.data);
  });

  // handles trace events
  evt.addEventListener("trace", (e) => {
    onTrace?.(JSON.parse(e.data));
  });

  // handles on complete event (last workflow json sent)
  evt.addEventListener("result", (e) => {
    const parsed = JSON.parse(e.data);
    onResult?.(parsed.answer, parsed.history_id);
    evt.close();// kill connection here we are done
  });

  evt.onerror = () =>{
    evt.close();
  };

  return evt;
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

// --- Workflow satisfaction ---
export interface ConfirmWorkflowPayload {
  question: string;
  workflow: WorkflowAnswer;
}

export interface ConfirmWorkflowResponse {
  message: string;
  data: {
    message: string;
  };
}

export const confirmWorkflow = async (
  payload: ConfirmWorkflowPayload
): Promise<ConfirmWorkflowResponse> => {
  const response = await axios.post<ConfirmWorkflowResponse>(
    `${url}/satisfied`,
    payload
  );

  return response.data;
};
