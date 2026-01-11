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
} from "./Copilot.types";

import type { CopilotHistory } from "../../api/copilot.api";

import { useCopilotChat } from "./hooks/useCopilotChat.hook";
import { useCopilotStream } from "./hooks/useCopilotStream.hook";
import { useCopilotFeedback } from "./hooks/useCopilotFeedback.hook";

// ADD DETAILS ABOUT HOW THE AI IS BUILDING THE FLOW WITH VISUALIZATIONS - HARD - F/B HEAVY ON BOTH
// ADD THE ABILITY TO SEND USER WORKFLOWS TO ADD ON IT/FIX IT - HARD - BACKEND HEAVY
// ENHANCE THE ABILITY TO CONTINUE THE CONVERSATION - HARD - BACKEND HEAVY
// HISOTRIES OVER 2 WEEKS OLD MUST BE AUTOMATICALLY DELETED - MEDIUM - BEACKEND HEAVY
// ADD PROMPT SAFEGURAD STAGE FOR VISCIOUS PROMPTS (forget everything, delete db exct/) - MEDIUM - BACKEND HEAVY
// FIGURE OUT A BETTER WAY TO GET USER FEEDBACK CURRENTLY NOT VERY EFFICIENT NOR DOES IT MAKE SENSE - UNKNOWN - BAVKEND HEAVY
// ADD THE ABILITY TO CREATE CUSTOM NODES - VERY HARD - F/B HEAVY ON BOTH
// ADD THE ABILITY TO SAVE CREDENTIALS OR FIGURE OUT A WAY TO DO IT AUTOMATICALLY - HARD F/B HEAVY ON BOTH
// APPROX TIME : 8 DAYS TO FINISH (EXCLUDING ENHANCING THE AI'S ABILITY TO GENERATE WORKFLOWS)

export const Copilot = () => {
  const [question, setQuestion] = useState("");
  const [stage, setStage] = useState<GenerationStage>("idle");
  const [currentHistoryId, setCurrentHistoryId] = useState<number | null>(null);

  const activeKey = currentHistoryId ?? "new";
  const textareaRef = useRef<HTMLTextAreaElement>(null);
  const chatRef = useRef<HTMLDivElement>(null);

  const { data: histories,  isLoading: historiesLoading } = useCopilotHistoriesQuery();
  const deleteHistoryMutation = useDeleteCopilotHistoryMutation();

  const [activeGenerationKey, setActiveGenerationKey] =
  useState<number | "new" | null>(null);


  // hooks
  const {
    messageStore,
    setMessageStore,
    upsertStreamingAssistant,
  } = useCopilotChat();
  const messages = messageStore[activeKey] ?? [];

  const { run , cancel} = useCopilotStream({
    onStage: setStage,
    onProgress: (key : number | "new", stage: GenerationStage) => {
      upsertStreamingAssistant(key, stage); 
    },
    onComplete: (answer, newHistoryId) => {
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
                  activeGeneration={activeGenerationKey === activeKey}
                  stage={stage}
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
