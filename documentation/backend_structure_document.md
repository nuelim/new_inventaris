# Backend Structure Document

## 1. Backend Architecture

We have chosen a **modular, layered architecture** that keeps the system organized and easy to grow. The main design patterns and frameworks are:

- **Framework**: NestJS (Node.js with TypeScript) for a clear separation of concerns.
- **Layers**:
  - Controllers: Handle incoming requests and send responses.
  - Services: Contain business logic and orchestrate data operations.
  - Repositories (Data Access): Talk directly to the database.
  - Shared Modules: Common utilities like logging, error handling, and validation.
- **Modularity**: Each feature (items, stock transactions, users, reports) lives in its own module so new features can be added without breaking existing code.

This structure supports:

- **Scalability**: Modules can be split into separate microservices later if needed.
- **Maintainability**: Clear layer boundaries make it easy to locate and update functionality.
- **Performance**: Services can be individually scaled, and critical paths can be optimized (for example, read-heavy operations can use caching).

## 2. Database Management

We use a combination of relational and in-memory databases to balance reliability with speed.

- **PostgreSQL (SQL)**:
  - Primary data store for structured information: items, users, roles, transactions.
  - Ensures data integrity with foreign keys and ACID transactions.
  - Backups managed daily with point-in-time recovery.

- **Redis (NoSQL, in-memory)**:
  - Caching of frequent read operations (e.g., item lookups, stock levels).
  - Message broker for real-time notifications and alerts.

**Data Practices**:

- **Normalization**: Core tables are normalized to remove duplication.
- **Indexing**: Critical columns (SKU, item name, user ID) are indexed for fast searches.
- **Archival**: Transactions older than a configurable age get archived to a separate store or cold storage.

## 3. Database Schema

Below is a human-readable overview, followed by the PostgreSQL table definitions.

### Human-Readable Schema

- **Users**: Stores user profiles and credentials.
- **Roles**: Defines roles like Admin, Manager, Clerk.
- **UserRoles**: Links users to their roles.
- **Items**: Catalog of inventory products (SKU, name, description, category).
- **Categories**: Groups items under logical categories.
- **Suppliers**: Vendor information for each item.
- **Locations**: Warehouses or stores where stock is held.
- **StockTransactions**: Records inbound/outbound stock changes.
- **Alerts**: Low-stock threshold settings and status.
- **AuditLogs**: Tracks all changes with timestamps and user IDs.

### PostgreSQL Schema (simplified)

```sql
-- Users
CREATE TABLE users (
  id SERIAL PRIMARY KEY,
  email VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(255),
  created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Roles
CREATE TABLE roles (
  id SERIAL PRIMARY KEY,
  name VARCHAR(50) UNIQUE NOT NULL
);

-- UserRoles
CREATE TABLE user_roles (
  user_id INT REFERENCES users(id),
  role_id INT REFERENCES roles(id),
  PRIMARY KEY (user_id, role_id)
);

-- Categories
CREATE TABLE categories (
  id SERIAL PRIMARY KEY,
  name VARCHAR(100) UNIQUE NOT NULL
);

-- Suppliers
CREATE TABLE suppliers (
  id SERIAL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  contact_info TEXT
);

-- Locations
CREATE TABLE locations (
  id SERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  address TEXT
);

-- Items
CREATE TABLE items (
  id SERIAL PRIMARY KEY,
  sku VARCHAR(100) UNIQUE NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  category_id INT REFERENCES categories(id),
  supplier_id INT REFERENCES suppliers(id),
  created_at TIMESTAMPTZ DEFAULT NOW()
);

-- StockTransactions
CREATE TABLE stock_transactions (
  id SERIAL PRIMARY KEY,
  item_id INT REFERENCES items(id),
  location_id INT REFERENCES locations(id),
  quantity INT NOT NULL,
  transaction_type VARCHAR(10) CHECK (transaction_type IN ('IN','OUT')),
  created_at TIMESTAMPTZ DEFAULT NOW(),
  performed_by INT REFERENCES users(id)
);

-- Alerts
CREATE TABLE alerts (
  id SERIAL PRIMARY KEY,
  item_id INT REFERENCES items(id),
  threshold INT NOT NULL,
  last_sent TIMESTAMPTZ
);

-- AuditLogs
CREATE TABLE audit_logs (
  id SERIAL PRIMARY KEY,
  table_name TEXT NOT NULL,
  record_id INT NOT NULL,
  changed_by INT REFERENCES users(id),
  change_time TIMESTAMPTZ DEFAULT NOW(),
  change_type VARCHAR(10),
  details JSONB
);
```

