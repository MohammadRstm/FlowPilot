import { useDeleteCopilotHistoryMutation } from "../data/deleteHistory.copilot.mutation.hook";
import { useCopilotHistoriesQuery } from "../data/getHistories.copilot.query.hook";
import { ChatMessageType, type ChatMessage } from "../../types";
import type { CopilotHistory } from "../data/types";

type ChatKey = number | "new";

type MessageStore = Record<ChatKey, ChatMessage[]>;
type TraceStore = Record<ChatKey, any[]>;


type HistoryHookProp = {
  currentHistoryId: number | null;
  activeGenerationKey : number | null | "new";
  cancel: () => void;
  setCurrentHistoryId: (id: number | null) => void;
  setStage: (s: any) => void;
  setQuestion: (q: string) => void;
  setMessageStore: React.Dispatch<React.SetStateAction<MessageStore>>;
  setTraceBlocks: React.Dispatch<React.SetStateAction<TraceStore>>;
  setActiveGenerationKey:  React.Dispatch<React.SetStateAction<ChatKey | null>>;
}

export function useCopilotHistoryController({
  currentHistoryId,
  activeGenerationKey,
  cancel,
  setCurrentHistoryId,
  setStage,
  setQuestion,
  setMessageStore,
  setTraceBlocks,
  setActiveGenerationKey,
}: HistoryHookProp) {
  const { data: histories, isLoading } = useCopilotHistoriesQuery();
  const deleteMutation = useDeleteCopilotHistoryMutation();


  const selectHistory = (history: CopilotHistory) => {
    if (activeGenerationKey !== null) return;
    cancel();
    setActiveGenerationKey(null);
    setTraceBlocks(prev => ({
      ...prev,
      [history.id]: []
    }));
    setCurrentHistoryId(history.id);
    setStage("done");

    setMessageStore(prev => {
      if (prev[history.id]) return prev;

      const msgs: ChatMessage[] = [];

      history.messages.forEach(m => {
        msgs.push({ type: ChatMessageType.USER, content: m.user_message });

        const blob = new Blob(
          [JSON.stringify(m.ai_response, null, 2)],
          { type: "application/json" }
        );

        msgs.push({
          type: ChatMessageType.ASSISTANT,
          content: "I’ve generated your workflow.",
          fileUrl: URL.createObjectURL(blob),
          fileName: `${m.ai_response.name || "workflow"}.json`,
        });
      });

      return { ...prev, [history.id]: msgs };
    });
  };


  const newChat = () => {
    if (activeGenerationKey !== null) return;

    setCurrentHistoryId(null);
    setStage("idle");
    setQuestion("");
    setTraceBlocks(prev => ({ ...prev, new: [] }));

    setMessageStore(prev => ({
      ...prev,
      new: [], 
    }));
  };


  const deleteHistory = (id: number) => {
    deleteMutation.mutate(id, {
      onSuccess: () => {
        setMessageStore(prev => {
          const next = { ...prev };
          delete next[id];
          return next;
        });

        if (currentHistoryId === id) {
          newChat();
        }
      },
    });
  };

  return {
    histories,
    loading: isLoading,
    selectHistory,
    newChat,
    deleteHistory,
  };
}
