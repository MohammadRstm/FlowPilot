import Header from "../components/Header";
import "../../styles/Copilot.css";
import { useEffect, useRef, useState } from "react";

import { ChatView } from "./components/ChatView";
import { ChatInput } from "./components/ChatInput";
import { FeedbackToast } from "./components/FeedbackToast";

import type {
  GenerationStage,
  TraceBlock,
} from "./Copilot.types";

import { useCopilotChatController } from "./hooks/useCopilotChat.hook";
import { useCopilotStream } from "./hooks/useCopilotStream.hook";
import { useCopilotFeedback } from "./hooks/useCopilotFeedback.hook";
import { applyTrace } from "./utils/traceAdapter";
import { buildWorkflowFile, commitHistory, finalizeAssistantMessage } from "./utils/onComplete";
import { useCopilotHistoryController } from "./hooks/useCopilotHistoryController.hook";
import { HistoryPanel } from "./components/HistoryPanel/HistoryPanel";

export const Copilot =() => {
  const [question, setQuestion] = useState("");
  const [stage, setStage] = useState<GenerationStage>("idle");// lets the UI know where the UI is in the generation process
  const [currentHistoryId, setCurrentHistoryId] = useState<number | null>(null);

  const activeKey = currentHistoryId ?? "new";
  const textareaRef = useRef<HTMLTextAreaElement>(null);
  const chatRef = useRef<HTMLDivElement>(null);

  const [activeGenerationKey, setActiveGenerationKey] =
  useState<number | "new" | null>(null);

  const [traceBlocks, setTraceBlocks] = useState<Record<number | "new", TraceBlock[]>>(
    { new: [] }
  );


  // streaming hook
  const { run , cancel , runId} = useCopilotStream({// this hook requires three call back functions
    onStage: setStage,// tracks current stage of generation
    onProgress: (key : number | "new", stage: GenerationStage) => {
      upsertStreamingAssistant(key, stage); // updates chat messages reflecting the new stage
    },
    onTrace: (key, trace) => {
      setTraceBlocks(prev => applyTrace(prev , key , trace));
    },
    onComplete: (answer, newHistoryId) =>handleStreamComplete(answer , newHistoryId)
  });

  // copilot chat hook
  const {
    messageStore,
    setMessageStore,
    upsertStreamingAssistant,
    submit,
    cancelGeneration,
    retry,
  } = useCopilotChatController({
    run,
    cancel,
    setStage,
    setQuestion,
    setActiveGenerationKey,
    setTraceBlocks,
    currentHistoryId,
  });
  const messages = messageStore[activeKey] ?? [];

  // feedback hook
  const {
    feedback,
    openFeedback,
    confirmYes,
    confirmNo,
  } = useCopilotFeedback();

  // history hook
  const history = useCopilotHistoryController({
    currentHistoryId,
    activeGenerationKey,
    cancel,
    setCurrentHistoryId,
    setStage,
    setQuestion,
    setMessageStore,
    setTraceBlocks,
    setActiveGenerationKey,
  });

  // scroll effect
  useEffect(() => {
    chatRef.current?.scrollTo({
      top: chatRef.current.scrollHeight,
      behavior: "smooth",
    });
  }, [messages]);

  // Handlers
  const handleTextareaChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
    setQuestion(e.target.value);

    const el = textareaRef.current;
    if (!el) return;

    el.style.height = "auto";
    el.style.height = `${Math.min(el.scrollHeight, 66)}px`;
    el.style.overflowY = el.scrollHeight > 66 ? "auto" : "hidden";
  };

  const handleStreamComplete = (answer: any, newHistoryId: number) => {
    const key = currentHistoryId ?? "new";

    const file = buildWorkflowFile(answer);

    setMessageStore(prev => {
      const msgs = prev[key] ?? [];
      const updated = finalizeAssistantMessage(
        msgs,
        file.url,
        file.name
      );

      const commited = commitHistory(prev, newHistoryId, updated);
      delete commited["new"];
      
      return commited;
    });

    setCurrentHistoryId(newHistoryId);
    setStage("done");
    setActiveGenerationKey(null);
    openFeedback(question, answer);
  };

  
  return (
    <div className="copilot-page">
      <Header />
      <section className="copilot-hero">
        <div className="copilot-layout-root">
          <HistoryPanel
            histories={history.histories}
            loading={history.loading}
            currentHistoryId={currentHistoryId}
            onNewChat={history.newChat}
            onSelect={history.selectHistory}
            onDelete={history.deleteHistory}
          />

          <div className="copilot-main">
            {stage === "idle" ? (
              <div className="copilot-content">
                <h1>Where the magic happens</h1>
                <div className="copilot-input-wrapper">
                <input
                  value={question}
                  placeholder="Tell us what you want to build"
                  onChange={(e) => setQuestion(e.target.value)}
                  onKeyDown={(e) => e.key === "Enter" && submit(question)}
                />
                </div>
              </div>
            ) : (
              <>
                <ChatView
                  messages={messages}
                  chatRef={chatRef}
                  traces={traceBlocks[activeKey] || []}
                  activeGeneration={activeGenerationKey === activeKey}
                  stage={stage}
                  runId={runId}
                  onCancel={cancelGeneration}
                  onRetry={retry}
                />

                <ChatInput
                  value={question}
                  textareaRef={textareaRef}
                  disabled={activeGenerationKey !== null}
                  onChange={handleTextareaChange}
                  onSubmit={() => submit(question)}
                />
              </>
            )}
          </div>
        </div>

        {feedback && (
        <FeedbackToast
          feedback={feedback}
          onYes={confirmYes}
          onNo={confirmNo}
        />
        )}
      </section>
    </div>
  );
};
