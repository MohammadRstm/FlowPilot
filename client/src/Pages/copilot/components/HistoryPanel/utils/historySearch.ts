import type { CopilotHistory } from "../../../hooks/data/types";

export function filterHistories(histories: CopilotHistory[], query: string) {
  const normalizedQuery = query.trim().toLowerCase();

  return [...histories]
    .map(history => {
      const title =
        history.messages[0]?.user_message?.toLowerCase() ??
        `chat ${history.id}`;

      let score = 0;

      if (title.includes(normalizedQuery)) score += 3;
      if (title.startsWith(normalizedQuery)) score += 2;

      normalizedQuery.split(" ").forEach(word => {
        if (title.includes(word)) score += 1;
      });

      return { history, score };
    })
    .filter(h => h.score > 0)
    .sort((a, b) => b.score - a.score)
    .map(h => h.history);
}
