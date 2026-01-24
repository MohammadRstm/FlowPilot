import { useMemo, useState } from "react";
import { filterHistories } from "./utils/historySearch";
import { HistoryHeader } from "./components/HistoryHeader";
import { HistorySearch } from "./components/HistorySearch";
import { HistoryList } from "./components/HistoryList";
import type { CopilotHistory } from "../../hooks/data/types";

interface HistoryPanelProps {
  histories?: CopilotHistory[];
  loading?: boolean;
  currentHistoryId: number | null;
  onSelect: (history: CopilotHistory) => void;
  onDelete: (id: number) => void;
  onNewChat: () => void;
}

export function HistoryPanel({
  histories = [],
  loading,
  currentHistoryId,
  onSelect,
  onDelete,
  onNewChat,
}: HistoryPanelProps) {
  const [searchOpen, setSearchOpen] = useState(false);
  const [query, setQuery] = useState("");

  const listToRender = useMemo(() => {
    if (!query.trim()) return histories;
    return filterHistories(histories, query);
  }, [histories, query]);

  return (
    <aside className="history-panel">
      <HistoryHeader
        onNewChat={onNewChat}
        searchOpen={searchOpen}
        toggleSearch={() => setSearchOpen(v => !v)}
      />

      <HistorySearch
        open={searchOpen}
        query={query}
        onChange={setQuery}
        onClose={() => {
          setQuery("");
          setSearchOpen(false);
        }}
      />

      <HistoryList
        histories={listToRender}
        loading={loading}
        currentHistoryId={currentHistoryId}
        onSelect={onSelect}
        onDelete={onDelete}
      />
    </aside>
  );
}
