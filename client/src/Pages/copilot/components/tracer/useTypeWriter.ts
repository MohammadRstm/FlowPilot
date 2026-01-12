import { useEffect, useState } from "react";
import { typingBarrier } from "./typingBarrier";

export function useTypewriter(text: string, speed = 12) {
  const [out, setOut] = useState("");

  useEffect(() => {
    if (!text) return;

    typingBarrier.start();
    setOut("");

    let i = 0;
    const t = setInterval(() => {
      i++;
      setOut(text.slice(0, i));

      if (i >= text.length) {
        clearInterval(t);
        typingBarrier.done();
      }
    }, speed);

    return () => {
      clearInterval(t);
      typingBarrier.done();
    };
  }, [text]);

  return out;
}
