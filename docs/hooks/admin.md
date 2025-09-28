
# Admin Hooks Documentation

## AdminBeforeLogin

Triggers before authentication of an admin user.

### Parameters

| Variable  | Type   | Notes |
|-----------|--------|-------|
| email     | string |       |
| password  | string |       |



### Response

No response supported.

### Example Code

```php
add_hook('AdminBeforeLogin', 1, function($credentials) {
    // Perform hook code here...
});
```
---

## AdminAfterLogout

Triggers after successful authentication of an admin user.

### Parameters

| Variable | Type             | Notes |
|----------|------------------|-------|
| $admin   | App\Models\Admin |       |


### Response

No response supported.

### Example Code

```php
add_hook('AdminAfterLogout', 1, function($admin) {
    // Perform hook code here...
});
```
---

## AdminFailedAuthorization

Triggers when failed authentication of an admin user.

### Parameters

| Variable  | Type   | Notes |
|-----------|--------|-------|
| email     | string |       |
| password  | string |       |

### Response

No response supported.

### Example Code

```php
add_hook('AdminFailedAuthorization', 1, function($credentials) {
    // Perform hook code here...
});
```
---

## AdminBeforeLogout

Triggers before logout of an admin user.

### Parameters

| Variable | Type             | Notes |
|----------|------------------|-------|
| $admin   | App\Models\Admin |       |


### Response

No response supported.

### Example Code

```php
add_hook('AdminBeforeLogout', 1, function($admin) {
    // Perform hook code here...
});
```
---

## AdminAfterLogout

Triggers after logout of an admin user.

### Parameters

| Variable | Type             | Notes |
|----------|------------------|-------|
| $admin   | App\Models\Admin |       |


### Response

No response supported.

### Example Code

```php
add_hook('AdminAfterLogout', 1, function($admin) {
    // Perform hook code here...
});
```
---

## AdminChangePermissions

Triggers after changing permissions.

### Parameters

| Variable                 | Type             | Notes |
|--------------------------|------------------|-------|
| $vars['admin']           | App\Models\Admin |       |
| $vars['old_permissions'] | array()          |       |
| $vars['new_permissions'] | array()          |       |


### Response

No response supported.

### Example Code

```php
add_hook('AdminChangePermissions', 1, function($vars) {
    // Perform hook code here...
});
```
---

