import { api } from "../client"
import { returnDataFormat } from "../utils";


export const toggleLike = async (postId : number)=>{
    const response = await api.post(`auth/community/toggleLike/${postId}`);

    return returnDataFormat(response);
}