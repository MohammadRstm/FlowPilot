import Header from "./components/Header";
import "../styles/Copilot.css";
import { useMemo, useState } from "react";
import { useCopilotMutation } from "../hooks/mutations/Copilot/getAnswer.copilot.mutation.hook";
import type { WorkflowAnswer } from "../api/copilot.api";


type GenerationStage =
  | "idle"
  | "thinking"
  | "generating"
  | "finalizing"
  | "done";

export type ChatMessage = {
  content: string;
};



export const Copilot = () =>{
    const [question, setQuestion] = useState("");
    const [workflow, setWorkflow] = useState<WorkflowAnswer | null>(null);
    const [stage, setStage] = useState<GenerationStage>("idle");
    const [submittedQuestion, setSubmittedQuestion] = useState<string | null>(null);
    const [messages, setMessages] = useState<ChatMessage[]>([]);


    const { mutate, isPending } = useCopilotMutation((answer) => {
        setWorkflow(answer);
        setStage("done")
    });

    const fileUrl = useMemo(() => {
        if (!workflow) return null;

        const blob = new Blob(
        [JSON.stringify(workflow, null, 2)],
        { type: "application/json" }
        );

        return URL.createObjectURL(blob);
    }, [workflow]);

    const handleSubmit = () => {
        if (!question.trim() || stage === "thinking" || stage === "generating" || stage === "finalizing") {
            return;
        }

        const userMessage : ChatMessage = {
            content : question.trim()
        }

        setMessages((prev) => [...prev, userMessage]);
        setSubmittedQuestion(question);
        setStage("thinking");
        setWorkflow(null);
        setQuestion("")

        const lastTenMessages = [...messages, userMessage].slice(-10);


        mutate(lastTenMessages);
    };


    return(
        <>
        <Header />
         <section className={`copilot-hero ${stage !== "idle" ? "generating" : ""}`}>
            <div className="copilot-layout">
                {/* LEFT: Progress */}
                {stage !== "idle" && (
                <div className="generation-progress">
                    <p className="progress-line">
                    &gt; {stage === "thinking" && "Thinking"}
                    {stage === "generating" && "Generating workflow"}
                    {stage === "finalizing" && "Finalizing"}
                    {stage === "done" && "Completed"}
                    </p>
                </div>
                )}

                {/* RIGHT: User question */}
                {submittedQuestion && (
                <div className="user-question">
                    <span>You</span>
                    <p>{submittedQuestion}</p>
                </div>
                )}
            </div>

            {/* CENTER (initial state only) */}
            {stage === "idle" && (
                <div className="copilot-content">
                <h1>What’s On Your Mind</h1>

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

            {/* FILE */}
            {workflow && fileUrl && (
                <div className="workflow-file floating">
                <a
                    href={fileUrl}
                    download={`${workflow.name || "workflow"}.json`}
                >
                    📄 {workflow.name || "Generated Workflow"}
                </a>
                </div>
            )}

            {/* BOTTOM INPUT */}
            {stage !== "idle" && (
                <div className="bottom-input">
                  <input
                    type="text"
                    value={question}
                    placeholder={
                        stage === "done"
                        ? "Ask another question…"
                        : "Generating workflow…"
                    }
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