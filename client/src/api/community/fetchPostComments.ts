import { api } from "../client"
import { returnDataFormat } from "../utils";


export const fetchPostComments = async (postId : number) => {
    const resp = await api.get(`auth/community/comments/${postId}`);
    const data = returnDataFormat(resp);
    console.log(data);
    return returnDataFormat(resp);
}