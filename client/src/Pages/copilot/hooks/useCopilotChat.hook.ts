import { useEffect, useState } from "react";
import type { ChatMessage, GenerationStage } from "../Copilot.types";

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

    const upsertProgressMessage = (key: number | "new", stage: GenerationStage) => {
        setMessageStore((prev) => {
            const msgs = prev[key] ?? [];

            const progress: ChatMessage = { type: "progress", stage };

            const hasProgress = msgs.some((m) => m.type === "progress");

            return {
            ...prev,
            [key]: hasProgress
                ? msgs.map((m) => (m.type === "progress" ? progress : m))
                : [...msgs, progress],
            };
        }); 
    };

    const removeProgressMessage = (key: number | "new") => {
        setMessageStore((prev) => ({
            ...prev,
            [key]: (prev[key] ?? []).filter((m) => m.type !== "progress"),
        }));
    };

    return {
        messageStore,
        setMessageStore,
        upsertProgressMessage,
        removeProgressMessage,
    };
}
