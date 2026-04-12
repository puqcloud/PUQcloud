# Output Hooks Documentation

Output hooks allow you to inject custom HTML, CSS, or JavaScript into the admin and client area pages. Unlike other hooks, output hooks support a return value — the returned string is appended to the page output.

## AdminAreaHeadOutput

Runs on every admin area page load. Allows injecting content into the `<head>` section.

### Parameters

No parameters.

### Response

String — HTML/CSS/JS to be injected inside `<head>`.

### Example Code

```php
add_hook('AdminAreaHeadOutput', 1, function() {
    return '<style>.custom-class { color: red; }</style>';
});
```
---

## AdminAreaHeaderOutput

Runs on every admin area page load. Allows injecting content after the header.

### Parameters

No parameters.

### Response

String — HTML to be injected after the header.

### Example Code

```php
add_hook('AdminAreaHeaderOutput', 1, function() {
    return '<div class="alert alert-info">System maintenance scheduled.</div>';
});
```
---

## AdminAreaFooterOutput

Runs on every admin area page load. Allows injecting content into the footer area.

### Parameters

No parameters.

### Response

String — HTML/JS to be injected in the footer.

### Example Code

```php
add_hook('AdminAreaFooterOutput', 1, function() {
    return '<script>console.log("Admin footer hook loaded");</script>';
});
```
---

## ClientAreaHeadOutput

Runs on every client area page load. Allows injecting content into the `<head>` section.

### Parameters

No parameters.

### Response

String — HTML/CSS/JS to be injected inside `<head>`.

### Example Code

```php
add_hook('ClientAreaHeadOutput', 1, function() {
    return '<style>.custom-client-class { color: blue; }</style>';
});
```
---

## ClientAreaHeaderOutput

Runs on every client area page load. Allows injecting content after the header.

### Parameters

No parameters.

### Response

String — HTML to be injected after the header.

### Example Code

```php
add_hook('ClientAreaHeaderOutput', 1, function() {
    return '<div class="alert alert-warning">Important announcement for all clients.</div>';
});
```
---

## ClientAreaFooterOutput

Runs on every client area page load. Allows injecting content into the footer area.

### Parameters

No parameters.

### Response

String — HTML/JS to be injected in the footer.

### Example Code

```php
add_hook('ClientAreaFooterOutput', 1, function() {
    return '<script>console.log("Client footer hook loaded");</script>';
});
```
---
