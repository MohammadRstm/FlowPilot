import { useQuery } from "@tanstack/react-query"
import { fetchPostComments } from "../../../api/community/fetchPostComments";

export const useFetchPostComments = (postId: number) => {
  return useQuery({
    queryKey: ["post-comments", postId],
    queryFn: () => fetchPostComments(postId),
    enabled: !!postId,
  });
};
