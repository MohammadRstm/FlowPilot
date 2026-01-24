import {
  QueryClient,
  QueryCache,
  MutationCache,
} from "@tanstack/react-query";
import { handleApiError } from "../Pages/utils/handleErrorMessage";
import { toastStore } from "../context/toastStore";

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: false,
      refetchOnWindowFocus: false,
    },
    mutations: {
      retry: false,
    },
  },

  queryCache: new QueryCache({
    onError: (error) => {
      handleApiError(error, toastStore.show);
    },
  }),

  mutationCache: new MutationCache({
    onError: (error) => {
      handleApiError(error, toastStore.show);
    },
  }),
});
