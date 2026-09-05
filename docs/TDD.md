# Technical Design Overview: Aggregate Product Type

---

## 1. Purpose

This document provides a **high-level technical design overview** for implementing the Aggregate Product type in Magento 2.

---

## 2. Architectural Goals

* Leverage Magento’s native product lifecycle
* Minimal cart and checkout customization
* Preserve compatibility with MSI and legacy inventory
* Maintain auditability of inventory changes

---

## 3. Product Model Strategy

* Aggregate Product acts as a parent-only sellable entity
* Child products remain standard simple/virtual products
* Child products are not represented as cart items (as grouped does)

---

## 4. Inventory Resolution Strategy

### 4.1 Salability Check

* Salability is computed dynamically based on child product stock
* Required quantity = child configured quantity * parent quantity

### 4.2 Reservation Strategy (MSI)

* Reservations are created for child products only
* Parent product does not maintain independent stock

---

## 5. Cart & Order Handling

* Cart item references parent product SKU
* Child inventory resolution occurs during:
    * Add-to-cart validation
    * Order placement

---

## 6. Order Persistence

* Order item contains parent SKU only
* Child inventory adjustments are recorded separately
* Aggregate relations must remain immutable post-order

---

## 7. Fulfillment Considerations

* Fulfillment systems rely on aggregate definition
* Pick/pack lists derive component quantities from aggregate configuration

---

## 8. Extension & Compatibility

* Compatible with standard promotions and tax rules
* No custom checkout steps required
* Supports MSI and legacy inventory modes

---

## 9. Non-Goals

* Dynamic or customer-configurable aggregates
* Partial kit fulfillment
* Component substitution logic

---

## 10. Risks & Mitigations

* **Inventory race conditions**: mitigated through standard Magento stock validation
* **Reporting confusion**: mitigated by strict parent-only order representation
