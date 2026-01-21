import { useMutation } from "@tanstack/react-query";
import { fetchFriendsSuggestions } from "../../../api/profile/fetchFriendsSuggestions";

export function useSearchFriendsMutation() {
  return useMutation({
    mutationFn: async (name: string) => fetchFriendsSuggestions(name),
  });
}