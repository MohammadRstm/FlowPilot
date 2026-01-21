import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { getToken } from "../../../api/auth";
import { useLoginMutation } from "./useLoginMutation";
import { useLoginGoogleMutation } from "./useLoginGoogleMutation";

declare global {
  interface Window {
    google: any;
  }
}

export function useLogin() {
  const navigate = useNavigate();

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");

  const loginMutation = useLoginMutation();
  const googleMutation = useLoginGoogleMutation();

  useEffect(() => {
    const token = getToken();
    if (token) navigate("/");
  }, [navigate]);

  useEffect(() => {
    let interval: number;

    const initGoogle = () => {
      if (!window.google) return;

      window.google.accounts.id.initialize({
        client_id: import.meta.env.VITE_GOOGLE_CLIENT_ID,
        callback: handleGoogleLogin,
        auto_select: false,
      });

      const btn = document.getElementById("google-login-btn");
      if (btn) {
        window.google.accounts.id.renderButton(btn, {
          theme: "outline",
          size: "large",
          text: "continue_with",
          shape: "rectangular",
          width: 400,
        });
      }

      window.google.accounts.id.disableAutoSelect();
      clearInterval(interval);
    };

    interval = window.setInterval(initGoogle, 100);
    return () => clearInterval(interval);
  }, []);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    loginMutation.mutate({ email, password });
  };

  const handleGoogleLogin = (response: any) => {
    googleMutation.mutate(response);
  };

  return {
    email,
    password,
    setEmail,
    setPassword,
    loading: loginMutation.isPending || googleMutation.isPending,
    error: loginMutation.error || googleMutation.error,
    handleSubmit,
  };
}
