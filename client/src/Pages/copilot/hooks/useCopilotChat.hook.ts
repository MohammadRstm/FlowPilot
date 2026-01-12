import { useEffect, useState } from "react";
import type { ChatMessage, GenerationStage } from "../Copilot.types";
import { STAGE_LABELS } from "../Copilot.constants";

const STORAGE_KEY = "copilot_messages";

export function useCopilotChat(){// the holy grale of storing messages
    const [messageStore, setMessageStore] = useState<{// the structure contians messages of new conversation or an old one (hence key)
        [key: number]: ChatMessage[];
        new?: ChatMessage[];
    }>({});

    // hydrate on mount
    useEffect(() => {
        try{
            const cached = localStorage.getItem(STORAGE_KEY);
            if(cached) setMessageStore(JSON.parse(cached));
        }catch{
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
    setMessageStore((prev) => {// update messages
        const msgs = prev[key] ?? [];// get current messages
        const last = msgs[msgs.length - 1];// get last message

        const label = STAGE_LABELS[stage] ?? "";// identify the message's label according to the current stage

        if(last?.type === "assistant" && last.isStreaming){// if the last message is an AI one and its currently streaming
        return {// return the all prev message only give the label to the last one as content
            ...prev,
            [key]: msgs.map((m, i) =>
            i === msgs.length - 1
                ? { ...m, content: label }
                : m
            ),
        };
        }
        // else just give the old messages back + new one (the last message is a user message (stage is still idle))
        return {
        ...prev,
        [key]: [
            ...msgs,
            {
            type: "assistant",
            content: label,
            isStreaming: true,
            canRetry: true,
            canCancel : true 
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
