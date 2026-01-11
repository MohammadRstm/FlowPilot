import type { ChatMessage as Msg, GenerationStage } from "../Copilot.types";
import { STAGE_LABELS } from "../Copilot.constants";
import { ProgressMessage } from "./ProgressMessage";

export function ChatMessage({ msg }: { msg: Msg }) {
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
            <p>{msg.content}</p>
            {msg.fileUrl && (
              <a href={msg.fileUrl} download={msg.fileName} className="file-link">
                📄 {msg.fileName}
              </a>
            )}
          </div>
        </div>
      );

    case "progress":
      return (
        <div className="chat-message assistant progress">
          <div className="bubble">
            {/* <p>{STAGE_LABELS[msg.stage as GenerationStage]}</p> */}
            <ProgressMessage stage={msg.stage as GenerationStage} />
          </div>
        </div>
      );

    default:
      return null; // should never happen
  }
}
