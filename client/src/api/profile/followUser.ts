import { api } from "../client"
import { returnDataFormat } from "../utils";

export const followUser = async (userId: number | undefined) =>{
    const res = api.post(`auth/profile/follow/${userId}`);

    return returnDataFormat(res);
}