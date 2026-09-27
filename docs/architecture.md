# Accounting Architecture

This application is independent from the storefront. It has its own users, authentication, database, companies and accounting data.

The storefront owns plans, payments and subscriptions. This application stores only the minimum subscription entitlement needed to decide access.

Subscription expiration never deletes accounting data; it only changes access state.

## Domain flow

Purchasing -> goods receipt -> warehouse -> material issue/transfer -> workshop operations -> labor -> overhead -> production -> finished goods -> cost calculation -> reports.

## Costing

Material valuation is configurable per material. Supported strategy values will include weighted average, FIFO, latest purchase and standard cost.

Overhead allocation will be configurable by production quantity, machine hours, labor hours, material percentage, or combinations.

## Notifications

Notifications are internal to the application database and are not tied to an external channel.
