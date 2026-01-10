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

// ADD DETAILS ABOUT HOW THE AI IS BUILDING THE FLOW WITH VISUALIZATIONS (COLLOUSIL)
// ANIMATE PROGRESS CURRENTLY LOOKS SHIT
// ADD A TABLE TO SAVE FEEDBACK
// ADD A SEND BUTTON
// ADD THE ABILITY TO SEND USER WORKFLOWS TO ADD ON IT/FIX IT
// ENHANCE THE ABILITY TO CONTINUE THE CONVERSATION
// ADD PAGINATED MESSAGE RETRIEVAL
// HISOTRIES OVER 2 WEEKS OLD MUST BE AUTOMATICALLY DELETED
// ADD SEARCH FEATURE FOR HISOTRIES
// ADD CANCLE/RETRY GENERATION
// ADD PROMPT SAFEGURAD STAGE FOR VISCIOUS PROMPTS (forget everything, delete db exct/)
// ADD LOADING SPINNER FOR HISTORY RETRIEVAL
// FIX SORRY TO HEAR THAT FEEDBACK TOAST HANGING
// FIGURE OUT A BETTER WAY TO GET USER FEEDBACK CURRENTLY NOT VERY EFFICIENT NOR DOES IT MAKE SENSE
// ADD THE ABILITY TO CREATE CUSTOM NODES
// ADD THE ABILITY TO SAVE CREDENTIALS OR FIGURE OUT A WAY TO DO IT AUTOMATICALLY

export const Copilot = () => {
  const [question, setQuestion] = useState("");
  const [stage, setStage] = useState<GenerationStage>("idle");
  const [currentHistoryId, setCurrentHistoryId] = useState<number | null>(null);

  const activeKey = currentHistoryId ?? "new";
  const textareaRef = useRef<HTMLTextAreaElement>(null);
  const chatRef = useRef<HTMLDivElement>(null);

  const { data: histories } = useCopilotHistoriesQuery();
  const deleteHistoryMutation = useDeleteCopilotHistoryMutation();

  // hooks
  const {
    messageStore,
    setMessageStore,
    upsertProgressMessage,
    removeProgressMessage,
  } = useCopilotChat();
  const messages = messageStore[activeKey] ?? [];

  const { run } = useCopilotStream({
    onStage: setStage,
    onProgress: (key : number | "new", stage: GenerationStage) => {
      upsertProgressMessage(key, stage); 
    },
    onComplete: (answer, newHistoryId) => {
      const key = currentHistoryId ?? "new";

      removeProgressMessage(key);

      const blob = new Blob([JSON.stringify(answer, null, 2)], {
        type: "application/json",
      });
      const url = URL.createObjectURL(blob);

      setMessageStore((prev) => {
        const existing = prev[key] ?? [];

        const next = {
          ...prev,
          [newHistoryId]: [
            ...existing,
            {
              type: "assistant",
              content: "I’ve generated your workflow.",
              fileUrl: url,
              fileName: `${answer.name || "workflow"}.json`,
            },
          ] as ChatMessage[],
        };

        delete next["new"];
        return next;
      });

      setCurrentHistoryId(newHistoryId);
      setStage("done");

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

  // helpers:
  const historyPanelOnSelect =(history : CopilotHistory) => {
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
  
  return (
    <>
      <Header />

      <section className="copilot-hero">
        <div className="copilot-layout-root">
          <HistoryPanel
            histories={histories}
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
                <ChatView messages={messages} chatRef={chatRef} />

                <ChatInput
                  value={question}
                  textareaRef={textareaRef}
                  disabled={stage !== "done"}
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
