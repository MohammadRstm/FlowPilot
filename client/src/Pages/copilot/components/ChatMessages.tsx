import type { GenerationStage, ChatMessage as Msg } from "../Copilot.types";
import { ProgressMessage } from "./ProgressMessage";

export function ChatMessage({ msg , stage }: { msg: Msg , stage : GenerationStage}) {
  switch (msg.type) {
    case "user":
      return (
        <div className="chat-message user">
          <div className="bubble">
            <p>{msg.content}</p>
          </div>
        </div>
      );

    case "assistant":
    return (
        <div className="chat-message assistant">
        <div className="bubble">
            {msg.isStreaming ? (
            <ProgressMessage stage={stage as GenerationStage} />
            ) : (
            <>
                <p>{msg.content}</p>
                {msg.fileUrl && (
                <a href={msg.fileUrl} download={msg.fileName} className="file-link">
                    📄 {msg.fileName}
                </a>
                )}
            </>
            )}
        </div>
        </div>
      );

    default:
      return null; // should never happen
  }
}
