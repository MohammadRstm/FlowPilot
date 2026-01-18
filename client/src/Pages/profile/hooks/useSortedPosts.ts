import { useMemo } from "react";
import { getCounts, getScore } from "../utils/postScoring";

export function useSortedPosts(posts: any[], sortBy: string) {
  return useMemo(() => {
    const list = [...posts];
    switch (sortBy) {
      case "likes":
        return list.sort((a, b) => getCounts(b).likes - getCounts(a).likes);
      case "imports":
        return list.sort((a, b) => getCounts(b).imports - getCounts(a).imports);
      case "comments":
        return list.sort((a, b) => getCounts(b).comments - getCounts(a).comments);
      default:
        return list.sort((a, b) => getScore(b) - getScore(a));
    }
  }, [posts, sortBy]);
}