## 4. API Design and Endpoints

We use a **RESTful approach**. All endpoints return JSON and use standard HTTP verbs.

Key endpoints:

- **Authentication**
  - POST `/auth/login`: User login, returns JWT.
  - POST `/auth/refresh`: Refresh access token.

- **User Management**
  - GET `/users`: List all users.
  - POST `/users`: Create a new user.
  - PUT `/users/{id}`: Update user profile or roles.
  - DELETE `/users/{id}`: Deactivate a user.

- **Roles & Permissions**
  - GET `/roles`: List available roles.
  - POST `/roles`: Add a new role.

- **Inventory Items**
  - GET `/items`: Search and list items (filters: SKU, category, supplier).
  - GET `/items/{id}`: Retrieve a single item.
  - POST `/items`: Create an item.
  - PUT `/items/{id}`: Update item details.
  - DELETE `/items/{id}`: Remove an item from catalog.

- **Stock Transactions**
  - POST `/items/{id}/stock`: Record an inbound or outbound transaction.
  - GET `/items/{id}/stock`: Get current stock levels by location.

- **Alerts**
  - GET `/alerts`: List configured alerts.
  - POST `/alerts`: Create or update a low-stock alert.

- **Reports & Exports**
  - GET `/reports/inventory`: KPI dashboard data.
  - GET `/export/csv`: Download inventory data as CSV.

## 5. Hosting Solutions

We host everything on **AWS** for reliability and pay-as-you-go pricing:

- **Amazon ECS (Fargate)** or **EKS** for containerized services.
- **Amazon RDS (PostgreSQL)** for the relational database.
- **Amazon ElastiCache (Redis)** for in-memory caching.
- **Amazon S3** for file storage (imports, exports, backups).

Benefits:

- **High Availability**: Multi-AZ deployments for RDS and ECS tasks.
- **Auto Scaling**: Services scale based on CPU or request metrics.
- **Cost Efficiency**: Pay only for usage, easily turn off unused resources.

## 6. Infrastructure Components

Key pieces working together:

- **Load Balancer (AWS ALB)**: Distributes incoming traffic across containers.
- **API Gateway (optional)**: Central entry point for routing and rate limiting.
- **Redis Cache**: Speeds up frequent reads and publishes notifications.
- **Content Delivery Network (CloudFront)**: Serves static assets and API responses closer to users.
- **Docker & Kubernetes/ECS**: Containers ensure consistent environments.
- **CI/CD Pipeline**: GitHub Actions or AWS CodePipeline for automated builds, tests, and deployments.

## 7. Security Measures

We follow best practices to protect data and comply with regulations:

- **Authentication**: JWT tokens with short lifespans; refresh tokens securely stored server-side.
- **Authorization**: Role-Based Access Control (RBAC) guards every endpoint.
- **Encryption**:
  - TLS for data in transit.
  - AES-256 encryption at rest for RDS snapshots and S3 objects.
- **Network Security**: Private subnets for databases, security groups with least-privilege rules.
- **Input Validation & Sanitization**: Prevent SQL injection and XSS via validation libraries.
- **Logging & Auditing**: All access and changes recorded in CloudWatch logs and our AuditLogs table.

## 8. Monitoring and Maintenance

To keep the system healthy and performant, we use:

- **CloudWatch & Prometheus**: Track CPU, memory, response times, error rates.
- **Grafana Dashboards**: Visualize trends and set alerts on key metrics.
- **ELK Stack (optional)**: Centralized logging for detailed troubleshooting.
- **Health Checks**: Kubernetes/ECS probes restart unhealthy containers.
- **Automated Backups & Migrations**:
  - RDS snapshots daily with retention policy.
  - Database migrations managed by TypeORM or similar migration tool.
- **Routine Maintenance**: Dependency updates, security patching, code reviews.

## 9. Conclusion and Overall Backend Summary

This backend is designed to support a robust, enterprise-grade inventory management system. By combining a modular NestJS architecture with PostgreSQL and Redis, we ensure data integrity and speed. AWS infrastructure provides reliability and scalability, while built-in security measures and monitoring tools keep the system safe and healthy. Every component—from the API design to the audit logs—aligns with our goal of delivering a transparent, extensible, and maintainable solution that meets the needs of diverse users.