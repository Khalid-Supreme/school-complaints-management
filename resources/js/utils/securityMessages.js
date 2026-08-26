/**
 * Central security -> human message map.
 * Only codes listed here are rendered. Raw server strings are never shown
 * to prevent leaking rule names, IPs, or internals.
 */

const MAP = {
    IP_BLOCKED: {
        severity: 'warn',
        summary: 'Access temporarily restricted',
        detail: 'For your protection, access from this connection has been temporarily restricted. Please wait and try again later.',
        life: 8000,
    },
    USER_BLOCKED: {
        severity: 'warn',
        summary: 'Account temporarily restricted',
        detail: 'Your account has been temporarily restricted because unusual activity was detected. Please try again later or contact your administrator if you believe this was a mistake.',
        life: 8000,
    },
    INTRUSION_DETECTED: {
        severity: 'error',
        summary: 'Security check triggered',
        detail: 'Your request was flagged for a security review and was not processed. Please check your input and try again. If you believe this is a mistake, contact support.',
        life: 8000,
    },
    RATE_LIMITED: {
        severity: 'warn',
        summary: 'Please slow down',
        detail: 'We’ve received too many requests from your connection in a short period. Please wait a moment and try again.',
        life: 6000,
    },
};

function formatRetry(seconds) {
    if (!seconds || isNaN(seconds) || seconds <= 0) return null;
    const s = Math.ceil(Number(seconds));
    if (s < 60) return `Please try again in about ${s} seconds.`;
    if (s < 3600) {
        const m = Math.ceil(s / 60);
        return `Please try again in approximately ${m} minute${m === 1 ? '' : 's'}.`;
    }
    const h = Math.ceil(s / 3600);
    if (h < 24) return `Please try again in about ${h} hour${h === 1 ? '' : 's'}.`;
    const d = Math.ceil(h / 24);
    return `Please try again in about ${d} day${d === 1 ? '' : 's'}.`;
}

export function isSecurityCode(code) {
    return code && Object.prototype.hasOwnProperty.call(MAP, String(code).toUpperCase());
}

export function getSecurityMessage(code, retryAfterSeconds = null) {
    const entry = MAP[String(code || '').toUpperCase()];
    if (!entry) return null;
    const retryText = formatRetry(retryAfterSeconds);
    return {
        ...entry,
        detail: retryText ? `${entry.detail} ${retryText}` : entry.detail,
    };
}

export function getThrottleMessage(retryAfterSeconds = null) {
    const base = MAP.RATE_LIMITED;
    const retryText = formatRetry(retryAfterSeconds);
    return {
        ...base,
        detail: retryText ? `${base.detail} ${retryText}` : base.detail,
    };
}

/**
 * Extract trusted security code from a response body.
 * Only returns a code if it is in the allowlist.
 */
export function extractSecurityCode(body) {
    if (!body || typeof body !== 'object') return null;
    const raw = body.code || body.error_code;
    if (isSecurityCode(raw)) return String(raw).toUpperCase();
    return null;
}
