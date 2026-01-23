import type { WorkflowAnswer } from "./hooks/data/types";

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

export const ChatMessageType = {
  USER: "user",
  ASSISTANT: "assistant",
} as const;

export type ChatMessageType =
  typeof ChatMessageType[keyof typeof ChatMessageType];

export type FeedbackStatus = "pending" | "thanks" | "sorry";

export interface FeedbackState{
  open: boolean;
  status: FeedbackStatus;
  message: string;
  question: string;
  workflow: WorkflowAnswer;
}


export interface TraceEvent {
  type: "analyzing" | "retrieval" | "ranking" | "generation" | "validation";
  payload: any;
}

export type TraceBlock =
  | {
      id: "intent";
      type: typeof TraceEventName.INTENT;
      intent: string;
    }
  | {
      id: "candidates";
      type: typeof TraceEventName.CANDIDATES;
      nodes: string[];
    }
  | {
      id: "plan";
      type: typeof TraceEventName.PLAN;
      nodes: {
        name: string;
        role: string;
        from: string | null;
      }[];
    }
  | {
      id: "workflow";
      type: typeof TraceEventName.WORKFLOW;
      workflow: any;
    }
  | {
      id: string;
      type: typeof TraceEventName.JUDGEMENT;
      capabilities: {
        id: string;
        description: string;
      }[];
      errors: {
        severity: string;
        message: string;
      }[];
      requirements: {
        id: string;
        description: string;
      }[];
    }
  | {
      id: "repaired_workflow";
      type: typeof TraceEventName.REPAIRED_WORKFLOW;
      workflow: any;
    };


export const TraceEventName = {
  JUDGEMENT: "judgement",
  INTENT: "intent",
  CANDIDATES: "candidates",
  PLAN: "plan",
  WORKFLOW: "workflow",
  REPAIRED_WORKFLOW: "repaired_workflow",
} as const;

export type TraceEventName =
  typeof TraceEventName[keyof typeof TraceEventName];