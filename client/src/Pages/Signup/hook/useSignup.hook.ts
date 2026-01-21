import { useMutation } from "@tanstack/react-query";
import { register as registerRequest } from "../../../api/auth";
import type { SignupFormValues } from "../../../validation/signup.schema";

export const useSignup = () => {
  return useMutation({
    mutationFn: async (data: SignupFormValues) => {
      return registerRequest({
        first_name: data.firstName,
        last_name: data.lastName,
        email: data.email,
        password: data.password,
      });
    },
  });
};
