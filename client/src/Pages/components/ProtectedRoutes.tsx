import { Navigate } from "react-router-dom";
import { useAuth } from "../../context/useAuth";

export default function ProtectedRoute({ children }: any) {
  const { user, loading } = useAuth();

  if (loading) return null;                           

  if(!user){
    return <Navigate to="/login" replace />;
  }

  return children;
}
