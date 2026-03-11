# Security Policy

## Supported Versions

We take security seriously and provide security updates for the following versions:

| Version | Supported |
| ------- | --------- |
| 0.3.x   | ✅ Yes    |
| 0.2.x   | ❌ No     |
| 0.1.x   | ❌ No     |

Only the latest released minor version of Uplinkr receives security updates. Security fixes are published to the default branch and released as patch versions.

## Reporting a Vulnerability

**Please report security issues privately by email:**

📧 **Email:** sascha@uplinkr.dev

**Please do NOT open public issues for security vulnerabilities.**

### What to Include

When reporting a security vulnerability, please include:

- Description of the vulnerability
- Steps to reproduce the issue
- Affected versions
- Potential impact assessment
- Any suggested fixes (if applicable)

### Response Timeline

- **Initial Response:** We aim to acknowledge your report within 48 hours
- **Status Updates:** We will keep you informed about the progress
- **Resolution:** We strive to release security fixes as quickly as possible, depending on severity and complexity

### Disclosure Policy

- Security issues will be disclosed publicly only after a fix has been released
- We will credit reporters in the security advisory (unless you prefer to remain anonymous)
- Coordinated disclosure is preferred to protect all users

### Language

Reports can be submitted in **English** or **German**.

## Security Best Practices

When using Uplinkr, please follow these recommendations:

- Always use the latest version
- Secure webhook endpoints with proper authentication
- Review email notification settings to avoid information disclosure
- Protect file-based Uplinkr storage from public access
- Treat probe headers, request bodies, and webhook secrets as sensitive data
- Restrict filesystem access to `uplinkr/settings.json`, project `settings.json`, `state.json`, and probe result files
- Use HTTPS for all monitored URLs when possible
- Regularly review and rotate any API keys or credentials used in probes

If you are unsure whether an issue belongs to Uplinkr itself, its configuration, or an affected dependency, please report it privately anyway. Ambiguous reports are preferable to missed security issues.
