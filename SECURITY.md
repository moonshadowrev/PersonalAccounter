# Security Policy

## Overview

PersonalAccounter takes security seriously and implements multiple layers of protection to safeguard your financial data. This document outlines our security measures, best practices, and procedures for reporting security vulnerabilities.

## 🛡️ Security Features

### Authentication & Authorization

#### **Multi-Factor Authentication (MFA)**
- **Two-Factor Authentication (2FA)** with Google Authenticator support
- **TOTP (Time-based One-Time Password)** implementation
- **Backup recovery codes** for account recovery
- **Mandatory 2FA** option for enhanced security

#### **Session Management**
- **Secure session handling** with HttpOnly and Secure cookie flags
- **Session regeneration** on authentication to prevent session fixation
- **Configurable session timeouts** to limit exposure
- **Automatic session invalidation** on logout

#### **Password Security**
- **Bcrypt hashing** with automatic salt generation (cost factor 12)
- **Timing attack protection** using `hash_equals()` for comparisons
- **Account lockout** after failed login attempts
- **Rate limiting** on authentication endpoints

### Input Validation & Sanitization

#### **CSRF Protection**
- **Token-based CSRF protection** on all forms
- **Double-submit cookie pattern** for API requests
- **SameSite cookie attributes** for additional protection
- **Automatic token regeneration** on each request

#### **XSS Prevention**
- **Input sanitization** using `htmlspecialchars()` with ENT_QUOTES
- **Output encoding** for all user-generated content
- **Content Security Policy (CSP)** headers
- **X-XSS-Protection** headers for legacy browser support

#### **SQL Injection Prevention**
- **Prepared statements** for all database queries
- **Parameterized queries** using Medoo ORM
- **Input type validation** and casting
- **Whitelist validation** for dynamic table/column names

### API Security

#### **Authentication**
- **API key authentication** with secure key generation
- **Bearer token support** for OAuth-style authentication
- **Rate limiting** per API key with configurable limits
- **Request signing** for sensitive operations

#### **Authorization**
- **Permission-based access control** with granular permissions
- **Role-based API access** (admin, superadmin)
- **Endpoint-specific permission checks**
- **Audit logging** for all API requests

### Data Protection

#### **Encryption**
- **TLS/SSL encryption** in transit (HTTPS required in production)
- **Database connection encryption** when available
- **Sensitive field encryption** for PII data
- **Secure key management** with environment variables

#### **File Upload Security**
- **File type validation** using MIME type checking
- **File size limits** to prevent DoS attacks
- **Upload directory isolation** outside web root
- **Virus scanning** integration capability

## 🔧 Security Configuration

### Environment Variables

```env
# Security Configuration
SESSION_SECURE=true
SESSION_SAMESITE=Strict
LOGIN_ATTEMPTS_LIMIT=5
LOGIN_ATTEMPTS_TIMEOUT=300

# API Security
API_MAX_FAILED_ATTEMPTS=5
API_BLOCK_DURATION=300
API_DEFAULT_RATE_LIMIT=60
API_MAX_RATE_LIMIT=1000

# Encryption
ENCRYPTION_KEY=your-32-character-key-here
```

### Web Server Security Headers

#### **Apache Configuration**
```apache
# Security Headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options SAMEORIGIN
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"

# Content Security Policy
Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'"
```

#### **Nginx Configuration**
```nginx
# Security Headers
add_header X-Content-Type-Options nosniff;
add_header X-Frame-Options SAMEORIGIN;
add_header X-XSS-Protection "1; mode=block";
add_header Referrer-Policy "strict-origin-when-cross-origin";
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload";

# Content Security Policy
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'";
```

### Database Security

#### **MySQL Configuration**
```sql
-- Create dedicated database user
CREATE USER 'personal_accounter'@'localhost' IDENTIFIED BY 'strong-password-here';

-- Grant minimal required permissions
GRANT SELECT, INSERT, UPDATE, DELETE ON personal_accounter.* TO 'personal_accounter'@'localhost';

-- Enable SSL connections
REQUIRE SSL;

-- Flush privileges
FLUSH PRIVILEGES;
```

## 🔍 Security Monitoring & Logging

### Audit Logging

PersonalAccounter logs all security-relevant events:

- **Authentication attempts** (successful and failed)
- **API key usage** and rate limit violations
- **Privilege escalation attempts**
- **Data modification operations**
- **File upload attempts**
- **Suspicious request patterns**

### Log Analysis

```bash
# Monitor failed login attempts
grep "Failed login attempt" logs/app.log | tail -20

# Check API rate limiting
grep "rate limit exceeded" logs/app.log | tail -20

# Monitor CSRF token failures
grep "CSRF token validation failed" logs/app.log | tail -20
```

