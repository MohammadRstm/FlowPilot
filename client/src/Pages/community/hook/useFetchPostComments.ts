import { useQuery } from "@tanstack/react-query"
import { returnDataFormat } from "../../utils/returnApiDataFormat";
import { api } from "../../../api/client";

export const useFetchPostComments = (postId: number , enabled: boolean) => {
  return useQuery({
    queryKey: ["post-comments", postId],
    queryFn: () => fetchPostComments(postId),
    enabled: enabled && !!postId,
  });
};

const fetchPostComments = async (postId : number) => {
    const resp = await api.get(`auth/community/comments/${postId}`);
    return returnDataFormat(resp);
}