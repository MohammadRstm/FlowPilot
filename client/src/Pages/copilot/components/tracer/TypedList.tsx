import { useEffect, useState } from "react";

export function TypedList<T>({
  items,
  renderItem,
}: {
  items: T[];
  renderItem: (item: T) => React.ReactNode;
}) {
  const [count, setCount] = useState(0);

  useEffect(() => {
    let cancelled = false;

    const run = async () => {
      for (let i = 0; i < items.length; i++) {
        if (cancelled) return;
        setCount(c => c + 1);
        await new Promise(r => setTimeout(r, 80));
      }
    };

    run();
    return () => {
      cancelled = true;
    };
  }, [items]);

  return (
    <ul>
      {items.slice(0, count).map((item, i) => (
        <li key={i}>{renderItem(item)}</li>
      ))}
    </ul>
  );
}
