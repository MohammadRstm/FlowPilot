import { ChatMessageType, type GenerationStage, type ChatMessage as Msg, type TraceBlock } from "../types";
import { ProgressMessage } from "./ProgressMessage";
import { TraceView } from "./tracer/TraceView";

export function ChatMessage({ msg , stage , traces }: { msg: Msg , stage : GenerationStage; traces : TraceBlock[]; runId : number}) {
  switch (msg.type) {
    case ChatMessageType.USER:
      return (
        <div className="chat-message user">
          <div className="bubble">
            <p>{msg.content}</p>
          </div>
        </div>
      );

    case ChatMessageType.ASSISTANT:
    return (
        <div className="chat-message assistant">
        <div className="bubble">
            {msg.isStreaming ? (
                <>
                    <TraceView traces={traces || []} />
                    <ProgressMessage stage={stage as GenerationStage} />
                </>
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
