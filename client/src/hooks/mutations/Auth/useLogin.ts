import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { getToken, googleLogin, login as loginRequest, setToken } from "../../../api/auth";

declare global {
  interface Window {
    google: any;
  }
}

export function useLogin() {
  const navigate = useNavigate();

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);


  const initializeGoogleHandShake = () => {
    window.google.accounts.id.initialize({
      client_id: import.meta.env.VITE_GOOGLE_CLIENT_ID,
      callback: handleGoogleLogin,
      auto_select: false,
    });
  }

  const initializeGoogleButton = () =>{
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
  }

  useEffect(() => {
    const token = getToken();
    if (token) navigate("/");
  }, [navigate]);

  useEffect(() => {
    let interval: number;

    const initGoogle = () => {
      if (!window.google) return;

      initializeGoogleHandShake();
      initializeGoogleButton();

      window.google.accounts.id.disableAutoSelect();
      clearInterval(interval);
    };

    interval = window.setInterval(initGoogle, 100);
    return () => clearInterval(interval);
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setLoading(true);

    callLoginApi(loginRequest , {email , password} , "Failed to login");
  };

  const handleGoogleLogin = async (response: any) => {
    setError(null);
    setLoading(true);

    callLoginApi(googleLogin , response , "Google login failed");
  };

  const callLoginApi = async (apiCall : CallableFunction , apiCallData : any , defaultErrorMessage : string) => {
    try{
      const { token } = await apiCall(apiCallData);
      setToken(token);
      navigate("/");
    }catch(err : any){
      const message = err.response?.data?.message || err.message || defaultErrorMessage;
      setError(message);
    }finally{
      setLoading(false);
    }
  }


  return {
    email,
    password,
    loading,
    error,
    setEmail,
    setPassword,
    handleSubmit,
  };
}
