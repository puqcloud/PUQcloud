# Output Hooks Documentation

## AdminAreaFooterOutput

Runs on every admin area page load.

### Parameters

| Variable | Type   | Notes |
|-----|--------|-------|
||||
||||



### Response

String

### Example Code

```php
add_hook('AdminAreaFooterOutput', 1, function() {
    return 'Output TEXT';
});
```
---
