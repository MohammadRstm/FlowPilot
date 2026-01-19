import { api } from "../client"
import  { returnDataFormat } from "../utils";

export const fetchExport = async (postId : number) =>{
    const response = await api.get(`auth/community/export/${postId}`);
    return returnDataFormat(response);
}