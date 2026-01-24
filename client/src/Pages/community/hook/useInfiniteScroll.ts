import { useEffect, useRef } from "react";
import { ROOT_MARGIN } from "../constants";

type Props = {
  hasMore: boolean;
  isLoading: boolean;
  onLoadMore: () => void;
  rootMargin?: string;
};

export function useInfiniteScroll({
  hasMore,
  isLoading,
  onLoadMore,
  rootMargin = ROOT_MARGIN,
}: Props) {
  const ref = useRef<HTMLDivElement | null>(null);

  useEffect(() => {
    if (!ref.current) return;

    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting && hasMore && !isLoading) {
          onLoadMore();
        }
      },
      { rootMargin }
    );

    observer.observe(ref.current);

    return () => observer.disconnect();
  }, [hasMore, isLoading, onLoadMore, rootMargin]);

  return ref;
}
