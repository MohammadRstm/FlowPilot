import { appendFile, mkdir } from "fs/promises"
import { dirname, resolve } from "path"
import { fileURLToPath } from "url"

const __dirname = dirname(fileURLToPath(import.meta.url))
const LOG_PATH = resolve(__dirname, "..", "logs", "log.log")

export async function log(...parts) {
	try {
		const dir = dirname(LOG_PATH)
		await mkdir(dir, { recursive: true })
	} catch (e) {
		// ignore
	}

	const time = new Date().toISOString()
	const message = parts.map(p => {
		if (typeof p === "string") return p
		try { return JSON.stringify(p, null, 2) } catch { return String(p) }
	}).join(" ")

	const line = `${time} ${message}\n`
	try {
		await appendFile(LOG_PATH, line, { encoding: "utf8" })
	} catch (e) {
		// fallback to console.error if file write fails
		console.error("Failed to write log:", e)
	}
}