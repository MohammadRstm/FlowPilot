import fs from "fs";
import { LOG_FILE } from "../config/paths";

export function log(message: string): void {
  const timestamp = new Date().toISOString();
  const line = `[${timestamp}] ${message}\n`;

  fs.appendFileSync(LOG_FILE, line);
  console.log(message);
}
