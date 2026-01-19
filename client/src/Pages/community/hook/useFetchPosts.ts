// src/hooks/useFetchPosts.ts
import { useInfiniteQuery } from "@tanstack/react-query";
import { fetchPosts } from "../../../api/community/fetchPosts";

export type PostDto = {
  id: number;
  author: string;
  username?: string | null;
  avatar?: string | null;
  content: string;
  likes: number;
  comments: number;
  exports: number;
  score?: number;
  created_at?: string | null;
};

type ApiResponse = {
  data: PostDto[];
  meta: {
    current_page: number;
    last_page: number;
  };
};

export function useFetchPosts() {
  const query = useInfiniteQuery<ApiResponse>({
    queryKey: ["community-posts"],
    queryFn: fetchPosts,

    getNextPageParam: (lastPage) => {
      const { current_page, last_page } = lastPage.meta;
      return current_page < last_page ? current_page + 1 : undefined;
    },

    staleTime: 1000 * 30, // 30s
    cacheTime: 1000 * 60 * 5, // 5 min
  });

  const posts =
    query.data?.pages.flatMap((page) => page.data) ?? [];

  return {
    posts,
    isLoading: query.isLoading,
    isFetchingMore: query.isFetchingNextPage,
    error: query.error,
    hasMore: query.hasNextPage,
    loadMore: query.fetchNextPage,
    refresh: query.refetch,
  };
}
