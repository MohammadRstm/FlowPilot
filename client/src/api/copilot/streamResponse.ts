import type { ChatMessage } from "../../Pages/copilot/Copilot.types";
import { url, type WorkflowAnswer } from "./types";

export const streamCopilotQuestion = (
  messages: ChatMessage[],
  historyId?: number | null,
  onStage?: (stage: string) => void,
  onTrace?: (trace: any) => void,
  onResult?: (answer: WorkflowAnswer, historyId: number) => void
) => {
  const params = new URLSearchParams();
  params.append("messages", JSON.stringify(messages));
  if (historyId) params.append("history_id", historyId.toString());

  const evt = new EventSource(`${url}/ask-stream?${params}`);
  
  evt.addEventListener("stage", (e) =>{
    onStage?.(e.data);
  });

  evt.addEventListener("trace", (e) => {
    onTrace?.(JSON.parse(e.data));
  });

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