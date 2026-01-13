import { useEffect, useState } from "react";

export function useTypewriter(text: string, speed = 9) {
  const [out, setOut] = useState("");

  useEffect(() => {
    if (!text) return;
    
    setOut("");
    let i = 0;

    const t = setInterval(() => {
      i++;
      setOut(text.slice(0, i));

      if (i >= text.length) {
        clearInterval(t);
      }
    }, speed);

    return () => {
      clearInterval(t);
    };
  }, [text]);

  return out;
}
