# Product Hooks Documentation

## ProductCreated

Executed after a new product has been created by an admin.

### Parameters

| Variable           | Type                | Notes |
|--------------------|---------------------|-------|
| $vars['product']   | App\Models\Product  |       |

### Response

No response supported.

### Example Code

```php
add_hook('ProductCreated', 1, function($vars) {
    $product = $vars['product'];
    // Perform hook code here...
});
```

---

## ProductEdit

Executed after a product has been edited by an admin.

### Parameters

| Variable           | Type                | Notes |
|--------------------|---------------------|-------|
| $vars['product']   | App\Models\Product  |       |

### Response

No response supported.

### Example Code

```php
add_hook('ProductEdit', 1, function($vars) {
    $product = $vars['product'];
    // Perform hook code here...
});
```

---

## ProductDeleted

Executed after a product has been deleted by an admin.

### Parameters

| Variable  | Type   | Notes                       |
|-----------|--------|-----------------------------|
| uuid      | string | UUID of the deleted product  |
| key       | string | Key of the deleted product   |

### Response

No response supported.

### Example Code

```php
add_hook('ProductDeleted', 1, function($productData) {
    $uuid = $productData['uuid'];
    $key = $productData['key'];
    // Perform hook code here...
});
```

---
