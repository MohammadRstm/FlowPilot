import { api } from "../client"
import { returnDataFormat } from "../utils";


export const toggleCommentLike = async (commentId : number)=>{
    const response = await api.post(`auth/community/toggleCommentLike/${commentId}`);

    return returnDataFormat(response);
}