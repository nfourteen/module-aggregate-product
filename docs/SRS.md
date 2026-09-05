# Software Requirements Specification: Aggregate Product Type

---

## 1. Purpose

This document translates the Aggregate Product BRD into **functional and system-level requirements** for Magento 2.

---

## 2. Scope

The SRS covers:

* Product type behavior
* Inventory resolution rules
* Cart, checkout, and order behavior
* Administrative capabilities
* MSI and legacy inventory considerations

---

## 3. Product Type Definition

### 3.1 Aggregate Product

An Aggregate Product is a sellable parent product composed of predefined child products with fixed quantities.

**System characteristics:**

* Identified by a unique SKU
* Has a single price and description
* Is the only product visible in cart and order

---

## 4. Administrative Requirements

### 4.1 Product Configuration

* Admin users must be able to:
    * Create an Aggregate Product
    * Assign one or more child products
    * Define a fixed quantity per child product

### 4.2 Validation Rules

* Child quantities must be positive integers
* Duplicate child products are not allowed
* Aggregate Product cannot be saved without at least one child product

---

## 5. Storefront Behavior

### 5.1 Product Detail Page

* Displays parent product information
* Displays list of included child products with quantities
* Child products are informational only

### 5.2 Purchasing Rules

* Customers cannot modify contents or quantities
* Aggregate Product behaves as a single purchasable item

---

## 6. Cart & Checkout Requirements

* Only the parent product appears in cart and checkout
* Quantity selection applies only to the parent product
* Cart price calculations use the parent product price exclusively

---

## 7. Inventory & Availability

### 7.1 Availability Calculation

* Aggregate Product is salable only if:
    * All child products have sufficient inventory to satisfy their defined quantities

### 7.2 Inventory Decrement

* On order placement:
    * Each child product inventory is decremented by its configured quantity * parent quantity

---

## 8. Inventory Model Compatibility

### 8.1 MSI

* Salability checks must account for stock, source assignment, and reservations
* Inventory reservations apply to child products only

### 8.2 Legacy Inventory

* Stock validation and decrement apply to `cataloginventory_stock_item` of child products

---

## 9. Order Management

* Order contains only the parent SKU
* Child product inventory changes must be auditable
* Fulfillment processes rely on aggregate definition

---

## 10. Error Handling

* Aggregate Product must be marked out of stock if any child product becomes unavailable
* Checkout must fail gracefully if inventory changes between add-to-cart and order placement

---

## 11. Non-Functional Constraints

* Must follow standard Magento product lifecycle
* Must not introduce custom checkout steps
* Must remain compatible with standard promotions and taxation
