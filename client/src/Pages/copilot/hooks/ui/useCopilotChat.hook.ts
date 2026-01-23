import { useEffect, useState } from "react";
import { ChatMessageType, type ChatMessage, type GenerationStage, type TraceBlock } from "../../types";
import { STAGE_LABELS } from "../../constants";

const STORAGE_KEY = "copilot_messages";

export type ChatKey = number | "new";

type CopilotChatPropsType = {
  userId: number;
  run: Function;
  cancel: () => void;
  setStage: (s: GenerationStage) => void;
  setQuestion: (q : string ) => void;
  setActiveGenerationKey: (k: ChatKey | null) => void;
  setTraceBlocks: React.Dispatch<
    React.SetStateAction<Record<ChatKey, TraceBlock[]>>
  >;
  currentHistoryId: number | null;
} 

export function useCopilotChatController({
  run,
  userId,
  cancel,
  setStage,
  setQuestion,
  setActiveGenerationKey,
  setTraceBlocks,
  currentHistoryId,
}: CopilotChatPropsType){

    const [messageStore, setMessageStore] = useState<Record<ChatKey, ChatMessage[]>>({
        new: [],
    });
    const activeKey: ChatKey = currentHistoryId ?? "new";

    useEffect(() => {
        try {
            const cached = localStorage.getItem(STORAGE_KEY);
            if (cached) setMessageStore(JSON.parse(cached));
        } catch {
            localStorage.removeItem(STORAGE_KEY);
        }
    }, []);

    useEffect(() => {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(messageStore));
    }, [messageStore]);

    const resetTracesForKey = (key: ChatKey) => {
        setTraceBlocks(prev => ({ ...prev, [key]: [] }));
    };

    const getLastUserMessage = () => {
        const msgs = messageStore[activeKey] ?? [];
        return [...msgs].reverse().find(m => m.type === ChatMessageType.USER);
    };

    const upsertStreamingAssistant = (key: ChatKey, stage: GenerationStage) => {
        setMessageStore(prev => {
        const msgs = prev[key] ?? [];
        const last = msgs[msgs.length - 1];
        const label = STAGE_LABELS[stage] ?? "";

        if (last?.type === ChatMessageType.ASSISTANT && last.isStreaming) {
            return {
            ...prev,
            [key]: msgs.map((m, i) =>
                i === msgs.length - 1 ? { ...m, content: label } : m
            ),
            };
        }

        return {
            ...prev,
            [key]: [
            ...msgs,
            {
                type: ChatMessageType.ASSISTANT,
                content: label,
                isStreaming: true,
                canRetry: true,
                canCancel: false,
            },
            ],
        };
        });
    };

    const submit = (question: string) => {
        if (!question.trim()) return;

        const userMessage: ChatMessage = {
        type: ChatMessageType.USER,
        content: question.trim(),
        };

        resetTracesForKey(activeKey);

        setMessageStore(prev => ({
        ...prev,
        [activeKey]: [...(prev[activeKey] ?? []), userMessage],
        }));

        setQuestion("");
        const nextMessages = [...(messageStore[activeKey] ?? []), userMessage];
        const lastTenUserMessages = nextMessages
        .filter((m): m is Extract<ChatMessage, { type: "user" }> => m.type === ChatMessageType.USER)
        .slice(-10);
        
        setActiveGenerationKey(activeKey);
        run(lastTenUserMessages, currentHistoryId, activeKey , userId);
    };

    const cancelGeneration = () => {
        cancel();// signals backend
        setActiveGenerationKey(null);
        setStage("done");
        resetTracesForKey(activeKey);

        setMessageStore(prev => {
        const msgs = prev[activeKey] ?? [];
        return {
            ...prev,
            [activeKey]: msgs.map((m, i) =>
            i === msgs.length - 1 && m.type === ChatMessageType.ASSISTANT
                ? { ...m, isStreaming: false, canRetry:true, content: "Generation cancelled." }
                : m
            ),
        };
        });
    };

    const retry = () => {
        const lastUser = getLastUserMessage();
        if (!lastUser) return;

        cancelGeneration();
        submit(lastUser.content); 
    };

    const edit = () =>{
        const lastUser = getLastUserMessage();
        if(!lastUser) return;

        cancelGeneration();
    }

    return {
        messageStore,
        setMessageStore,
        upsertStreamingAssistant,
        submit,
        cancelGeneration,
        retry,
    };
}
