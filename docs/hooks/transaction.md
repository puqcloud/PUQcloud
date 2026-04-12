# Transaction Hooks Documentation

## TransactionCreated

Executed after a new transaction has been created and the client balance has been updated.

### Parameters

| Variable              | Type                    | Notes |
|-----------------------|-------------------------|-------|
| $vars['transaction']  | App\Models\Transaction  |       |

### Response

No response supported.

### Example Code

```php
add_hook('TransactionCreated', 1, function($vars) {
    $transaction = $vars['transaction'];
    // Perform hook code here...
});
```

---
