import type { RefObject } from "react";

interface ChatInputProps {
  value: string;
  disabled: boolean;
  textareaRef: RefObject<HTMLTextAreaElement | null>;
  onChange: (e: React.ChangeEvent<HTMLTextAreaElement>) => void;
  onSubmit: () => void;
}

export function ChatInput({
  value,
  disabled,
  textareaRef,
  onChange,
  onSubmit,
}: ChatInputProps) {
  return (
    <div className="bottom-input">
      <textarea
        ref={textareaRef}
        value={value}
        placeholder="Keep them coming"
        disabled={disabled}
        rows={1}
        onChange={onChange}
        onKeyDown={(e) => {
          if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            onSubmit();
          }
        }}
      />
    </div>
  );
}
