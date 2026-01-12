import type { WorkflowAnswer } from "../../api/copilot.api";

export type GenerationStage =
  | "idle"
  | "analyzing"
  | "retrieving"
  | "ranking"
  | "generating"
  | "validating"
  | "done";

export type ChatMessage =
  | { type: "user"; content: string }
  | {
      type: "assistant";
      content: string;
      fileUrl?: string;
      fileName?: string;
      isStreaming?: boolean;
      canRetry?: boolean;
    };

export type FeedbackStatus = "pending" | "thanks" | "sorry";

export interface FeedbackState {
  open: boolean;
  status: FeedbackStatus;
  message: string;
  question: string;
  workflow: WorkflowAnswer;
}

export type CopilotHistoryMessage = {
  user_message: string;
  ai_response: WorkflowAnswer; 
};


export interface TraceEvent {
  type: "analyzing" | "retrieval" | "ranking" | "generation" | "validation";
  payload: any;
}
