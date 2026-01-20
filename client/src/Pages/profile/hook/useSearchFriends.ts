import { useQuery } from "@tanstack/react-query";
import { fetchFriendsSuggestions } from "../../../api/profile/fetchFriendsSuggestions";

export const useSearchForFriends = (name : string) => {
  return useQuery({
    queryKey: ["fetch-friends-suggestions"],
    queryFn: () => fetchFriendsSuggestions(name),
    enabled: true,
  });
};
