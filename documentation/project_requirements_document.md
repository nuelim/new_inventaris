# Project Requirements Document for new_inventaris

## 1. Project Overview
new_inventaris is a brand-new inventory management system designed to help small to mid-sized businesses track, organize, and control their stock across one or more locations. Right now, the project only has a placeholder README. The idea is to build a full-featured web application that replaces spreadsheets and manual logs with a centralized, real-time platform for item cataloging, stock monitoring, alerts, reporting, and more.

The main goal is to give warehouse managers, warehouse staff, and administrators clear visibility into every item’s status—where it is, how much is left, and who last updated it—so that teams can prevent stockouts, reduce overstock, and make data-driven decisions. Success will be measured by user adoption (number of active users), reduction in stock discrepancies, and positive feedback on system speed and ease of use.

## 2. In-Scope vs. Out-of-Scope

### In-Scope (Version 1.0)
- **Item Tracking & Catalog**: Create, view, edit, and delete items with fields like name, SKU, category, supplier, location, and custom attributes.
- **Real-Time Stock Monitoring**: Update and display live stock levels whenever stock is added or removed.
- **Low-Stock Alerts**: Let users set thresholds per item or category and trigger email/in-app notifications when stock is low.
- **Role-Based Access Control**: Support user roles (Admin, Manager, Staff) with permissions for viewing, editing, and approving changes.
- **Basic Reporting Dashboard**: Show key metrics (inventory value, turnover rate, stock movement) on a single page.
- **Data Import/Export**: Bulk upload and download inventory data via CSV or Excel.
- **Audit Trail**: Log every change (who did what, when, and why) for transparency.
- **RESTful API**: Expose CRUD (Create, Read, Update, Delete) operations for integration with other systems.
- **Modular Codebase Structure**: Separate frontend, backend, and shared libraries for maintainability.
- **Documentation & Onboarding**: Provide a setup guide, environment configuration steps, and coding conventions in a living README.

### Out-of-Scope (Later Phases)
- Mobile-native app (iOS/Android).
- Multi-warehouse optimization algorithms or AI-driven demand forecasting.
- Advanced workflow automation like purchase order generation.
- GraphQL API or real-time WebSocket features.
- Third-party payment or procurement integrations.

## 3. User Flow
When a new user arrives, they sign up with an email address and choose a secure password. After email verification, they log in and land on a clean dashboard that summarizes current stock levels, low-stock alerts, and recent inventory transactions. A left-hand navigation panel lets them switch between the catalog, stock adjustments, reports, and user settings.

A warehouse manager clicks “Add Item” to define a new product: they fill in the name, SKU, supplier details, and initial stock. The system instantly updates the central database and adjusts the dashboard metrics. Later, when stock is moved in or out, staff members select the item, choose “Stock In” or “Stock Out,” enter the quantity, and hit confirm. If the new quantity falls below the threshold, the manager sees an alert in their inbox and on the dashboard.

## 4. Core Features
- **Authentication & Authorization**: Secure login, password hashing, sessions, and role checks.  
- **Item Management**: Full CRUD for inventory items with custom attribute support.  
- **Stock Transactions**: Record inbound/outbound movements with immediate quantity updates.  
- **Threshold Alerts**: User-configurable low-stock levels plus email and in-app notifications.  
- **Reporting Dashboard**: Charts and tables for inventory valuation, turnover, and activity logs.  
- **Search & Filter**: Find items by SKU, category, supplier, location, or stock status.  
- **Bulk Import/Export**: CSV/Excel templates for mass data operations.  
- **Audit Logs**: Immutable records of each inventory change with timestamp and user ID.  
- **REST API**: Endpoints for external system integration (e.g., GET /items, POST /transactions).  
- **Settings & Roles**: Manage user accounts, assign roles, and configure thresholds.

## 5. Tech Stack & Tools
- **Frontend**: React (with TypeScript), Next.js for server-side rendering, Tailwind CSS for styling.  
- **Backend**: Node.js (TypeScript) with Express.js or NestJS for structured modules.  
- **Database**: PostgreSQL for reliable relational data, managed via Prisma ORM.  
- **Notifications**: Nodemailer for emails; optional in-app alerts stored in database.  
- **API Docs**: Swagger (OpenAPI) for documenting REST endpoints.  
- **Development Tools**:  
  - IDE: VSCode with ESLint, Prettier, and GitLens.  
  - Containerization: Docker for local dev and staging environments.  
  - CI/CD: GitHub Actions for linting, testing, and deployment to AWS Elastic Beanstalk or similar.  
- **Authentication**: JSON Web Tokens (JWT) or session-based auth with Passport.js.  

## 6. Non-Functional Requirements
- **Performance**: API responses ≤ 200ms under typical load; page load ≤ 1s for dashboards.  
- **Scalability**: Support up to 1,000 concurrent users; database indexing for fast queries.  
- **Security**: Data encrypted in transit (HTTPS/TLS) and at rest; OWASP Top 10 compliance.  
- **Availability**: 99.9% uptime; daily backups of production database.  
- **Usability**: Mobile-responsive design; accessible contrast ratios; straightforward onboarding.  
- **Maintainability**: 80% unit test coverage; code linting and formatting enforced automatically.

## 7. Constraints & Assumptions
- **Constraints**:  
  - Must use PostgreSQL as the main data store.  
  - Email provider (e.g., SendGrid) must be available for alerts.  
  - Deployment environment is AWS (or equivalent).  
- **Assumptions**:  
  - Users have modern browsers (Chrome, Firefox, Safari Edge).  
  - Internal network allows Web traffic on standard ports (80/443).  
  - No external AI integration is required at this stage.

## 8. Known Issues & Potential Pitfalls
- **Concurrency**: Simultaneous stock updates can cause race conditions—mitigate with database transactions or row-level locks.  
- **CSV Import Errors**: Malformed data may break imports—provide clear templates and front-end validation.  
- **Email Deliverability**: Alerts might go to spam—set up SPF/DKIM records and include retry logic.  
- **Scaling Reports**: Generating large reports can be slow—consider background jobs or pagination.  
- **Role Misconfiguration**: Incorrect permissions may lock out users—build a super-admin role that cannot be removed.

---

This document serves as the single source of truth for new_inventaris. It lays out exactly what Version 1.0 must do, how users will move through the system, which technologies to use, and what hurdles to watch out for. With this in place, detailed technical guides (file structure, API specs, design system) can be spun up without any questions left unanswered.