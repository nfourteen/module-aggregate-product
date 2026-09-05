# Feature Spec: "What's in the Box" Grid

## 1. Overview

### Problem

The aggregate product PDP currently shows child contents as a plain text list:

```
Includes:
  5 x Child Product A
  3 x Child Product B
```

This is functional but lacks visual appeal. It also cannot represent non-inventory items (packaging, documentation, accessories) that ship with the product but don't exist as Magento catalog products.

### Goal

Add a responsive card grid ("What's in the Box") that shows each included item as a visual card with image, name, and quantity. Support two item sources:

1. **Inventory items** — existing child products from `catalog_product_aggregate_link`
2. **Non-inventory items** — per-product entries managed on the admin product form (e.g., packaging, documentation, accessories)

Both the existing text summary and the new grid are independently togglable via system configuration with `ifconfig` layout directives.

### Reference Design

Inspired by the Navimow X4 product page "What's in the Box" section:

- Card grid: 5 columns desktop, 3 tablet, 2 mobile
- Each card: square image (1:1 aspect ratio, light gray background), bold item name, "x QTY" below
- Clean, minimal styling

---

## 2. Data Sources

### Inventory Items (Existing)

Source: `catalog_product_aggregate_link` table joining to `catalog_product_entity`.

`LinkedProductProvider::LOAD_FULL` already loads the `thumbnail` attribute alongside `name`, `price`, `status`, `tax_class_id`, and `weight`. The grid will use `LOAD_FULL` (or a new load mode) to retrieve the thumbnail URL for each child product.

### Non-Inventory Items (New)

Stored in a new table (see section 3). Each entry has an uploaded image, text name, and quantity. These items have no SKU, no stock, and no catalog product entity — they exist only in the context of a specific aggregate product.

---

## 3. Non-Inventory Items — Admin UI & Storage

### Database Table

New table: `catalog_product_aggregate_non_inventory_item`

| Column       | Type                          | Notes                              |
|--------------|-------------------------------|------------------------------------|
| `item_id`    | `int unsigned`, PK, identity  | Auto-increment primary key         |
| `parent_id`  | `int unsigned`, FK            | References `catalog_product_entity.entity_id`, CASCADE delete |
| `name`       | `varchar(255)`, not null      | Display name                       |
| `image`      | `varchar(255)`, nullable      | Relative path in media storage     |
| `qty`        | `decimal(12,4)`, default 1    | Quantity to display                |

Index on `parent_id` for efficient lookups.

### Admin Product Form

A dynamic rows grid on the aggregate product edit form, rendered **within the existing "Aggregate Products" fieldset** (below the inventory children listing) or as a new sibling fieldset titled "Non-Inventory Items."

Each row contains:

| Field   | UI Component        | Notes                                    |
|---------|---------------------|------------------------------------------|
| Image   | File uploader       | Single image, stored via Magento media storage (similar to category image uploads) |
| Name    | Text input          | Required                                 |
| Qty     | Number input        | Required, minimum 1                      |
| Actions | Delete button       | Removes the row                          |

#### Implementation Pattern

Follow the existing `Composite` modifier pattern:

