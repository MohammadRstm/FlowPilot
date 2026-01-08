import Header from "./components/Header";
import "../styles/Copilot.css";
import { useMemo, useState } from "react";
import { useCopilotMutation } from "../hooks/mutations/Copilot/getAnswer.copilot.mutation.hook";
import type { WorkflowAnswer } from "../api/copilot.api";


export const Copilot = () =>{
    const [question, setQuestion] = useState("");
    const [workflow, setWorkflow] = useState<WorkflowAnswer | null>(null);

    const { mutate, isPending } = useCopilotMutation((answer) => {
        setWorkflow(answer);
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
        if (!question.trim()) return;
        mutate(question);
    };


    return(
        <>
        <Header />
        <section className="copilot-hero">
            <div className="copilot-content">
                <h1>What’s On Your Mind</h1>

                {/* File preview */}
                {workflow && fileUrl && (
                <div className="workflow-file">
                    <a
                    href={fileUrl}
                    download={`${workflow.name || "workflow"}.json`}
                    >
                    📄 {workflow.name || "Generated Workflow"}
                    </a>
                </div>
                )}

                <div className="copilot-input-wrapper">
                <input
                    type="text"
                    placeholder="Tell us what you want to build"
                    value={question}
                    onChange={(e) => setQuestion(e.target.value)}
                    onKeyDown={(e) => e.key === "Enter" && handleSubmit()}
                    disabled={isPending}
                />
                </div>
            </div>
        </section>
        </>
    );
}