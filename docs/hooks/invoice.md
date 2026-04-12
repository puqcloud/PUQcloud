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
    $invoice = $vars['invoice'];
    // Perform hook code here...
});
```

---

## ProformaInvoiceCreated

Executed after a proforma invoice has been published (status changed from draft to unpaid).

### Parameters

| Variable         | Type               | Notes |
|------------------|--------------------|-------|
| $vars['invoice'] | App\Models\Invoice |       |

### Response

No response supported.

### Example Code

```php
add_hook('ProformaInvoiceCreated', 1, function($vars) {
    $invoice = $vars['invoice'];
    // Perform hook code here...
});
```

---

## InvoicePaid

Executed after an invoice has been fully paid.

### Parameters

| Variable         | Type               | Notes                                |
|------------------|--------------------|--------------------------------------|
| $vars['invoice'] | App\Models\Invoice | The newly created invoice with status 'paid' |

### Response

No response supported.

### Example Code

```php
add_hook('InvoicePaid', 1, function($vars) {
    $invoice = $vars['invoice'];
    // Perform hook code here...
});
```

---

## InvoiceCancelled

Executed after a proforma invoice has been cancelled.

### Parameters

| Variable         | Type               | Notes |
|------------------|--------------------|-------|
| $vars['invoice'] | App\Models\Invoice |       |

### Response

No response supported.

### Example Code

```php
add_hook('InvoiceCancelled', 1, function($vars) {
    $invoice = $vars['invoice'];
    // Perform hook code here...
});
```

---

## InvoiceRefunded

Executed after a refund has been processed and a credit note created.

### Parameters

| Variable             | Type               | Notes                        |
|----------------------|--------------------|------------------------------|
| $vars['invoice']     | App\Models\Invoice | The original invoice         |
| $vars['credit_note'] | App\Models\Invoice | The newly created credit note |

### Response

No response supported.

### Example Code

```php
add_hook('InvoiceRefunded', 1, function($vars) {
    $invoice = $vars['invoice'];
    $creditNote = $vars['credit_note'];
    // Perform hook code here...
});
```

---
