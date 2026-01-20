import { api } from "../client"
import { returnDataFormat } from "../utils";


export const fetchPostComments = async (postId : number) => {
    const resp = await api.get(`auth/community/comments/${postId}`);
    return returnDataFormat(resp);
}