- Add a new modifier class (e.g., `NonInventoryItemsPanel`) to the `Composite` modifier's `modifiers` argument in `etc/adminhtml/di.xml`
- The modifier only renders when `canShowAggregateFieldset()` returns true (product type is `aggregate`), inheriting the same gating logic
- Use `Magento_Ui/js/dynamic-rows/dynamic-rows` component (not `dynamic-rows-grid` since there's no modal/search — rows are created inline)
- Image upload uses `Magento_Ui/js/form/element/file-uploader` with a backend controller to handle upload/storage

#### Save & Delete

- Save: Controller/plugin processes the dynamic rows data on product save, persisting to the `catalog_product_aggregate_non_inventory_item` table
- Delete: Rows removed in the UI are deleted from the table on save. CASCADE FK ensures cleanup when the parent product is deleted.

---

## 4. System Configuration

New section in `system.xml` under **Stores > Configuration**:

**Path:** `Nfourteen_AggregateProduct > Aggregate Product > Display`

| Config Path                          | Label                        | Type    | Default |
|--------------------------------------|------------------------------|---------|---------|
| `aggregate/display/show_text_summary`| Show "Includes" Text Summary | boolean | Yes (1) |
| `aggregate/display/show_whats_in_box`| Show "What's in the Box"     | boolean | Yes (1) |

Both flags are used as `ifconfig` attributes in layout XML to control block visibility:

```xml
<!-- Existing text summary — now gated -->
<block name="product.info.aggregate.configuration"
       ifconfig="aggregate/display/show_text_summary"
       .../>

<!-- New grid -->
<block name="product.info.aggregate.whats_in_box"
       ifconfig="aggregate/display/show_whats_in_box"
       .../>
```

---

## 5. Frontend Display

### Template

New template: `Nfourteen_AggregateProduct::breeze/product/view/whats_in_the_box.phtml`

```
┌─────────────────────────────────────────────────┐
│  What's in the Box                              │
│                                                 │
│  ┌───────┐ ┌───────┐ ┌───────┐ ┌───────┐ ┌───┐ │
│  │ [img] │ │ [img] │ │ [img] │ │ [img] │ │...│ │
│  │       │ │       │ │       │ │       │ │   │ │
│  │ Name  │ │ Name  │ │ Name  │ │ Name  │ │   │ │
│  │ x 5   │ │ x 3   │ │ x 1   │ │ x 2   │ │   │ │
│  └───────┘ └───────┘ └───────┘ └───────┘ └───┘ │
└─────────────────────────────────────────────────┘
```

### Card Structure

Each card contains:

1. **Image container** — 1:1 aspect ratio, light gray background (`#f5f5f5`). For inventory items, renders the product catalog thumbnail. For non-inventory items, renders the uploaded image. Falls back to a placeholder if no image is available.
2. **Item name** — bold, truncated with ellipsis if it overflows
3. **Quantity** — formatted as "x QTY" (e.g., "x 5")

### Responsive Breakpoints

| Viewport        | Columns |
|-----------------|---------|
| Desktop (>1024px) | 5     |
| Tablet (769–1024px) | 3   |
| Mobile (<768px)  | 2      |

Implemented via CSS grid with media queries. Gap between cards: ~16px.

### Item Ordering

1. Inventory items first (ordered by their position in `catalog_product_aggregate_link`)
2. Non-inventory items second (ordered by `item_id` ascending)

### Layout XML Placement

The block is added to the PDP layout in `breeze_catalog_product_view_type_aggregate.xml`, positioned after the existing `product.info.details` block.

---

## 6. Technical Notes

### View Model

Create a new view model (e.g., `WhatsInTheBox`) or extend the existing `LinkedProducts` view model to provide a unified list of items (both inventory and non-inventory) to the template:

```php
public function getItems(ProductInterface $product): array
{
    // Returns array of DataObjects, each with:
    // - 'name' (string)
    // - 'qty' (float)
    // - 'image_url' (string|null) — thumbnail URL for inventory, media URL for non-inventory
    // - 'type' (string) — 'inventory' or 'non_inventory'
}
```

For inventory items, uses `LinkedProductProvider` with a load mode that includes `thumbnail`. For non-inventory items, queries the `catalog_product_aggregate_non_inventory_item` table.

### Image Storage (Non-Inventory Items)

Follow the pattern used by Magento's category image and CMS image uploads:

- Images stored under `pub/media/nfourteen/aggregate/non_inventory/`
- Upload controller handles file validation (allowed types, max size) and moves to media storage
- Database stores relative path (e.g., `nfourteen/aggregate/non_inventory/envelope.jpg`)

### Existing Code to Leverage

| What                        | Where                                                                 | Why                                                    |
|-----------------------------|-----------------------------------------------------------------------|--------------------------------------------------------|
| `Composite` modifier        | `Ui/DataProvider/Product/Form/Modifier/Composite.php`                 | Product-type gating pattern for admin form modifiers   |
| `AggregatePanel` modifier   | `Ui/DataProvider/Product/Form/Modifier/AggregatePanel.php`            | Pattern for dynamic rows grid on product form          |
| `AggregateProductsListing`  | `Ui/DataProvider/Product/Form/Modifier/AggregateProductsListing.php`  | Pattern for dynamic rows columns & configuration       |
| `LinkedProductProvider`      | `Model/LinkedProductProvider.php`                                     | `LOAD_FULL` already loads `thumbnail`; extend or reuse |
| `LinkedProducts` view model  | `ViewModel/LinkedProducts.php`                                        | Existing PDP data provider for text summary            |
| `LinkedProductFormatter`     | `Service/LinkedProductFormatter.php`                                  | "qty x name" formatting                               |
| Breeze layout XML            | `view/frontend/layout/breeze_catalog_product_view_type_aggregate.xml` | Existing PDP layout to extend                          |

### No Sort Order

There is no sort order field for non-inventory items in this iteration. Items are ordered by `item_id` (insertion order). A `sort_order` column can be added later if needed.
