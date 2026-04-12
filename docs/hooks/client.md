# Client Hooks Documentation

## ClientCreated

Executed after a new client has been created.

### Parameters

| Variable          | Type                | Notes |
|-------------------|---------------------|-------|
| $vars['client']   | App\Models\Client   |       |

### Response

No response supported.

### Example Code

```php
add_hook('ClientCreated', 1, function($vars) {
    $client = $vars['client'];
    // Perform hook code here...
});
```

---

## ClientEdit

Executed after a client has been edited by an admin.

### Parameters

| Variable          | Type                | Notes |
|-------------------|---------------------|-------|
| $vars['client']   | App\Models\Client   |       |

### Response

No response supported.

### Example Code

```php
add_hook('ClientEdit', 1, function($vars) {
    $client = $vars['client'];
    // Perform hook code here...
});
```

---

## ClientChangePassword

Executed after a user has changed their password from the client area.

### Parameters

| Variable       | Type            | Notes |
|----------------|-----------------|-------|
| $vars['user']  | App\Models\User |       |

### Response

No response supported.

### Example Code

```php
add_hook('ClientChangePassword', 1, function($vars) {
    $user = $vars['user'];
    // Perform hook code here...
});
```

---
