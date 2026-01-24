import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App.tsx'
import {  QueryClientProvider } from '@tanstack/react-query'
import { AuthProvider } from "./context/AuthContext";
import { ToastProvider } from './context/toastContext.tsx'
import { queryClient } from "./lib/reactQuery"; // <- IMPORTANT



createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <QueryClientProvider client={queryClient}>
      <ToastProvider>
        <AuthProvider>
          <App />
        </AuthProvider>
      </ToastProvider>
    </QueryClientProvider>
  </StrictMode>,
)
