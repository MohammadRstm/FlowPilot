import { useQuery } from "@tanstack/react-query";

export const useProfileQuery = (name : string) => {
  return useQuery({
    queryKey: ["fetch-friends-suggestions"],
    queryFn: () => fetchFriendsSuggestion(name),
    enabled: true,
  });
};
