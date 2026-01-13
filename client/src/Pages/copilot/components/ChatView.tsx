import { useCallback, useEffect, useRef, useState, type RefObject } from "react";
import type { GenerationStage, ChatMessage as Msg, TraceBlock } from "../Copilot.types";
import { ChatMessage as ChatMessageComponent } from "./ChatMessages";

interface ChatViewProps {
  messages: Msg[];
  chatRef: RefObject<HTMLDivElement | null>;
  activeGeneration: boolean;
  traces: TraceBlock[];
  stage: GenerationStage;
  runId: number;
  onCancel: () => void;
  onRetry: () => void;
}

export function ChatView({
  messages,
  chatRef,
  activeGeneration,
  stage,
  traces,
  runId,
  onCancel,
  onRetry,
}: ChatViewProps) {
  const [autoScroll, setAutoScroll] = useState(true);
  const lastScrollTop = useRef(0);
  const autoScrollRef = useRef(true);


  const SCROLL_BUFFER = 50; // px buffer to ignore minor scrolls

  // handle user scroll
    const handleScroll = useCallback(() => {
        if (!chatRef.current) return;

        const { scrollTop, scrollHeight, clientHeight } = chatRef.current;
        const distanceFromBottom = scrollHeight - (scrollTop + clientHeight);

        const shouldAutoScroll = distanceFromBottom <= SCROLL_BUFFER;

        autoScrollRef.current = shouldAutoScroll; 
        setAutoScroll(shouldAutoScroll);
    }, []);


    useEffect(() => {
        autoScrollRef.current = autoScroll;
    }, [autoScroll]);


    // attach scroll listener
    useEffect(() => {
        const el = chatRef.current;
        if (!el) return;
        el.addEventListener("scroll", handleScroll);
        return () => el.removeEventListener("scroll", handleScroll);
    }, [handleScroll]);

    // continuously scroll while streaming
    useEffect(() => {
    if (!chatRef.current || !autoScroll) return;

    let frameId: number;

    const scrollToBottom = () => {
        if (!chatRef.current) return;
        if (!autoScrollRef.current) return; 

        chatRef.current.scrollTo({
        top: chatRef.current.scrollHeight,
        behavior: "smooth",
        });

        if (activeGeneration) {
        frameId = requestAnimationFrame(scrollToBottom);
        }
    };

    frameId = requestAnimationFrame(scrollToBottom);

    return () => cancelAnimationFrame(frameId);
    }, [messages, traces, activeGeneration, autoScroll]);

  return (
    <div className="chat-container" ref={chatRef}>
      {messages.map((msg, idx) => {
        const isLast = idx === messages.length - 1;

        return (
          <div key={idx}>
            <ChatMessageComponent
              msg={msg}
              stage={stage}
              traces={isLast ? traces : []}
              runId={runId}
            />

            {msg.type === "assistant" &&
              isLast &&
              activeGeneration && (
                <div className="assistant-actions">
                  {msg.isStreaming && (
                    <button
                      className="assistant-action-btn cancel"
                      aria-label="Cancel generation"
                      onClick={onCancel}
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="3"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                      >
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                      </svg>
                    </button>
                  )}

                  <button
                    className="assistant-action-btn retry"
                    onClick={onRetry}
                    aria-label="Retry generation"
                  >
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      width="16"
                      height="16"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeWidth="3"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <polyline points="1 4 1 10 7 10" />
                      <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10" />
                    </svg>
                  </button>
                </div>
              )}
          </div>
        );
      })}
    </div>
  );
}
