import { api } from "../client"
import { returnDataFormat } from "../utils";

export const followUser = (userId: number) =>{
    const res = api.put(`auth/profile/follow/${userId}`);

    return returnDataFormat(res);
}