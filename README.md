# Nfourteen_AggregateProduct

A Magento 2 module that enables selling groups of products at fixed quantities as a single product.

## Overview

The Aggregate Product module allows merchants to create composite products containing multiple child products with specific quantities. When an aggregate product is purchased, each child product's inventory is decremented by its configured quantity.

**Primary Use Case:** Selling packs of items (e.g., greeting card packs) where individual item inventory is managed to prevent overselling.

## Features

- Custom product type: `aggregate`
- Admin UI for managing child products and quantities
- Frontend display of aggregate contents
- Order history with aggregate configuration
- GraphQL support (query + add-to-cart mutation)
- MSI integration for salability checks
- Child products added as separate cart/order line items

## How Inventory Works

1. When an aggregate is added to cart, child products are added as separate line items to the quote with a parent ID
2. Each child quantity = aggregate cart quantity * configured child quantity
3. Stock decrements happen per child product when MSI is enabled
4. The same child product can be part of multiple aggregates (inventory is shared)

**Example:**
- Aggregate "Holiday Pack" contains: Card A (qty: 5), Card B (qty: 3)
- Customer orders 2x "Holiday Pack"
- Cart contains: 10x Card A, 6x Card B
- Inventory decremented accordingly

## Database Schema

**Table:** `catalog_product_aggregate_link`

| Column | Type | Description |
|--------|------|-------------|
| `link_id` | INT | Primary key |
| `product_id` | INT | Child product ID |
| `parent_id` | INT | Aggregate product ID |
| `qty` | DECIMAL(12,4) | Quantity of child in aggregate |

## Installation

TODO

## Usage

### Creating an Aggregate Product (Admin)

1. Navigate to **Catalog > Products** and choose the **Add Product** button
2. Select product type: **Aggregate Product**
3. Fill in standard product attributes (name, SKU, price, etc.)
4. In the **Aggregate Products** section, click **Add Products**
5. Select child products and set quantities for each
6. Save the product

