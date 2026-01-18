// src/pages/profile/hooks/useProfile.ts
import { useEffect, useState, useCallback } from "react";
import { api } from "../../../api/client";
import type { ProfileApiShape } from "../types";

export const useProfile = (userId?: string | undefined) => {
  const [profile, setProfile] = useState<ProfileApiShape | null>(null);
  const [loading, setLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);

  const fetchProfile = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await api.get("/profileDetails", {
        params: userId ? { user_id: userId } : undefined,
      });
      const payload = res.data?.data ?? null;
      if (!payload) {
        setError("Unexpected server response.");
        setProfile(null);
      } else {
        setProfile(payload);
      }
    } catch (err) {
      console.error("Failed to fetch profileDetails:", err);
      setError("Failed to load profile data.");
      setProfile(null);
    } finally {
      setLoading(false);
    }
  }, [userId]);

  useEffect(() => {
    let mounted = true;
    // safe-guard to avoid state updates after unmount
    fetchProfile();
    return () => {
      mounted = false;
    };
  }, [fetchProfile]);

  return { profile, setProfile, loading, error, refresh: fetchProfile };
};
