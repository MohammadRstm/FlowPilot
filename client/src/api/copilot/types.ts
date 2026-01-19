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

export interface CopilotHistoriesResponse {
    message: string;
    data: {
        histories: CopilotHistory[];
    };
}

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

const BASE_URL = import.meta.env.VITE_BASE_URL;
const prefix = "copilot";

export const url = BASE_URL + "/auth/" + prefix;