import { api } from "../client";
import { returnDataFormat } from "../utils";


export const uploadAvatar =async  (file: File ) => {
    const formData = new FormData();
    formData.append("avatar", file);

    const resp = await api.post("auth/profile/avatar" , formData , {
        headers: { "Content-Type": "multipart/form-data" },
    });

    return returnDataFormat(resp);

     
}