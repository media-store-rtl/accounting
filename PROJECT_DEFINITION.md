# Accounting Project Definition

## 1. Product Definition

Accounting is a production cost accounting system for manufacturing businesses.

Its core purpose is to manage and calculate the cost of production through the complete business flow, starting from an order and continuing through production, finished products, and sales.

## 2. Core Business Flow

```
Order
  ↓
Production
  ↓
Finished Product
  ↓
Cost of Goods / Production Cost
  ↓
Sale
  ↓
Financial & Management Reports
```

The system must model the relationship between these stages so that production cost is derived from the underlying operational and financial data rather than being treated as an isolated manual value.

## 3. Core Scope

The Accounting project is centered on:

- Order management
- Production management
- Product and finished-product management
- Production cost / cost-of-goods calculation
- Sales
- Financial and management reporting

Supporting capabilities such as users, permissions, authentication/SSO, subscriptions, database architecture, security, UI/UX, testing, and deployment exist to support this core business workflow.

## 4. Relationship with Web2022

Web2022 and Accounting are separate applications.

Web2022 provides the surrounding ecosystem, including authentication/SSO integration and the subscription/purchase flow. Accounting is the application responsible for the manufacturing cost-accounting workflow.

Subscription and SSO are supporting integrations; they are not the primary business purpose of Accounting.

## 5. Subscription Principles

- Access to Accounting is subscription-aware.
- An active subscription enables the operational functionality assigned to the user's subscription.
- When a subscription expires, historical Accounting data must remain available.
- Expiration must not delete historical records or make previously recorded business data disappear.
- Expired users must be able to renew through the Web2022 purchase flow.
- Plan switching/upgrading is not part of the current scope unless explicitly added later.

## 6. Access Control Principle

Operational access must be enforced server-side. Hiding or disabling UI elements alone is not considered sufficient authorization.

## 7. Data Principle

The production-cost chain must remain traceable from the originating business records through production and finished products to sales and reporting.

Historical records are retained and must remain available according to the user's authorized access.

## 8. Project Boundary

The project should be designed as a modular Laravel application so that the core manufacturing accounting modules can be developed independently while sharing well-defined data, authorization, and integration contracts.

New features must be evaluated against the core objective:

**Order → Production → Product → Production Cost → Sale → Reporting**

Features that do not support this objective or the necessary platform/security/integration capabilities should be treated as future scope unless explicitly approved.
