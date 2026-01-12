import { useEffect, useState } from "react";
import { typingBarrier } from "./typingBarrier";

export function TypedArrayItem({ children }: { children: React.ReactNode }) {
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    let mounted = true;

    typingBarrier.wait().then(() => {
      if (mounted) setVisible(true);
    });

    return () => { mounted = false };
  }, []);

  return visible ? <li>{children}</li> : null;
}
