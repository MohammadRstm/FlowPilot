import type { ChatMessage, GenerationStage } from "../types";
import { streamCopilotQuestion } from "../hooks/data/streamResponse";
import type { ToastType } from "../../components/toast/toast.types";

export type StreamKey = number | "new";

export interface StreamState {
  key: StreamKey;
  historyId: number | null;
  messages: ChatMessage[];
  userId: number;
  stage: GenerationStage;
  eventSource: EventSource | null;
  startedAt: number;
}

export interface StreamListeners {
  onStage?: (key: StreamKey, stage: GenerationStage) => void;
  onProgress?: (key: StreamKey, stage: GenerationStage) => void;
  onTrace?: (key: StreamKey, trace: any) => void;
  onComplete?: (key: StreamKey, answer: any, historyId: number) => void;
  onError?: (key: StreamKey) => void;
  showToast?:(message: string, type?: ToastType) => void;
}

class BackgroundStreamService {
  private streams = new Map<StreamKey, StreamState>();
  private listeners = new Map<string, StreamListeners>();

  startStream(
    key: StreamKey,
    messages: ChatMessage[],
    historyId: number | null,
    userId: number,
    showToast: (message: string, type?: string) => void,
    onListeners: StreamListeners
  ) {
    this.stopStream(key);

    const streamState: StreamState = {
      key,
      historyId,
      messages,
      userId,
      stage: "analyzing",
      eventSource: null,
      startedAt: Date.now(),
    };

    const listenerId = `stream_${key}`;
    this.listeners.set(listenerId, onListeners);

    const params = new URLSearchParams();
    params.append("messages", JSON.stringify(messages));
    params.append("userId", userId.toString());
    if (historyId) params.append("history_id", historyId.toString());

    const eventSource = streamCopilotQuestion(
      userId,
      messages,
      showToast,
      historyId,
      (stage) => {
        streamState.stage = stage as GenerationStage;
        onListeners.onStage?.(key, stage as GenerationStage);
        onListeners.onProgress?.(key, stage as GenerationStage);
      },
      (trace) => {
        onListeners.onTrace?.(key, trace);
      },
      (answer, newHistoryId) => {
        onListeners.onComplete?.(key, answer, newHistoryId);
        this.streams.delete(key);
        this.listeners.delete(listenerId);
      },
      () => {
        onListeners.onError?.(key);
        this.streams.delete(key);
        this.listeners.delete(listenerId);
      }
    );

    streamState.eventSource = eventSource;
    this.streams.set(key, streamState);

    return {
      cancel: () => this.stopStream(key),
      getStream: () => this.streams.get(key),
    };
  }

  stopStream(key: StreamKey) {
    const stream = this.streams.get(key);
    if (stream?.eventSource) {
      stream.eventSource.close();
    }
    this.streams.delete(key);
    this.listeners.delete(`stream_${key}`);
  }

  getActiveStreams(): StreamState[] {
    return Array.from(this.streams.values());
  }

  isStreamActive(key: StreamKey): boolean {
    return this.streams.has(key);
  }

  getStream(key: StreamKey): StreamState | undefined {
    return this.streams.get(key);
  }

  stopAllStreams() {
    this.streams.forEach((stream) => {
      stream.eventSource?.close();
    });
    this.streams.clear();
    this.listeners.clear();
  }
}

export const backgroundStreamService = new BackgroundStreamService();
