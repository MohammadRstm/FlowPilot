import { useRef } from "react";
import { streamCopilotQuestion } from "../../../api/copilot.api";
import type { GenerationStage, ChatMessage } from "../Copilot.types";

export function useCopilotStream({
  onStage,
  onProgress,
  onComplete,
}: {
  onStage: (s: GenerationStage) => void;
  onProgress: (key : number | "new", label: GenerationStage) => void;
  onComplete: (answer: any, historyId: number) => void;
}) {
  const streamRef = useRef<EventSource | null>(null);

  const run = (
    messages: ChatMessage[],
    historyId: number | null,
    key : number | "new" = "new"
  ) => {
    streamRef.current?.close();

    onStage("analyzing");
    onProgress(key , "analyzing");

    streamRef.current = streamCopilotQuestion(
      messages,
      historyId,
      (stage) => {
        const typed = stage as GenerationStage;
        onStage(typed);
        onProgress(key ,typed);
      },
      (answer , historyId) =>{
          onComplete(answer , historyId)
      }
    );
  };

  return { run };
}
