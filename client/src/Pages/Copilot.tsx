import Header from "./components/Header";
import "../styles/Copilot.css";
import { useState , useRef, useEffect } from "react";
import { useCopilotMutation } from "../hooks/mutations/Copilot/getAnswer.copilot.mutation.hook";


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



export const Copilot = () =>{
    const [question, setQuestion] = useState("");
    const [stage, setStage] = useState<GenerationStage>("idle");
    const [messages, setMessages] = useState<ChatMessage[]>([]);

    const chatRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        chatRef.current?.scrollTo({
            top: chatRef.current.scrollHeight,
            behavior: "smooth",
        });
    }, [messages]);



    const { mutate } = useCopilotMutation((answer) => {
        setStage("done");

        const blob = new Blob(
            [JSON.stringify(answer, null, 2)],
            { type: "application/json" }
        );

        const url = URL.createObjectURL(blob);

        setMessages((prev) => [
            ...prev,
            {
            role: "assistant",
            content: "I’ve generated your workflow.",
            fileUrl: url,
            fileName: `${answer.name || "workflow"}.json`,
            },
        ]);
    });


    const handleSubmit = () => {
        if (!question.trim() || stage === "thinking" || stage === "generating" || stage === "finalizing") {
            return;
        }

        const userMessage: ChatMessage = {
            role: "user",
            content: question.trim(),
        };

        setMessages((prev) => [...prev, userMessage]);
        setStage("thinking");
        setQuestion("")

        const lastTenMessages = [...messages, userMessage].slice(-10);


        mutate(lastTenMessages);
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



    return (
  <>
    <Header />

    <section className={`copilot-hero ${stage !== "idle" ? "generating" : ""}`}>
      {/* Initial state */}
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

      {/* Chat area */}
      {stage !== "idle" && (
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
      )}

      {/* Bottom input */}
      {stage !== "idle" && (
        <div className="bottom-input">
            <input
                type="text"
                value={question}
                placeholder={getInputPlaceholder()}
                disabled={stage !== "done"}
                onChange={(e) => setQuestion(e.target.value)}
                onKeyDown={(e) => e.key === "Enter" && handleSubmit()}
            />
        </div>
      )}
    </section>
  </>
);

}