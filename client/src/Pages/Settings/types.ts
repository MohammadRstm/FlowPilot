export type UserAccountType = {
  normalAccount: boolean;
  googleAccount: boolean;
};

export type N8nLinkPayload = {
  base_url: string;
  api_key: string;
}

export type SetPasswordPayload = { 
  current_password?: string;
  new_password: string;
  new_password_confirmation: string;
}