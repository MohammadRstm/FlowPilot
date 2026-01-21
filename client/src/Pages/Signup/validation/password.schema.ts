import { z } from "zod";
import zxcvbn from "zxcvbn";

const passwordWeaknessThreshold = import.meta.env.VITE_PASSWORD_THRESHOLD; 

export const passwordSchema = z
  .string()
  .min(8, "Password must be at least 8 characters")
  .regex(/[A-Z]/, "Password must contain at least one uppercase letter")
  .refine(
    (password) => zxcvbn(password).score >= passwordWeaknessThreshold,
    "Password is too weak"
  );
