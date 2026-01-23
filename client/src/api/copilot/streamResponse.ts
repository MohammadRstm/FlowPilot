import type { ToastType } from "../../Pages/components/toast/toast.types";
import type { ChatMessage } from "../../Pages/copilot/Copilot.types";
import { BASE_URL, type WorkflowAnswer } from "./types";

export const streamCopilotQuestion = (
  userId:number,
  messages: ChatMessage[],
  showToast: (message : string , type : ToastType | undefined)=> void,
  historyId?: number | null,
  onStage?: (stage: string) => void,
  onTrace?: (trace: any) => void,
  onResult?: (answer: WorkflowAnswer, historyId: number) => void,
  onError?: () => void
)=>{
  const params = new URLSearchParams();
  params.append("messages", JSON.stringify(messages));
  params.append("userId" , userId.toString());
  if (historyId) params.append("history_id", historyId.toString());

  const evt = new EventSource(`${BASE_URL}/ask-stream?${params}`);
  
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

  evt.addEventListener("error", (e: MessageEvent) => {
    try {
      const data = JSON.parse(e.data);
      showToast(data.message ?? "Stream error", "error");
    } catch {
      showToast("Stream connection failed", "error");
    }
    onError?.();// reset UI
    evt.close();
  });


  evt.onerror = () =>{
    onError?.();
    evt.close();
  };

  return evt;
};