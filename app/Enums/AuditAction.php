<?php

namespace App\Enums;

/**
 * Canonical vocabulary of security-sensitive actions recorded in audit_logs.
 *
 * Each case maps 1:1 to the audit_logs.action column. Keeping this a
 * backed enum means the logged vocabulary is closed and queryable.
 */
enum AuditAction: string
{
    // Authentication
    case Login = 'login';
    case LoginFailed = 'login.failed';
    case Logout = 'logout';
    case PasswordResetLink = 'password.reset.link';
    case PasswordReset = 'password.reset';
    case PasswordChange = 'password.change';
    case PasswordChangeFailed = 'password.change.failed';
    case AdminPasswordReset = 'admin.password.reset';
    case PasswordChangeTemporaryLogin = 'password.change.temporary.login';
    case PasswordChangeCodeSent = 'password.change.code.sent';
    case PasswordChangeVerified = 'password.change.verified';
    case PasswordChangeVerificationFailed = 'password.change.verification.failed';
    case PasswordChangeVerificationExpired = 'password.change.verification.expired';
    case EmailVerified = 'email.verified';
    case UserRegistered = 'user.registered';

    // User Management
    case UserCreated = 'user.created';
    case UserUpdated = 'user.updated';
    case UserDeleted = 'user.deleted';
    case RoleChanged = 'role.changed';

    // Complaint Management
    case ComplaintCreated = 'complaint.created';
    case ComplaintAssigned = 'complaint.assigned';
    case ComplaintUpdated = 'complaint.updated';
    case ComplaintClosed = 'complaint.closed';
    case ComplaintReopened = 'complaint.reopened';
    case ComplaintStatusChanged = 'complaint.status.changed';

    // Administration
    case SettingsUpdated = 'settings.updated';
    case CategoryCreated = 'category.created';
    case CategoryUpdated = 'category.updated';
    case CategoryDeleted = 'category.deleted';
    case DepartmentCreated = 'department.created';
    case DepartmentUpdated = 'department.updated';
    case DepartmentDeleted = 'department.deleted';

    // IPS / Intrusion Detection
    case IntrusionSqli = 'intrusion.sqli';
    case IntrusionXss = 'intrusion.xss';
    case IpBlocked = 'ip.blocked';
    case RateLimitExceeded = 'rate.limit.exceeded';
    case IpUnblocked = 'ip.unblocked';

    /**
     * Human-readable label for display and filtering.
     */
    public function label(): string
    {
        return match ($this) {
            self::Login => 'Login',
            self::LoginFailed => 'Failed login',
            self::Logout => 'Logout',
            self::PasswordResetLink => 'Password reset link requested',
            self::PasswordReset => 'Password reset',
            self::PasswordChange => 'Password changed',
            self::PasswordChangeFailed => 'Password change failed',
            self::AdminPasswordReset => 'Password reset by administrator',
            self::PasswordChangeTemporaryLogin => 'Temporary password login',
            self::PasswordChangeCodeSent => 'Password change verification code sent',
            self::PasswordChangeVerified => 'Password change verified',
            self::PasswordChangeVerificationFailed => 'Password change verification failed',
            self::PasswordChangeVerificationExpired => 'Password change verification expired',
            self::EmailVerified => 'Email verified',
            self::UserRegistered => 'User registered',
            self::UserCreated => 'User created',
            self::UserUpdated => 'User updated',
            self::UserDeleted => 'User deleted',
            self::RoleChanged => 'Role changed',
            self::ComplaintCreated => 'Complaint created',
            self::ComplaintAssigned => 'Complaint assigned',
            self::ComplaintUpdated => 'Complaint updated',
            self::ComplaintClosed => 'Complaint closed',
            self::ComplaintReopened => 'Complaint reopened',
            self::ComplaintStatusChanged => 'Complaint status changed',
            self::SettingsUpdated => 'Application settings updated',
            self::CategoryCreated => 'Complaint category created',
            self::CategoryUpdated => 'Complaint category updated',
            self::CategoryDeleted => 'Complaint category deleted',
            self::DepartmentCreated => 'Department created',
            self::DepartmentUpdated => 'Department updated',
            self::DepartmentDeleted => 'Department deleted',
            self::IntrusionSqli => 'SQL injection attempt detected',
            self::IntrusionXss => 'Cross-site scripting attempt detected',
            self::IpBlocked => 'IP address blocked',
            self::RateLimitExceeded => 'Rate limit exceeded',
            self::IpUnblocked => 'IP address unblocked',
        };
    }
}
