import { useMutation } from "@tanstack/react-query";
import { returnDataFormat } from "../../utils/returnApiDataFormat";
import { api } from "../../../api/client";

export function useSearchFriendsMutation() {
  return useMutation({
    mutationFn: async (name: string) => fetchFriendsSuggestions(name),
  });
}

const fetchFriendsSuggestions = async (name: string) =>{
    const resp = await api.get(`auth/profile/searchFriends/${name}`);

    return returnDataFormat(resp);
}