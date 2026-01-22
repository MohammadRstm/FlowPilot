import { useInfiniteQuery, type QueryFunctionContext } from "@tanstack/react-query";
import { returnDataFormat } from "../../utils/returnApiDataFormat";
import { api } from "../../../api/client";
import type { ApiResponse } from "../types";

export function useFetchPosts() {
  const query = useInfiniteQuery<ApiResponse>({
    queryKey: ["community-posts"],
    queryFn: fetchPosts,

    getNextPageParam: (lastPage) => {
      const { current_page, last_page } = lastPage.meta;
      return current_page < last_page ? current_page + 1 : undefined;
    },

    initialPageParam: 1,
    staleTime: 1000 * 30, // 30s
    gcTime: 1000 * 60 * 5, // 5 min
  });

  const posts =
    query.data?.pages.flatMap((page) => page.data) ?? [];

  return {
    posts,
    isPending: query.isPending,
    isFetchingMore: query.isFetchingNextPage,
    error: query.error,
    hasMore: query.hasNextPage,
    loadMore: query.fetchNextPage,
    refresh: query.refetch,
  };
}


const fetchPosts = async ( ctx: QueryFunctionContext) =>{
    const pageParam = (ctx.pageParam as number) ?? 1;
    const res = await api.get(`auth/community/posts?page=${pageParam}`);

    return returnDataFormat(res);
}