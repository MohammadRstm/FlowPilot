type LogLevel = "debug" | "info" | "error";

const format = (level: LogLevel) => {
  const time = new Date().toISOString();
  return `[${time}] [${level.toUpperCase()}]`;
};

export const Logger = {
  debug: (message: unknown, ...optional: unknown[]) => {
    console.debug(format("debug"), message, ...optional);
  },

  info: (message: unknown, ...optional: unknown[]) => {
    console.info(format("info"), message, ...optional);
  },

  error: (message: unknown, ...optional: unknown[]) => {
    console.error(format("error"), message, ...optional);
  }
};
