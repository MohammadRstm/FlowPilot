// copilot/components/ChatView.tsx
import type { RefObject } from "react";
import type { ChatMessage as Msg } from "../Copilot.types";
import { ChatMessage as ChatMessageComponent } from "./ChatMessages";

interface ChatViewProps {
  messages: Msg[];
  chatRef: RefObject<HTMLDivElement | null>;
}

export function ChatView({ messages, chatRef }: ChatViewProps) {
  return (
    <div className="chat-container" ref={chatRef}>
      {messages.map((msg, idx) => (
        <ChatMessageComponent key={idx} msg={msg} />
      ))}
    </div>
  );
}
