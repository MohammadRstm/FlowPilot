import Header from "../components/Header";
import "../../styles/Copilot.css";
import { useEffect, useRef, useState } from "react";

import { useCopilotHistoriesQuery } from "../../hooks/queries/Copilot/getHistories.copilot.query.hook";
import { useDeleteCopilotHistoryMutation } from "../../hooks/mutations/Copilot/deleteHistory.copilot.mutation.hook";

import { ChatView } from "./components/ChatView";
import { ChatInput } from "./components/ChatInput";
import { HistoryPanel } from "./components/HistoryPanel";
import { FeedbackToast } from "./components/FeedbackToast";

import type {
  ChatMessage,
  GenerationStage,
  TraceBlock,
} from "./Copilot.types";

import type { CopilotHistory } from "../../api/copilot.api";

import { useCopilotChat } from "./hooks/useCopilotChat.hook";
import { useCopilotStream } from "./hooks/useCopilotStream.hook";
import { useCopilotFeedback } from "./hooks/useCopilotFeedback.hook";


export const Copilot =() => {
  const [question, setQuestion] = useState("");
  const [stage, setStage] = useState<GenerationStage>("idle");// lets the UI know where the UI is in the generation process
  const [currentHistoryId, setCurrentHistoryId] = useState<number | null>(null);

  const activeKey = currentHistoryId ?? "new";
  const textareaRef = useRef<HTMLTextAreaElement>(null);
  const chatRef = useRef<HTMLDivElement>(null);

  const { data: histories,  isLoading: historiesLoading } = useCopilotHistoriesQuery();
  const deleteHistoryMutation = useDeleteCopilotHistoryMutation();

  const [activeGenerationKey, setActiveGenerationKey] =
  useState<number | "new" | null>(null);

  const [traceBlocks, setTraceBlocks] = useState<
    Record<number | "new", TraceBlock[]>
  >({});

  // hooks
  const {
    messageStore,
    setMessageStore,
    upsertStreamingAssistant,
  } = useCopilotChat();
  const messages = messageStore[activeKey] ?? [];

  const { run , cancel , runId} = useCopilotStream({// this hook requires three call back functions
    onStage: setStage,// tracks current stage of generation
    onProgress: (key : number | "new", stage: GenerationStage) => {
      upsertStreamingAssistant(key, stage); // updates chat messages reflecting the new stage
    },
 onTrace: (key, trace) => {
  console.log(trace);
  setTraceBlocks(prev => {
    const blocks = prev[key] ?? [];

    // normalize backend types
    let blockId: TraceBlock["type"] | null = null;

    if (trace.type === "intent analysis") blockId = "intent";
    if (trace.type === "candidates") blockId = "candidates";
    if (trace.type === "genration_plan") blockId = "plan";
    if (trace.type === "workflow") blockId = "workflow";
    if(trace.type === "judgement") blockId = "judgement";
    if (trace.type === "repaired_workflow") blockId = "repaired_workflow";

    if (!blockId) return prev;

    const index = blocks.findIndex(b => b.id === blockId);

    // ---------- INTENT ----------
    if (blockId === "intent") {
      const intent = trace.payload?.intent;
      if (!intent) return prev;

      const block: TraceBlock = {
        id: "intent",
        type: "intent",
        intent,
      };

      if (index === -1) {
        return { ...prev, [key]: [...blocks, block] };
      }

      const next = [...blocks];
      next[index] = block;
      return { ...prev, [key]: next };
    }

    // ---------- CANDIDATES ----------
    if (blockId === "candidates") {
      const nodes = trace.payload?.nodes;
      if (!Array.isArray(nodes)) return prev;

      const block: TraceBlock = {
        id: "candidates",
        type: "candidates",
        nodes,
      };

      if (index === -1) {
        return { ...prev, [key]: [...blocks, block] };
      }

      const next = [...blocks];
      next[index] = block;
      return { ...prev, [key]: next };
    }

    // ---------- PLAN ----------
    if (blockId === "plan") {
      const nodes = trace.payload?.connected_nodes || [];
      if (!Array.isArray(nodes)) return prev;

      const block: TraceBlock = {
        id: "plan",
        type: "plan",
        nodes,
      };

      if (index === -1) {
        return { ...prev, [key]: [...blocks, block] };
      }

      const next = [...blocks];
      next[index] = block;
      return { ...prev, [key]: next };
    }

    // ---------- WORKFLOW ----------
    if (blockId === "workflow") {
      const workflow = trace.payload?.workflow;
      if (!workflow) return prev;

      const block: TraceBlock = {
        id: "workflow",
        type: "workflow",
        workflow,
      };

      if (index === -1) {
        return { ...prev, [key]: [...blocks, block] };
      }

      const next = [...blocks];
      next[index] = block;
      return { ...prev, [key]: next };
    }

    if (trace.type === "judgement") {
      if (index === -1) {
        return {
          ...prev,
          [key]: [
            ...blocks,
            {
              id: "judgement",
              type: "judgement",
              capabilities: trace.payload.capabilities,
              errors: trace.payload.errors,
              requirements: trace.payload.requirements,
              matches: trace.payload.matches,
            },
          ],
        };
      }
      const next = [...blocks];
      next[index] = {
        ...next[index],
        capabilities: trace.payload.capabilities,
        errors: trace.payload.errors,
        requirements: trace.payload.requirements,
        matches: trace.payload.matches,
      };
      return { ...prev, [key]: next };
    }

    if (blockId === "repaired_workflow") {
      const workflow = trace.payload?.workflow;
      if (!workflow) return prev;

      const block: TraceBlock = {
        id: "repaired_workflow",
        type: "repaired_workflow",
        workflow,
      };

      if (index === -1) {
        return { ...prev, [key]: [...blocks, block] };
      }

      const next = [...blocks];
      next[index] = block; // 🔁 replace previous repair
      return { ...prev, [key]: next };
    }
    return prev;
  });
},
    onComplete: (answer, newHistoryId) =>{// called once when backend finishes generation
      // if (id !== runIdRef.current) return;
      const key = currentHistoryId ?? "new";

      const blob = new Blob([JSON.stringify(answer, null, 2)], {
        type: "application/json",
      });
      const url = URL.createObjectURL(blob);

      setMessageStore((prev) => {
        const msgs = prev[key] ?? [];

        const updated = msgs.map((m, i) =>
          i === msgs.length - 1 && m.type === "assistant"
            ? {
                ...m,
                isStreaming: false,
                canRetry: true,
                content: "I’ve generated your workflow.",
                fileUrl: url,
                fileName: `${answer.name || "workflow"}.json`,
              }
            : m
        );

        return {
          ...prev,
          [newHistoryId]: updated,
        };
      });

      setCurrentHistoryId(newHistoryId);
      setStage("done");
      setActiveGenerationKey(null);

      openFeedback(question, answer);
    },
  });

  const {
    feedback,
    openFeedback,
    confirmYes,
    confirmNo,
  } = useCopilotFeedback();


  useEffect(() => {
    chatRef.current?.scrollTo({
      top: chatRef.current.scrollHeight,
      behavior: "smooth",
    });
  }, [messages]);


  const handleSubmit = () => {
    if (!question.trim()) return;
    if (stage !== "idle" && stage !== "done") return;

    const userMessage: ChatMessage = {
      type: "user",
      content: question.trim(),
    };

    resetTracesForKey(activeKey);


    setMessageStore((prev) => ({
      ...prev,
      [activeKey]: [...(prev[activeKey] ?? []), userMessage],
    }));

    setQuestion("");

    const lastTen = [
      ...(messageStore[activeKey] ?? []),
      userMessage,
    ].slice(-10);

    setActiveGenerationKey(activeKey);

    run(lastTen, currentHistoryId , activeKey);
  };

  const handleTextareaChange = (
    e: React.ChangeEvent<HTMLTextAreaElement>
  ) => {
    setQuestion(e.target.value);

    const el = textareaRef.current;
    if (!el) return;

    el.style.height = "auto";
    el.style.height = `${Math.min(el.scrollHeight, 66)}px`;
    el.style.overflowY = el.scrollHeight > 66 ? "auto" : "hidden";
  };

  const handleCancel = () => {
    cancel();
    setActiveGenerationKey(null);
    setStage("done");

    resetTracesForKey(activeKey);


    setMessageStore((prev) => {
      const msgs = prev[activeKey] ?? [];

      return {
        ...prev,
        [activeKey]: msgs.map((m, i) =>
          i === msgs.length - 1 && m.type === "assistant"
            ? {
                ...m,
                isStreaming: false,
                canRetry: true,
                content: "Generation cancelled.",
              }
            : m
        ),
      };
    });
  };

  const handleRetry = () => {
    const lastUser = getLastUserMessage();
    if (!lastUser) return;

    cancel();

    resetTracesForKey(activeKey);

    setQuestion(lastUser.content);
    setActiveGenerationKey(null);
    setStage("done");

    setMessageStore((prev) => {
      const msgs = prev[activeKey] ?? [];

      return {
        ...prev,
        [activeKey]: msgs.map((m, i) =>
          i === msgs.length - 1 && m.type === "assistant"
            ? {
                ...m,
                isStreaming: false,
                canRetry: true,
                content: "Generation cancelled.",
              }
            : m
        ),
      };
    });
  };

  // helpers:

  const historyPanelOnSelect =(history : CopilotHistory) => {
    cancel();
    setActiveGenerationKey(null);

    resetTracesForKey(activeKey);


    setCurrentHistoryId(history.id);
    setStage("done");

    setMessageStore((prev) => {
      if (prev[history.id]) return prev;

      const msgs: ChatMessage[] = [];
      history.messages.forEach((m) => {
        msgs.push({ type: "user", content: m.user_message });

        const blob = new Blob(
          [JSON.stringify(m.ai_response, null, 2)],
          { type: "application/json" }
        );

        msgs.push({
          type: "assistant",
          content: "I’ve generated your workflow.",
          fileUrl: URL.createObjectURL(blob),
          fileName: `${m.ai_response.name || "workflow"}.json`,
        });
      });

      return { ...prev, [history.id]: msgs };
    });
  }

  const historyPanelOnNewChat = () =>{
    setCurrentHistoryId(null);
    setStage("idle");
    setQuestion("");
    setTraceBlocks({ new: [] });
  }

  const historyPanelOnDelete = (id : number) =>
    deleteHistoryMutation.mutate(id, {
      onSuccess: () => {
        setMessageStore((prev) => {
          const next = { ...prev };
          delete next[id];
          return next;
        });

        if (currentHistoryId === id) {
          setCurrentHistoryId(null);
          setStage("idle");
          setQuestion("");
        }
      }
  })

  const getLastUserMessage = () => {
    const msgs = messageStore[activeKey] ?? [];
    return [...msgs].reverse().find(m => m.type === "user");
  };

  const resetTracesForKey = (key: number | "new") => {
    setTraceBlocks(prev => ({
      ...prev,
      [key]: [],
    }));
  };


  
  return (
    <>
      <Header />
      <section className="copilot-hero">
        <div className="copilot-layout-root">
          <HistoryPanel
            histories={histories}
            loading={historiesLoading}
            currentHistoryId={currentHistoryId}
            onNewChat={historyPanelOnNewChat}
            onSelect={historyPanelOnSelect}
            onDelete={historyPanelOnDelete}
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
                  onKeyDown={(e) => e.key === "Enter" && handleSubmit()}
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
                  onCancel={handleCancel}
                  onRetry={handleRetry}
                />

                <ChatInput
                  value={question}
                  textareaRef={textareaRef}
                  disabled={activeGenerationKey !== null}
                  onChange={handleTextareaChange}
                  onSubmit={handleSubmit}
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
    </>
  );
};
