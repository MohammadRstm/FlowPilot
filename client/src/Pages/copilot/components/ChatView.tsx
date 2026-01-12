import type { RefObject } from "react";
import type { GenerationStage, ChatMessage as Msg, TraceEvent } from "../Copilot.types";
import { ChatMessage as ChatMessageComponent } from "./ChatMessages";

interface ChatViewProps {
  messages: Msg[];
  chatRef: RefObject<HTMLDivElement | null>;
  activeGeneration: boolean;
  traces: TraceEvent[];
  stage: GenerationStage;
  onCancel: () => void;
  onRetry: () => void;
}

export function ChatView({
  messages,
  chatRef,
  activeGeneration,
  stage,
  traces,
  onCancel,
  onRetry,
}: ChatViewProps) {
  return (
    <div className="chat-container" ref={chatRef}>
      {messages.map((msg, idx) => {
        const isLast = idx === messages.length - 1;

        return (
          <div key={idx}>
            <ChatMessageComponent msg={msg} stage={stage} traces={isLast ? traces : []}/>

            {msg.type === "assistant" &&
              isLast &&
              activeGeneration && (
                <div className="assistant-actions">
                  {msg.isStreaming && (
                    <button
                    className="assistant-action-btn cancel"
                    aria-label="Cancel generation"
                    onClick={onCancel}>
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
