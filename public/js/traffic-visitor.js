(function () {
    const cookieName = window.TrafficVisitorConfig?.cookieName || "sei_visitor_id";
    const storageKey = cookieName;

    function createVisitorId() {
        if (window.crypto?.randomUUID) {
            return window.crypto.randomUUID();
        }

        if (window.crypto?.getRandomValues) {
            const bytes = new Uint8Array(16);
            window.crypto.getRandomValues(bytes);

            return Array.from(bytes, (byte) => byte.toString(16).padStart(2, "0")).join("");
        }

        return `${Date.now().toString(36)}-${Math.random().toString(36).slice(2)}`;
    }

    function getVisitorId() {
        try {
            const existing = window.localStorage.getItem(storageKey);
            if (existing) return existing;

            const visitorId = createVisitorId();
            window.localStorage.setItem(storageKey, visitorId);
            return visitorId;
        } catch (_) {
            return createVisitorId();
        }
    }

    const visitorId = getVisitorId();
    const secure = window.location.protocol === "https:" ? "; Secure" : "";
    document.cookie = `${cookieName}=${encodeURIComponent(visitorId)}; Max-Age=31536000; Path=/; SameSite=Lax${secure}`;

    window.SeiTraffic = {
        event(name, parameters = {}) {
            if (typeof window.gtag !== "function") return;

            window.gtag("event", name, {
                ...parameters,
                transport_type: "beacon",
            });
        },
    };

    document.addEventListener("click", (event) => {
        const trigger = event.target.closest?.("[data-traffic-event]");
        if (!trigger) return;

        window.SeiTraffic.event(trigger.dataset.trafficEvent);
    });
})();
