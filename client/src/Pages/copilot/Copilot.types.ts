import type { WorkflowAnswer } from "../../api/copilot.api";
import type { PlanNode } from "./Copilot.constants";

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

export interface FeedbackState{
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

export type TraceBlock =
  | {
      id: "intent";
      type: "intent";
      intent: string;
    }
  | {
      id: "candidates";
      type: "candidates";
      nodes: string[];
    }
  | {
      id: "plan";
      type: "plan";
      nodes: {
        name: string;
        role: string;
        from: string | null;
      }[];
    }
  | {
      id: "workflow";
      type: "workflow";
      workflow: any;
    }
  | { id: string; type: "judgement"; capabilities: any[]; errors?: any[]; matches?: any[]; requirements?: any[] }
  |{
      id: "repaired_workflow";
      type: "repaired_workflow";
      workflow: any;
    };

