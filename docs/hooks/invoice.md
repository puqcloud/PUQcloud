# Invoice Hooks Documentation

## InvoiceCreated

Executed after the invoice has been created, which means that the payment has been made in full.

### Parameters

| Variable         | Type               | Notes |
|------------------|--------------------|-------|
| $vars['invoice'] | App\Models\Invoice |       |

### Response

No response supported.

### Example Code

```php
add_hook('InvoiceCreated', 1, function($vars) {
    // Perform hook code here...
});
```

---
