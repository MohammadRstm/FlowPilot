import { useTypewriter } from "./useTypeWriter";

export function TypedLine({ value }: { value: string }) {
  const typed = useTypewriter(value);
  return <span className="typed-line">{typed}</span>;
}
