import Header from "./components/Header";
import "../styles/Copilot.css";
import { useState, useRef, useEffect } from "react";
import { useCopilotHistoriesQuery } from "../hooks/queries/Copilot/getHistories.copilot.query.hook";
import { useDeleteCopilotHistoryMutation } from "../hooks/mutations/Copilot/deleteHistory.copilot.mutation.hook";
import { streamCopilotQuestion, type CopilotHistory, type WorkflowAnswer } from "../api/copilot.api";
import { useConfirmWorkflowMutation } from "../hooks/mutations/Copilot/confirmWorkflow.copilot.mutation.hook";

type GenerationStage =
  | "idle"
  | "analyzing"
  | "retrieving"
  | "ranking"
  | "generating"
  | "validating"
  | "done";

export type ChatMessage = {
  role: "user" | "assistant";
  content: string;
  fileUrl?: string;
  fileName?: string;
};

type FeedbackStatus = "pending" | "thanks" | "sorry";

interface FeedbackState {
  open: boolean;
  status: FeedbackStatus;
  message: string;
  question: string;
  workflow: WorkflowAnswer;
}

const STAGE_LABELS: Record<GenerationStage, string> = {
  idle: "",
  analyzing: "🔍 Understanding your request…",
  retrieving: "📚 Searching relevant workflows…",
  ranking: "🧠 Evaluating best solution…",
  generating: "⚙️ Generating workflow…",
  validating: "✅ Validating workflow logic…",
  done: "",
};


export const Copilot = () => {
  const [question, setQuestion] = useState("");
  const [stage, setStage] = useState<GenerationStage>("idle");
  const [currentHistoryId, setCurrentHistoryId] = useState<number | null>(null);
  const textareaRef = useRef<HTMLTextAreaElement>(null);
  const streamRef = useRef<EventSource | null>(null);
  const [feedback, setFeedback] = useState<FeedbackState | null>(null);

  const [messageStore, setMessageStore] =
    useState<Record<number | "new", ChatMessage[]>>({});

  const activeKey = currentHistoryId ?? "new";
  const messages = messageStore[activeKey] ?? [];

  const chatRef = useRef<HTMLDivElement>(null);
  const { data: histories } = useCopilotHistoriesQuery();
  const deleteHistoryMutation = useDeleteCopilotHistoryMutation();
  const confirmWorkflowMutation = useConfirmWorkflowMutation();

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

  const runCopilot = (messages: ChatMessage[], historyId: number | null, originQuestion: string) => {
  streamRef.current?.close();

  const key = historyId ?? "new";

  setStage("analyzing");
  upsertProgressMessage(key, STAGE_LABELS.analyzing);

  const stream = streamCopilotQuestion(
    messages,
    historyId,
    (stage) => {
      const typedStage = stage as GenerationStage;
      setStage(typedStage);

      const label = STAGE_LABELS[typedStage];
      if (label) {
        upsertProgressMessage(key, label);
      }
    },
    (answer, newHistoryId) => {
      setStage("done");

      const blob = new Blob(
        [JSON.stringify(answer, null, 2)],
        { type: "application/json" }
      );

      const url = URL.createObjectURL(blob);

      setMessageStore((prev) => {
        const existing = (prev[key] ?? []).filter(
          (m) => !m.content.startsWith("__progress__")
        );

        const next = {
          ...prev,
          [newHistoryId]: [
            ...existing,
            {
              role: "assistant",
              content: "I’ve generated your workflow.",
              fileUrl: url,
              fileName: `${answer.name || "workflow"}.json`,
            },
          ],
        };

        delete next["new"];
        return next;
      });

      setCurrentHistoryId(newHistoryId);

      // show satisfaction popup for this workflow
      setFeedback({
        open: true,
        status: "pending",
        message: "Are you satisfied with the generated workflow?",
        question: originQuestion,
        workflow: answer,
      });
    }
  );

  streamRef.current = stream;
};



const upsertProgressMessage = (
  key: number | "new",
  content: string
) => {
  setMessageStore((prev) => {
    const msgs = prev[key] ?? [];

    const hasProgress = msgs.some(
      (m) => m.role === "assistant" && m.content.startsWith("__progress__")
    );

    const progressMessage: ChatMessage = {
      role: "assistant",
      content: `__progress__${content}`,
    };

    return {
      ...prev,
      [key]: hasProgress
        ? msgs.map((m) =>
            m.content.startsWith("__progress__") ? progressMessage : m
          )
        : [...msgs, progressMessage],
    };
  });
};





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

    runCopilot(lastTenMessages, currentHistoryId, userMessage.content);
  };

  // auto-hide logic for feedback popup
  useEffect(() => {
    if (!feedback) return;

    if (feedback.open && feedback.status === "pending") {
      const t = setTimeout(() => {
        setFeedback((prev) => (prev ? { ...prev, open: false } : prev));
      }, 10000);
      return () => clearTimeout(t);
    }

    if (!feedback.open) {
      const t = setTimeout(() => setFeedback(null), 400);
      return () => clearTimeout(t);
    }
  }, [feedback]);

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
                <h1>Where the magic happens</h1>

                <div className="copilot-input-wrapper">
                  <input
                    type="text"
                    placeholder="Tell us what you want to build"
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
                        <p>
                        {msg.content.startsWith("__progress__")
                            ? msg.content.replace("__progress__", "")
                            : msg.content}
                        </p>

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
                placeholder="Keep them coming"
                disabled={stage !== "idle" && stage !== "done"}
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

        {feedback && (
          <div
            className={`copilot-feedback ${
              feedback.open ? "copilot-feedback--visible" : "copilot-feedback--hide"
            }`}
          >
            <div className="copilot-feedback-body">
              <p>{feedback.message}</p>

              {feedback.status === "pending" && (
                <div className="copilot-feedback-actions">
                  <button
                    type="button"
                    onClick={() => {
                      // user is satisfied
                      setFeedback((prev) =>
                        prev
                          ? {
                              ...prev,
                              status: "thanks",
                              message: "Thank you for your response.",
                            }
                          : prev
                      );

                      if (feedback.question && feedback.workflow) {
                        confirmWorkflowMutation.mutate({
                          question: feedback.question,
                          workflow: feedback.workflow,
                        });
                      }

                      setTimeout(
                        () =>
                          setFeedback((prev) =>
                            prev && prev.status === "thanks"
                              ? { ...prev, open: false }
                              : prev
                          ),
                        2000
                      );
                    }}
                  >
                    Yes
                  </button>

                  <button
                    type="button"
                    onClick={() => {
                      setFeedback((prev) =>
                        prev
                          ? {
                              ...prev,
                              status: "sorry",
                              message: "Sorry to hear that.",
                            }
                          : prev
                      );

                      setTimeout(
                        () =>
                          setFeedback((prev) =>
                            prev && prev.status === "sorry"
                              ? { ...prev, open: false }
                              : prev
                          ),
                        2000
                      );
                    }}
                  >
                    No
                  </button>
                </div>
              )}
            </div>
          </div>
        )}
      </section>
    </>
  );
};
