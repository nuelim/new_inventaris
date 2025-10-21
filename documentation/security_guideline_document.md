# Security Guidelines for `new_inventaris` Inventory Management System

This document outlines security requirements and best practices tailored to the `new_inventaris` project. It embeds security by design, ensuring a robust and resilient inventory management application.

---

## 1. Introduction

- **Purpose:** Define security controls for authentication, data protection, input validation, API defenses, infrastructure hardening, and dependency management.
- **Scope:** All code, configurations, CI/CD pipelines, cloud infrastructure, and third-party integrations within `new_inventaris`.

---

## 2. Core Security Principles

1. **Security by Design:** Incorporate security reviews at every development milestone.  
2. **Least Privilege:** Grant users and services only required rights—e.g., database users limited to CRUD on inventory tables.  
3. **Defense in Depth:** Layer controls (network ACLs, application checks, data encryption).  
4. **Fail Securely:** On errors, return generic messages; avoid leaking stack traces or PII.  
5. **Secure Defaults:** Ship with strong configurations (e.g., HTTPS only, safe CORS).  
6. **Keep Security Simple:** Favor clear, maintainable controls over ad hoc complex scripts.

---

## 3. Authentication & Access Control

- **Strong Authentication:**  
  • Enforce account password complexity (min. 12 chars, mix of upper/lowercase, digits, symbols).  
  • Store passwords with Argon2 or bcrypt + unique salts.  
- **Session Management:**  
  • Use secure, HttpOnly, SameSite=strict cookies.  
  • Implement idle (15 min) and absolute (24 hr) session timeouts.  
- **Multi-Factor Authentication (MFA):**  
  • Optional for warehouse clerks; mandatory for administrators.  
- **Role-Based Access Control (RBAC):**  
  • Roles: `admin`, `warehouse_manager`, `clerk`, `auditor`.  
  • Enforce server-side checks on every endpoint.  
  • Review and audit role assignments quarterly.

---

## 4. Input Handling & Validation

- **Prevent Injection Attacks:**  
  • Use parameterized queries or ORM (e.g., Sequelize, Hibernate).  
  • Reject or escape special characters in search/filter inputs.  
- **File Upload Security:**  
  • Accept only `.csv` or `.xlsx`, verify MIME type, scan for malware.  
  • Store uploads outside webroot; serve via signed URLs.  
  • Sanitize filenames to prevent path traversal.  
- **CSRF Protection:**  
  • Apply synchronizer‐token pattern for POST/PUT/DELETE.  
- **Output Encoding & XSS Mitigation:**  
  • Escape all dynamic data in HTML, JS, and JSON contexts.  
  • Implement a strict Content Security Policy (CSP) disallowing inline scripts.

---

## 5. Data Protection & Privacy

- **Encryption in Transit:**  
  • Enforce TLS 1.2+ with HSTS and modern ciphers.  
- **Encryption at Rest:**  
  • Use AES-256 for database volumes and backups.  
- **PII Handling:**  
  • Mask or redact supplier contact info in logs and reports.  
  • Only collect fields required for operation (GDPR/CCPA compliance).  
- **Secrets Management:**  
  • Store API keys and credentials in Vault or AWS Secrets Manager.  
  • Rotate secrets quarterly.

---

## 6. API & Service Security

- **HTTPS Only:**  
  • Redirect HTTP to HTTPS; disable weak TLS versions.  
- **Rate Limiting & Throttling:**  
  • Block clients exceeding 100 requests/minute per IP or API key.  
- **CORS Policy:**  
  • Allow only trusted origins (e.g., `https://app.new_inventaris.com`).  
- **API Versioning:**  
  • Prefix endpoints with `/api/v1/`; deprecate old versions gracefully.  
- **Least-Data Exposure:**  
  • Return only necessary fields (e.g., exclude `cost_price` from clerk endpoints).

---

## 7. Web Application Security Hygiene

- **HTTP Security Headers:**  
  • Content-Security-Policy  
  • X-Frame-Options: DENY  
  • X-Content-Type-Options: nosniff  
  • Referrer-Policy: no-referrer  
  • Strict-Transport-Security: max-age=31536000; includeSubDomains
- **Secure Cookies:**  
  • Set `Secure`, `HttpOnly`, `SameSite=Strict` on all session cookies.  
- **Subresource Integrity (SRI):**  
  • Add integrity hashes for all CDN-loaded scripts/styles.

---

## 8. Infrastructure & Configuration

- **Environment Segregation:**  
  • Separate production, staging, development VPCs.  
- **Server Hardening:**  
  • Disable unused services; apply OS and library patches monthly.  
- **Secret Configuration:**  
  • Do not commit `.env` or credentials to Git.  
  • Use managed identity or IAM roles for cloud resources.  
- **File Permissions:**  
  • App user owns code; no write access for world or other users.

---

## 9. Dependency Management

- **Secure Dependencies:**  
  • Vet and pin to specific versions in lockfiles (`package-lock.json`, `Pipfile.lock`).  
- **Vulnerability Scanning:**  
  • Integrate SCA tools (e.g., Dependabot, Snyk) in CI pipeline.  
- **Minimal Footprint:**  
  • Remove unused libraries; avoid monolithic frameworks if not needed.

---

## 10. Security Testing & Monitoring

- **Static & Dynamic Analysis:**  
  • Run ESLint/Flake8 and OWASP ZAP scans in CI.  
- **Logging & Alerting:**  
  • Centralize logs (e.g., ELK, CloudWatch) with PII redaction.  
  • Trigger alerts on unusual activities (multiple failed logins, high error rates).  
- **Penetration Testing:**  
  • Conduct annual third-party pen tests; remediate findings promptly.

---

## 11. Conclusion

Adherence to these guidelines will help ensure that the `new_inventaris` system remains secure, reliable, and compliant. Security is a continuous process—regularly review and update controls as the project evolves.

*Last updated: YYYY-MM-DD*