### Automated Monitoring

Consider implementing automated monitoring for:
- Unusual login patterns
- Multiple failed authentication attempts
- High-volume API requests
- Suspicious database queries
- File upload anomalies

## 🚨 Vulnerability Reporting

### Reporting Process

We take security vulnerabilities seriously. If you discover a security issue, please follow these steps:

1. **DO** create a public GitHub issue
3. **Include** a clear description and reproduction steps
4. **Provide** your contact information for follow-up

### What to Include

- **Vulnerability description** and potential impact
- **Steps to reproduce** the issue
- **Affected versions** or components
- **Proof of concept** (if applicable)
- **Suggested mitigation** (if you have ideas)

### Response Timeline

- **Initial response**: Within 24 hours
- **Vulnerability assessment**: Within 72 hours
- **Fix development**: Based on severity (1-30 days)
- **Patch release**: As soon as possible after fix

### Responsible Disclosure

We follow responsible disclosure practices:
- We'll work with you to understand and resolve the issue
- We'll keep you informed of our progress
- We'll credit you in our security advisory (if desired)
- We ask that you give us reasonable time to fix the issue before public disclosure

## 🏆 Security Best Practices

### For Administrators

#### **Installation Security**
```bash
# Set secure file permissions
chmod 755 -R .
chmod 700 logs/ sessions/
chmod 600 .env config/app.php

# Remove default files
rm -f composer.lock.example .env.example

# Update dependencies regularly
composer update --no-dev
```

#### **Database Security**
- Use a dedicated database user with minimal permissions
- Enable SSL/TLS for database connections
- Regularly backup and test restore procedures
- Monitor database logs for suspicious activity

#### **Server Hardening**
- Keep PHP and web server updated
- Disable unnecessary PHP extensions
- Configure fail2ban for brute force protection
- Use a Web Application Firewall (WAF)

### For Users

#### **Account Security**
- **Enable 2FA** immediately after account creation
- **Use strong passwords** with mixed case, numbers, and symbols
- **Regularly review** account activity and API keys
- **Log out** when finished, especially on shared computers

#### **Data Protection**
- **Regular backups** of your financial data
- **Secure your API keys** and never share them
- **Monitor** for unusual account activity
- **Report** suspicious behavior immediately

### For Developers

#### **Secure Coding**
```php
// Always validate input
$amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);
if ($amount === false || $amount < 0) {
    throw new InvalidArgumentException('Invalid amount');
}

// Use prepared statements
$stmt = $db->prepare("SELECT * FROM expenses WHERE user_id = ?");
$stmt->execute([$userId]);

// Escape output
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// Validate CSRF tokens
if (!hash_equals($_SESSION['_token'], $_POST['_token'])) {
    throw new SecurityException('CSRF token mismatch');
}
```

#### **API Development**
```php
// Check permissions
if (!$this->hasPermission('expenses.read')) {
    $this->forbidden('Permission denied');
}

// Rate limiting
if (!$this->checkRateLimit($apiKey)) {
    $this->rateLimitExceeded();
}

// Input validation
$this->validateRequired($data, ['title', 'amount', 'category_id']);
```

## 🔄 Security Updates

### Staying Secure

- **Subscribe** to security notifications
- **Apply updates** promptly when released
- **Monitor** the changelog for security fixes
- **Test updates** in a staging environment first

### Security Releases

Security updates are released as:
- **Patch releases** (e.g., 1.2.3 → 1.2.4) for minor security fixes
- **Minor releases** (e.g., 1.2.0 → 1.3.0) for moderate security improvements
- **Emergency releases** for critical vulnerabilities

### Notification Channels

- **GitHub Security Advisories**
- **Release notes** on GitHub
- **Security mailing list** (coming soon)
- **Documentation updates**

## 📋 Security Checklist

### Pre-Production Checklist

- [ ] Enable HTTPS with valid SSL certificate
- [ ] Configure security headers
- [ ] Set secure environment variables
- [ ] Review file permissions
- [ ] Test authentication flows
- [ ] Verify CSRF protection
- [ ] Check input validation
- [ ] Test rate limiting
- [ ] Configure logging
- [ ] Set up monitoring

### Regular Security Reviews

**Monthly:**
- [ ] Review access logs
- [ ] Check for failed login attempts
- [ ] Audit API key usage
- [ ] Review user accounts

**Quarterly:**
- [ ] Update dependencies
- [ ] Review security configurations
- [ ] Test backup procedures
- [ ] Audit permissions

**Annually:**
- [ ] Security assessment
- [ ] Penetration testing
- [ ] Compliance review
- [ ] Incident response plan review


---

**Remember**: Security is a shared responsibility. By following these guidelines and best practices, you help keep PersonalAccounter secure for everyone. 