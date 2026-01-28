import { useRef } from "react";
import type { GenerationStage, ChatMessage } from "../../types";
import { useToast } from "../../../../context/toastContext";
import { backgroundStreamService } from "../../services/backgroundStreamService";

export function useCopilotStream({
  onStage,
  onProgress,
  onTrace,
  onComplete,
  onError
}: {
  onStage: (s: GenerationStage) => void;
  onProgress: (key: number | "new", label: GenerationStage) => void;
  onTrace: (key: number | "new", trace: any) => void;
  onComplete: (answer: any, historyId: number) => void;
  onError: () => void;
}) {
  const { showToast } = useToast();
  const runIdRef = useRef(0);
  const currentKeyRef = useRef<number | "new">("new");

  const cancel = () => {
    backgroundStreamService.stopStream(currentKeyRef.current);
  };

  const run = (
    messages: ChatMessage[],
    historyId: number | null,
    key: number | "new" = "new",
    userId: number
  ) => {
    runIdRef.current += 1;
    const id = runIdRef.current;
    currentKeyRef.current = key;

    // immediately enqueue "analyzing" stage
    onStage("analyzing");

    // Use background stream service - stream continues even after navigation
    backgroundStreamService.startStream(
      key,
      messages,
      historyId,
      userId,
      (message: string, type?: string) => showToast(message, type as any),
      {
        onStage: (_streamKey, stage) => {
          if (id !== runIdRef.current) return;
          onStage(stage);
        },
        onProgress: (_streamKey, stage) => {
          if (id !== runIdRef.current) return;
          onProgress(_streamKey, stage);
        },
        onTrace: (_streamKey, trace) => {
          if (id !== runIdRef.current) return;
          onTrace(_streamKey, trace);
        },
        onComplete: (_streamKey, answer, historyId) => {
          if (id !== runIdRef.current) return;
          onComplete(answer, historyId);
        },
        onError: (_streamKey) => {
          if (id !== runIdRef.current) return;
          onError();
        },
      }
    );
  };

  const runId = runIdRef.current;
  return { run, cancel, runId };
}
