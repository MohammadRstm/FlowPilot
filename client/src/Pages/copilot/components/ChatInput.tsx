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

       <button
        type="button"
        className="chat-send-button"
        disabled={disabled || !value.trim()}
        onClick={onSubmit}
        aria-label="Send"
      >
        <SendArrowIcon />
      </button>
    </div>
  );
}

function SendArrowIcon() {
  return (
    <svg
      width="18"
      height="18"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    >
      <path d="M5 12h14" />
      <path d="M13 5l7 7-7 7" />
    </svg>
  );
}
