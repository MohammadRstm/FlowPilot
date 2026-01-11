// copilot/components/ChatView.tsx
import type { RefObject } from "react";
import type { GenerationStage, ChatMessage as Msg } from "../Copilot.types";
import { ChatMessage as ChatMessageComponent } from "./ChatMessages";
import { Spinner } from "../../components/Spinner";

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
