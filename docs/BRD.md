# Business Requirements Document: Aggregate Product Type

---

## 1. Purpose

This document defines the **business requirements** for a new Magento 2 product type called **Aggregate Product**. The Aggregate Product enables merchants to sell **fixed kits or composite products** made up of multiple predefined child products, while ensuring accurate inventory control, simplified purchasing, and clear reporting.

This document is intentionally business-focused. Technical design and implementation details are out of scope.

---

## 2. Problem Statement

Magento 2’s existing product types (bundle, grouped, configurable) do not fully support scenarios where:

* Product contents are fixed and non-configurable
* Inventory is strictly derived from component SKUs
* Only a single parent SKU appears in cart and orders
* Reporting is aggregated at the parent product level

Businesses selling kits, multipacks, curated sets, or component assemblies must often compromise inventory accuracy, reporting clarity, or storefront simplicity.

---

## 3. Business Objectives

The Aggregate Product type must:

1. Enable sale of fixed composite products under a single SKU
2. Decrement inventory accurately across all component products
3. Provide a simple, consistent customer purchasing experience
4. Support clear merchandising and product communication
5. Deliver aggregated sales reporting with component-level inventory tracking

---

## 4. Stakeholders

### Primary

* **Merchant / Catalog Manager** – defines products, components, and pricing
* **Customer** – purchases products with clear expectations of contents

### Secondary

* Operations and fulfillment teams
* Finance and reporting stakeholders
* Merchandising and marketing teams
* Customer support teams

---

## 5. Product Definition

An **Aggregate Product** is a sellable parent product composed of one or more predefined child products, each with a fixed quantity.

**Parent product characteristics:**

* Single SKU
* Single price
* Unified product page
* No customer-configurable options

**Child product characteristics:**

* Standard Magento products that are composable
* Not individually added to cart or order
* Drive inventory availability and stock decrements

---

## 6. Representative Use Cases

### 6.1 Fixed Multipack

A parent product represents multiple units of the same child product at a fixed quantity.

* One parent SKU
* One child SKU with a defined quantity
* Inventory decremented by the fixed quantity per purchase

### 6.2 Predefined Kit or Assortment

A parent product represents a predefined set of multiple distinct products.

* One parent SKU
* Multiple child SKUs with fixed quantities
* Contents cannot be modified by the customer
* Availability depends on all components being in stock

---

## 7. Functional Business Requirements

### 7.1 Product Management

* Merchants can create Aggregate Products
* Merchants define:
    * Included child products
    * Fixed quantity per child
* Customers cannot modify contents or quantities

### 7.2 Pricing

* Aggregate Products have a single price
* Pricing is independent of child product prices
* Promotions, discounts, and taxes apply at the parent level

### 7.3 Storefront Presentation

* Single product identity and description
* Display of included child products with quantities
* Optional visual representation of included items
* Customers interact only with the parent product

### 7.4 Cart & Checkout

* Only the parent product is added to cart and order
* Child products are not shown as individual line items
* Standard Magento checkout behavior applies

### 7.5 Inventory & Availability

* Parent availability is derived from child inventory
* Parent product is out of stock if any child lacks sufficient inventory
* On purchase, child inventory is decremented by configured quantities

### 7.6 Order Management & Fulfillment

* Orders contain only the parent SKU
* Fulfillment relies on the aggregate definition to identify components
* Inventory changes must be traceable to aggregate sales
* Configured child quantities are recorded at the time of purchase in case quantities are changed by an admin later

### 7.7 Reporting

* Sales reporting is available at the parent product level
* Inventory tracking remains at the child product level
* Kit or set performance can be analyzed independently

---

## 8. Business Rules & Constraints

* Aggregate Products are fixed in relations
* No substitutions or partial fulfillment
* Child products must exist as valid Magento products
* Parent stock cannot exceed component stock availability

---

## 9. Success Criteria

The Aggregate Product solution is successful when:

* Fixed kits can be sold under a single SKU
* Inventory remains accurate across all components
* Customers experience a simple, predictable purchase flow
* Reporting supports aggregate sales and component inventory
* Operational effort to manage kits and sets is reduced
