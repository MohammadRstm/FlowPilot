import type { ChatMessage } from "../../Pages/copilot/Copilot.types";
import { url, type WorkflowAnswer } from "./types";

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