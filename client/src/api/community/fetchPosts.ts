import { api } from "../client"
import { returnDataFormat } from "../utils";

export const fetchPosts = async ({pageParam = 1}) =>{
    const res = await api.get(`auth/community/posts?page=${pageParam}`);

    return returnDataFormat(res);
}