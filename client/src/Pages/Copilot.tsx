import Header from "./components/Header";
import "../styles/Copilot.css";
import { useState, useRef, useEffect } from "react";
import { useCopilotMutation } from "../hooks/mutations/Copilot/getAnswer.copilot.mutation.hook";
import { useCopilotHistoriesQuery } from "../hooks/queries/Copilot/getHistories.copilot.query.hook";
import { useDeleteCopilotHistoryMutation } from "../hooks/mutations/Copilot/deleteHistory.copilot.mutation.hook";
import type { CopilotHistory } from "../api/copilot.api";

type GenerationStage =
  | "idle"
  | "thinking"
  | "generating"
  | "finalizing"
  | "done";

export type ChatMessage = {
  role: "user" | "assistant";
  content: string;
  fileUrl?: string;
  fileName?: string;
};

export const Copilot = () => {
  const [question, setQuestion] = useState("");
  const [stage, setStage] = useState<GenerationStage>("idle");
  const [currentHistoryId, setCurrentHistoryId] = useState<number | null>(null);
  const textareaRef = useRef<HTMLTextAreaElement>(null);


  const [messageStore, setMessageStore] =
  useState<Record<number | "new", ChatMessage[]>>({});

  const activeKey = currentHistoryId ?? "new";
  const messages = messageStore[activeKey] ?? [];

  const chatRef = useRef<HTMLDivElement>(null);
  const { data: histories } = useCopilotHistoriesQuery();
  const deleteHistoryMutation = useDeleteCopilotHistoryMutation();

  /* Auto-scroll */
  useEffect(() => {
    chatRef.current?.scrollTo({
      top: chatRef.current.scrollHeight,
      behavior: "smooth",
    });
  }, [messages]);

  /* Persist messages */
  useEffect(() => {
    localStorage.setItem("copilot_messages", JSON.stringify(messageStore));
  }, [messageStore]);

  /* Hydrate messages */
  useEffect(() => {
    try {
      const cached = localStorage.getItem("copilot_messages");
      if (cached) setMessageStore(JSON.parse(cached));
    } catch {
      localStorage.removeItem("copilot_messages");
    }
  }, []);

  const { mutate } = useCopilotMutation((answer, historyId) => {
    setStage("done");

    const blob = new Blob(
        [JSON.stringify(answer, null, 2)],
        { type: "application/json" }
    );

    const url = URL.createObjectURL(blob);

    setMessageStore((prev) => {
        const existingMessages =
        prev[historyId] ??
        prev["new"] ??
        [];

        const next = {
        ...prev,
        [historyId]: [
            ...existingMessages,
            {
            role: "assistant",
            content: "I’ve generated your workflow.",
            fileUrl: url,
            fileName: `${answer.name || "workflow"}.json`,
            },
        ],
    };

    // 🔥 remove "new" once migrated
    delete next["new"];

    return next;
  });

  setCurrentHistoryId(historyId);
  });


  const handleSubmit = () => {
    if (!question.trim()) return;
    if (stage !== "idle" && stage !== "done") return;

    const userMessage: ChatMessage = {
      role: "user",
      content: question.trim(),
    };

    setMessageStore((prev) => ({
      ...prev,
      [activeKey]: [...(prev[activeKey] ?? []), userMessage],
    }));

    setStage("thinking");
    setQuestion("");

    const lastTenMessages = [
      ...(messageStore[activeKey] ?? []),
      userMessage,
    ].slice(-10);

    mutate({
      messages: lastTenMessages,
      historyId: currentHistoryId,
    });
  };

  const getInputPlaceholder = () => {
    switch (stage) {
      case "thinking":
        return "Thinking…";
      case "generating":
        return "Generating workflow…";
      case "finalizing":
        return "Finalizing…";
      case "done":
        return "Ask another question…";
      default:
        return "Tell us what you want to build";
    }
  };


    const handleTextareaChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
        setQuestion(e.target.value);

        const el = textareaRef.current;
        if (!el) return;

        el.style.height = "auto";

        const lineHeight = 22; // must match CSS
        const maxRows = 3;
        const maxHeight = lineHeight * maxRows;

        el.style.height = `${Math.min(el.scrollHeight, maxHeight)}px`;
        el.style.overflowY = el.scrollHeight > maxHeight ? "auto" : "hidden";
    };


  return (
    <>
      <Header />

      <section className="copilot-hero">
        <div className="copilot-layout-root">
          <aside className="history-panel">
            <div className="history-header">
              <h2>Histories</h2>
              <button
                type="button"
                className="new-chat-button"
                onClick={() => {
                  setCurrentHistoryId(null);
                  setStage("idle");
                  setQuestion("");
                }}
              >
                New chat
              </button>
            </div>

            <div className="history-list">
              {histories?.map((history: CopilotHistory) => (
                <button
                  key={history.id}
                  type="button"
                  className={`history-item ${
                    currentHistoryId === history.id ? "active" : ""
                  }`}
                  onClick={() => {
                    setCurrentHistoryId(history.id);
                    setStage("done");

                    setMessageStore((prev) => {
                      if (prev[history.id]) return prev;

                      const chatMessages: ChatMessage[] = [];

                      history.messages.forEach((m) => {
                        chatMessages.push({
                          role: "user",
                          content: m.user_message,
                        });

                        const blob = new Blob(
                          [JSON.stringify(m.ai_response, null, 2)],
                          { type: "application/json" }
                        );

                        chatMessages.push({
                          role: "assistant",
                          content: "I’ve generated your workflow.",
                          fileUrl: URL.createObjectURL(blob),
                          fileName: `${m.ai_response.name || "workflow"}.json`,
                        });
                      });

                      return {
                        ...prev,
                        [history.id]: chatMessages,
                      };
                    });
                  }}
                >
                  <div className="history-text">
                    <span className="history-title">
                      {history.messages[0]?.user_message?.slice(0, 40) ||
                        `Chat #${history.id}`}
                    </span>
                    <span className="history-subtitle">
                      {new Date(history.created_at).toLocaleString()}
                    </span>
                  </div>
                  <span
                    className="history-delete"
                    onClick={(e) => {
                      e.stopPropagation();
                      deleteHistoryMutation.mutate(history.id, {
                        onSuccess: () => {
                          setMessageStore((prev) => {
                            const next = { ...prev };
                            delete next[history.id];
                            return next;
                          });

                          if (currentHistoryId === history.id) {
                            setCurrentHistoryId(null);
                            setStage("idle");
                            setQuestion("");
                          }
                        },
                      });
                    }}
                  >
                    🗑
                  </span>
                </button>
              ))}

              {(!histories || histories.length === 0) && (
                <p className="history-empty">No histories yet.</p>
              )}
            </div>
          </aside>

          <div className="copilot-main">
            {stage === "idle" && (
              <div className="copilot-content">
                <h1>What’s On Your Mind</h1>

                <div className="copilot-input-wrapper">
                  <input
                    type="text"
                    placeholder={getInputPlaceholder()}
                    value={question}
                    onChange={(e) => setQuestion(e.target.value)}
                    onKeyDown={(e) => e.key === "Enter" && handleSubmit()}
                  />
                </div>
              </div>
            )}

            {stage !== "idle" && (
              <>
                <div className="chat-container" ref={chatRef}>
                  {messages.map((msg, index) => (
                    <div key={index} className={`chat-message ${msg.role}`}>
                      <div className="bubble">
                        <p>{msg.content}</p>

                        {msg.fileUrl && (
                          <a
                            href={msg.fileUrl}
                            download={msg.fileName}
                            className="file-link"
                          >
                            📄 {msg.fileName}
                          </a>
                        )}
                      </div>
                    </div>
                  ))}
                </div>

                <div className="bottom-input">
                <textarea
                    ref={textareaRef}
                    value={question}
                    placeholder={getInputPlaceholder()}
                    disabled={stage !== "done"}
                    rows={1}
                    onChange={handleTextareaChange}
                    onKeyDown={(e) => {
                    if (e.key === "Enter" && !e.shiftKey) {
                        e.preventDefault();
                        handleSubmit();
                    }
                    }}
                />
                </div>

              </>
            )}
          </div>
        </div>
      </section>
    </>
  );
};
