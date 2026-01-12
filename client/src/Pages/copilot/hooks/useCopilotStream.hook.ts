import { useRef } from "react";
import { streamCopilotQuestion } from "../../../api/copilot.api";
import type { GenerationStage, ChatMessage } from "../Copilot.types";
import { typingBarrier } from "../components/tracer/typingBarrier";

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
}){

  const streamRef = useRef<EventSource | null>(null);// holds SSE connection
  const stageQueue = useRef<GenerationStage[]>([]);// holds queue for tracing
  const processing = useRef(false);// tracks whether a stage is currently being handeld

  const waitForTyping = () => typingBarrier.wait();// typingBarrier sends a signal when a stage has finished being typed on the screen

  const processQueue = async (key: number | "new") => {
    processing.current = true;// set processing on

    while (stageQueue.current.length){// while queue not empty
      const next = stageQueue.current.shift()!;
      onStage(next);// update UI stage
      onProgress(key, next);// update per session stage

      await waitForTyping();// wait for the current stage to finish being typed before moving on
    }

    processing.current = false;// set processing off
  };

  const enqueueStage = (s: GenerationStage, key: number | "new") =>{// push an incoming stage into queue
    stageQueue.current.push(s);
    if (!processing.current){// only initiate processing if their is no stage being processed
      processQueue(key);
    }
  };

  const cancel = () =>{// closes SSE connection
    streamRef.current?.close();
    streamRef.current = null;
  };

  const run = (// running the stream
    messages: ChatMessage[],
    historyId: number | null,
    key : number | "new" = "new"
  ) => {
    streamRef.current?.close();// close any previous connections

    // reset states
    stageQueue.current = [];
    processing.current = false;

    enqueueStage("analyzing", key);// immediatley enqueue analyzing (no need to make user wait)
    streamRef.current = streamCopilotQuestion(// open SSE connection
        messages,// user question(s)
        historyId,// to what history this conversation belongs to
        // pass call backs
        (stage) =>{// pushes stage
            const typed = stage as GenerationStage;
            enqueueStage(typed, key);
        },
        (trace) =>{// calls onTrace handler defined in Copilot.tsx
            onTrace(key, trace);
        },
        (answer, historyId) =>{// calls onComplete handler defined in Copilot.tsx
            onComplete(answer, historyId);
        }
    );
  };

  return { run , cancel };
}
