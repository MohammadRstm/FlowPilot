import { api } from "../client"
import { returnDataFormat } from "../utils";


export const postComment =async  (postId : number , content : string) =>{
    const payload = {
        content 
    };
    const resp = await api.post(`auth/community/postComment/${postId}` , payload);

    return returnDataFormat(resp);
}