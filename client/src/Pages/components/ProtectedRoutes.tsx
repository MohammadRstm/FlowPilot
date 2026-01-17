import { useContext } from "react";
import { Navigate } from "react-router-dom";
import { AuthContext, type AuthContextType } from "../../context/AuthContext";

export default function ProtectedRoute({ children }: any) {
  const { user, loading } = useContext<AuthContextType | null>(AuthContext);

  if (loading) return null;                           

  if(!user){
    return <Navigate to="/login" replace />;
  }

  return children;
}
