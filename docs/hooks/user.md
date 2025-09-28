# User Hooks Documentation

## UserBeforeRegister

Triggers before authentication of a user.

### Parameters

| Variable | Type   | Notes |
|----------|--------|-------|
| email    | string |       |
| password | string |       |

### Response

No response supported.

### Example Code

```php
add_hook('UserBeforeRegister', 1, function($credentials) {
    // Perform hook code here...
});
```

---

## UserAfterRegister

Triggers after successful registration of a user.

### Parameters

| Variable | Type            | Notes |
|----------|-----------------|-------|
| $user    | App\Models\User |       |

### Response

No response supported.

### Example Code

```php
add_hook('UserAfterRegister', 1, function($user) {
    // Perform hook code here...
});
```

---

## UserBeforeLogin

Triggers before authentication of a user.

### Parameters

| Variable | Type   | Notes |
|----------|--------|-------|
| email    | string |       |
| password | string |       |

### Response

No response supported.

### Example Code

```php
add_hook('UserBeforeLogin', 1, function($credentials) {
    // Perform hook code here...
});
```

---

## UserAfterLogin

Triggers after successful authentication of a user.

### Parameters

| Variable | Type            | Notes |
|----------|-----------------|-------|
| $user    | App\Models\User |       |

### Response

No response supported.

### Example Code

```php
add_hook('UserAfterLogin', 1, function($user) {
    // Perform hook code here...
});
```

---

## UserFailedAuthorization

Triggers when failed authentication of a user.

### Parameters

| Variable | Type   | Notes |
|----------|--------|-------|
| email    | string |       |
| password | string |       |
| ip       | string |       |
| date     | string |       |
| r_dns    | string |       |

### Response

No response supported.

### Example Code

```php
add_hook('ClientFailedAuthorization', 1, function($credentials) {
    // Perform hook code here...
});
```

---

## UserBeforeLogout

Triggers before logout of a user.

### Parameters

| Variable | Type            | Notes |
|----------|-----------------|-------|
| $user    | App\Models\User |       |

### Response

No response supported.

### Example Code

```php
add_hook('UserBeforeLogout', 1, function($admin) {
    // Perform hook code here...
});
```

---

## UserAfterLogout

Triggers after logout of a user.

### Parameters

| Variable | Type            | Notes |
|----------|-----------------|-------|
| $user    | App\Models\User |       |

### Response

No response supported.

### Example Code

```php
add_hook('UserAfterLogout', 1, function($admin) {
    // Perform hook code here...
});
```

---

## UserResetPassword

Triggers after Reset Password request.

### Parameters

| Variable              | Type            | Notes |
|-----------------------|-----------------|-------|
| $email                | string          |       |
| $user                 | App\Models\User |       |
| $password_reset_token | string          |       |
| $reset_password_url   | string          |       |
| $expire               | int             |       |

### Response

No response supported.

### Example Code

```php
add_hook('UserResetPassword', 1, function($admin) {
    // Perform hook code here...
});
```

---
