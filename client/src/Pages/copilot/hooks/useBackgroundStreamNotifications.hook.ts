import { useEffect } from "react";
import { useToast } from "../../../context/toastContext";
import { backgroundStreamService } from "../services/backgroundStreamService";

/**
 * Global hook that listens for background stream completions
 * and shows toasts when workflows are ready.
 * Should be mounted at the app root level.
 */
export function useBackgroundStreamNotifications() {
  const { showToast } = useToast();

  useEffect(() => {
    const checkStreams = setInterval(() => {
      const activeStreams = backgroundStreamService.getActiveStreams();

      // Check if any streams are in "done" stage and show notification
      activeStreams.forEach((stream) => {
        if (stream.stage === "done") {
          showToast("Your workflow is ready!", "success");
          backgroundStreamService.stopStream(stream.key);
        }
      });
    }, 1000);

    return () => clearInterval(checkStreams);
  }, [showToast]);
}
