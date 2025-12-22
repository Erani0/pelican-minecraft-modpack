# Security Policy

## 🔒 Supported Versions

We release patches for security vulnerabilities for the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |

## 🐛 Reporting a Vulnerability

We take the security of our plugin seriously. If you have discovered a security vulnerability, please follow these steps:

### DO NOT

- **Do not** open a public GitHub issue for security vulnerabilities
- **Do not** disclose the vulnerability publicly until it has been addressed

### DO

1. **Email the maintainer** directly at the email associated with the GitHub account
   - Or use GitHub's private vulnerability reporting feature
   - Subject: `[SECURITY] Brief description of vulnerability`

2. **Include in your report**:
   - Description of the vulnerability
   - Steps to reproduce the issue
   - Potential impact
   - Suggested fix (if you have one)
   - Your contact information for follow-up

3. **Wait for acknowledgment**
   - We will acknowledge receipt within 48 hours
   - We will provide an initial assessment within 7 days
   - We will keep you updated on the progress toward a fix

## 🛡️ Security Considerations

### API Keys

- **CurseForge API keys** are stored in the environment configuration
- Keys are never exposed in logs or error messages
- Ensure your `.env` file has appropriate permissions (600)

### File Operations

- The plugin can delete server files if explicitly requested during installation
- This operation requires appropriate user permissions
- Always backup your server before installing modpacks

### Network Requests

- All API requests include timeouts to prevent hanging
- Invalid SSL certificates are rejected
- User-Agent headers identify the plugin

### Caching

- Cached data is stored in Laravel's cache system
- No sensitive information is cached
- Cache keys are hashed to prevent enumeration

## 🔐 Best Practices for Users

1. **Keep the plugin updated** to the latest version
2. **Secure your CurseForge API key**:
   - Don't commit `.env` to version control
   - Use environment variables in production
   - Rotate keys periodically

3. **Review permissions**:
   - Ensure Pelican users have appropriate permissions
   - Limit access to plugin settings

4. **Monitor logs**:
   - Check `/var/www/pelican/storage/logs/` regularly
   - Watch for unusual API activity

5. **Backup before installations**:
   - Always backup server files before installing modpacks
   - Test installations on non-production servers first

## 🔄 Security Update Process

When a security vulnerability is reported:

1. **Triage** - Assess severity and impact
2. **Develop Fix** - Create and test a patch
3. **Release** - Deploy fix in new version
4. **Notify** - Inform users via:
   - GitHub Security Advisory
   - Release notes
   - README update

## 📋 Security Checklist for Contributors

When contributing code, ensure:

- [ ] No hardcoded credentials or API keys
- [ ] Input validation for all user inputs
- [ ] SQL injection prevention (if applicable)
- [ ] XSS prevention in UI components
- [ ] CSRF protection for forms
- [ ] Proper error handling without information disclosure
- [ ] Secure file operations with path validation
- [ ] Rate limiting for API calls
- [ ] Proper logging without sensitive data

## 🚨 Known Security Considerations

### Modpack Installation

- Installing modpacks downloads files from external sources
- Always verify the source and reputation of modpacks
- The plugin cannot guarantee the safety of third-party modpack content
- Use reputable platforms (Modrinth, CurseForge recommended)

### API Integrations

- Third-party APIs may change without notice
- API keys should be treated as sensitive credentials
- Network requests are made to external services

## 📞 Contact

For security concerns:
- Use GitHub's private vulnerability reporting
- Or open a security advisory on the repository
- For urgent issues, contact the maintainer directly

## 🙏 Acknowledgments

We appreciate security researchers who responsibly disclose vulnerabilities. Contributors who report valid security issues will be:

- Acknowledged in the security advisory (if desired)
- Mentioned in release notes
- Credited in the repository

---

**Thank you for helping keep this plugin and its users safe!**
