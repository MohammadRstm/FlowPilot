import { ChatMessageType } from "../types";

export const buildWorkflowFile = (answer: any) => {
  const blob = new Blob([JSON.stringify(answer, null, 2)], {
    type: "application/json",
  });

  return {
    url: URL.createObjectURL(blob),
    name: `${answer.name || "workflow"}.json`,
  };
};


export const finalizeAssistantMessage = (
  msgs: any[],
  fileUrl: string,
  fileName: string
) => {
  return msgs.map((m, i) =>
    i === msgs.length - 1 && m.type === ChatMessageType.ASSISTANT
      ? {
          ...m,
          isStreaming: false,
          canRetry: true,
          content: "I’ve generated your workflow.",
          fileUrl,
          fileName,
        }
      : m
  );
};


export const commitHistory = (
  prev: any,
  newHistoryId: number,
  updatedMsgs: any[]
) => {
  return {
    ...prev,
    [newHistoryId]: updatedMsgs,
  };
};
