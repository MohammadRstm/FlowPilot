import { api } from "../client"
import { returnDataFormat } from "../utils";


export const fetchFriendsSuggestions = async (name: string) =>{
    const resp = await api.get(`auth/profile/searchFriends/${name}`);

    return returnDataFormat(resp);
}