/**
 * Global password-strength policy, shared by every form that creates, changes
 * or resets a password. The checks must stay in sync with the backend rule
 * (App\Rules\StrongPassword) — this is the single frontend source of truth.
 */

export const PASSWORD_REQUIREMENTS = [
    { key: 'minLength', label: 'At least 8 characters', test: (value) => value.length >= 8 },
    { key: 'hasUppercase', label: 'One uppercase letter (A-Z)', test: (value) => /[A-Z]/.test(value) },
    { key: 'hasLowercase', label: 'One lowercase letter (a-z)', test: (value) => /[a-z]/.test(value) },
    { key: 'hasNumber', label: 'One number (0-9)', test: (value) => /[0-9]/.test(value) },
    { key: 'hasSymbol', label: 'One special character (!@#$%^&*)', test: (value) => /[^A-Za-z0-9\s]/.test(value) },
];

/**
 * Validate a password against the global policy.
 *
 * @param {string} password
 * @returns {{ minLength: boolean, hasUppercase: boolean, hasLowercase: boolean, hasNumber: boolean, hasSymbol: boolean, isValid: boolean }}
 */
export function validatePassword(password) {
    const value = password || '';
    const checks = {};
    for (const rule of PASSWORD_REQUIREMENTS) {
        checks[rule.key] = rule.test(value);
    }
    checks.isValid = PASSWORD_REQUIREMENTS.every((rule) => checks[rule.key]);
    return checks;
}

/**
 * Human-readable labels for the requirements that are still unmet.
 *
 * @param {string} password
 * @returns {string[]}
 */
export function missingPasswordRequirements(password) {
    const checks = validatePassword(password);
    return PASSWORD_REQUIREMENTS
        .filter((rule) => !checks[rule.key])
        .map((rule) => rule.label);
}
