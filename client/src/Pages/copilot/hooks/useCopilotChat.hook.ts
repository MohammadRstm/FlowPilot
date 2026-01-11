import { useEffect, useState } from "react";
import type { ChatMessage, GenerationStage } from "../Copilot.types";
import { STAGE_LABELS } from "../Copilot.constants";

const STORAGE_KEY = "copilot_messages";

export function useCopilotChat() {
    const [messageStore, setMessageStore] = useState<{
        [key: number]: ChatMessage[];
        new?: ChatMessage[];
    }>({});

    // hydrate on mount
    useEffect(() => {
        try {
        const cached = localStorage.getItem(STORAGE_KEY);
        if (cached) {
            setMessageStore(JSON.parse(cached));
        }
        } catch {
        localStorage.removeItem(STORAGE_KEY);
        }
    }, []);

    // persist on change
    useEffect(() => {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(messageStore));
    }, [messageStore]);

    const upsertStreamingAssistant = (
    key: number | "new",
    stage: GenerationStage
    ) => {
    setMessageStore((prev) => {
        const msgs = prev[key] ?? [];
        const last = msgs[msgs.length - 1];

        const label = STAGE_LABELS[stage] ?? "";

        if (last?.type === "assistant" && last.isStreaming) {
        return {
            ...prev,
            [key]: msgs.map((m, i) =>
            i === msgs.length - 1
                ? { ...m, content: label }
                : m
            ),
        };
        }

        return {
        ...prev,
        [key]: [
            ...msgs,
            {
            type: "assistant",
            content: label,
            isStreaming: true,
            canRetry: false,
            },
        ],
        };
    });
    };

    return{
        messageStore,
        setMessageStore,
        upsertStreamingAssistant,
    };
}
