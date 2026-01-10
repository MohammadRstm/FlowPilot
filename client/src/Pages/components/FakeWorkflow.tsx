import { useEffect, useRef } from "react";

export default function FakeWorkflow() {
  
    const ref = useRef<HTMLDivElement>(null); // or whatever element type


    useEffect(() => {
        const el = ref.current;
        if (!el) return;

        let mouse = { x: 0, y: 0 };

        const updateMouse = (e : MouseEvent) => {
        mouse.x = e.clientX;
        mouse.y = e.clientY;
        checkHover();
        };

        const checkHover = () => {
        const rect = el.getBoundingClientRect();
        const inside =
            mouse.x >= rect.left &&
            mouse.x <= rect.right &&
            mouse.y >= rect.top &&
            mouse.y <= rect.bottom;
        if (inside) {
            el.classList.add("active");
            el.style.setProperty("--x", `${mouse.x - rect.left}px`);
            el.style.setProperty("--y", `${mouse.y - rect.top}px`);
        } else {
            el.classList.remove("active");
        }
        };


        // This is the missing piece
        window.addEventListener("scroll", checkHover, { passive: true });
        window.addEventListener("mousemove", updateMouse);

        return () => {
        window.removeEventListener("scroll", checkHover);
        window.removeEventListener("mousemove", updateMouse);
        };
    }, []);

    return <div ref={ref} className="fake-workflow" />;
}
