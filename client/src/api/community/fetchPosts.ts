import type { QueryFunctionContext } from "@tanstack/react-query";
import { api } from "../client"
import { returnDataFormat } from "../utils";

export const fetchPosts = async ( ctx: QueryFunctionContext) =>{
    const pageParam = (ctx.pageParam as number) ?? 1;
    const res = await api.get(`auth/community/posts?page=${pageParam}`);

    return returnDataFormat(res);
}