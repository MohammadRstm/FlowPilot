import { api } from "../client"
import { returnDataFormat } from "../utils";

export const isBeingFollowedByUser = async (userId : number | undefined) =>{
    const response = await api.get(`auth/profile/isFollowed/${userId}`);

    return returnDataFormat(response);
}