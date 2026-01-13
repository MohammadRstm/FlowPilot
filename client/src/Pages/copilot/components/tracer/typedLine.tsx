import { useEffect, useState } from "react";

export function TypedLine({ value }: { value: string }) {
  const [text, setText] = useState("");

  useEffect(() => {
    let i = 0;
    let cancelled = false;

    const tick = async () => {
      while (i < value.length && !cancelled) {
        setText(value.slice(0, i + 1));
        i++;
        await new Promise(r => setTimeout(r, 20));
      }
    };

    tick();
    return () => {
      cancelled = true;
    };
  }, [value]);

  return (
    <span className="typed-line">
      {text}
      {text.length < value.length && <span className="cursor">▌</span>}
    </span>
  );
}
