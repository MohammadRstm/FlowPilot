import { useRef } from "react";
import type { GenerationStage, ChatMessage } from "../Copilot.types";
import { streamCopilotQuestion } from "../../../api/copilot/streamResponse";

export function useCopilotStream({
  onStage,
  onProgress,
  onTrace,
  onComplete,
}: {
  onStage: (s: GenerationStage) => void;
  onProgress: (key: number | "new", label: GenerationStage) => void;
  onTrace: (key: number | "new", trace: any) => void;
  onComplete: (answer: any, historyId: number) => void;
}) {
  const streamRef = useRef<EventSource | null>(null); // SSE connection
  const runIdRef = useRef(0);

    const cancel = () => {
        streamRef.current?.close();
        streamRef.current = null;
    };


  const run = (
    messages: ChatMessage[],
    historyId: number | null,
    key: number | "new" = "new"
  ) => {
    runIdRef.current += 1;
    const id =runIdRef.current;
    // close any previous SSE connection
    streamRef.current?.close();

    // immediately enqueue "analyzing" stage
    onStage("analyzing");
    // open new SSE connection
    streamRef.current = streamCopilotQuestion(
      messages,
      historyId,
      (stage) => {
        onStage(stage as GenerationStage);
        onProgress(key, stage as GenerationStage);
      },
      (trace) => {
        if (id !== runIdRef.current) return;
        onTrace(key, trace);
      },
      (answer, historyId) => {
        if (id !== runIdRef.current) return;
        onComplete(answer, historyId);
      }
    );
  };
  const runId = runIdRef.current;
  return { run, cancel , runId};
}
