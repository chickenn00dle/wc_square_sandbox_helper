# Square for WooCommerce Sandbox Helper

## Getting Started

This plugin requires [Square for WooCommerce](http://woocommerce.com/products/square/) to be installed and a Sandbox account to be connected/location ID set.

## Usage

### Batch Upsert

Creates a batch of Square catalog items.

```
wp square batch_upsert "Base Name" 100
```

The following will create 100 products with names `Base Name N` and Sku `basenameN` where `N` is 1 to 100. You can provide an optional `--max_variations=n` flag which will force each product created to have a random number of variations from 1 to `n`. This defaults to 1.

Note: An array of `object_ids` is stored in the sites database for use when changing inventory or deleting objects.

### Batch Delete

This deletes any provided `object_ids` in both Square and WooCommerce if present.

```
wp square batch_delete [ID 1] [ID 2]
```

If you provide no IDs, then this defaults to deleting all stored `object_ids`.

### Batch Change

This allows you to change inventory for provided `object_ids` in Square.

```
wp square batch_change 10 [ID1] [ID2]
```

This adjusts inventory for ID1 and ID2 by +10. If no IDs are provided, Inventory for all stored `object_ids` are adjusted. Currently can only add inventory.

### List Catalog

Lists all object IDs in current Location ID. If no arguments are provided, this defaults to listing only objects of type ITEM and ITEM_VARIATION. You may provide a comma seperated list of types instead.

```
wp square list ITEM,ITEM_VARIATION,CATEGORY
```

You can pass an optional `--save` flag to store this list, overriding existing object IDs. Alternatively, you can pass an optional `--cached` flag to display the object IDs that are currently stored in your database.

### Set Sync Interval

Sets the sync interval.

```
wp set_interval 5
```

This sets the sync interval to 5 minutes. You can provide a `--reset` flag to delete this option and set the interval back to default.
