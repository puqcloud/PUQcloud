## PendingService

It is executed immediately after the order is placed.
The service is still in the pending state.

### Parameters

| Variable | Type               | Notes |
|----------|--------------------|-------|
| $service | App\Models\Service |       |


### Response

No response supported.

### Example Code

```php
add_hook('PendingService', 1, function($admin) {
    // Perform hook code here...
});
```
---

## CreateService

Executes before automation actions when creating a service.

### Parameters

| Variable | Type               | Notes |
|----------|--------------------|-------|
| $service | App\Models\Service |       |


### Response

No response supported.

### Example Code

```php
add_hook('CreateService', 1, function($admin) {
    // Perform hook code here...
});
```
---

## CreateServiceError

Executes if automation fails during service creation.

### Parameters

| Variable | Type               | Notes |
|----------|--------------------|-------|
| $service | App\Models\Service |       |


### Response

No response supported.

### Example Code

```php
add_hook('CreateServiceError', 1, function($admin) {
    // Perform hook code here...
});
```
---

## CreateServiceSuccess

Executes upon successful service creation.

### Parameters

| Variable | Type               | Notes |
|----------|--------------------|-------|
| $service | App\Models\Service |       |


### Response

No response supported.

### Example Code

```php
add_hook('CreateServiceSuccess', 1, function($admin) {
    // Perform hook code here...
});
```
---




## SuspendService

Executes before automation actions when suspending a service.

### Parameters

| Variable | Type               | Notes |
|----------|--------------------|-------|
| $service | App\Models\Service |       |


### Response

No response supported.

### Example Code

```php
add_hook('SuspendService', 1, function($admin) {
    // Perform hook code here...
});
```
---

## SuspendServiceError

Executes if automation fails during service suspending.

### Parameters

| Variable | Type               | Notes |
|----------|--------------------|-------|
| $service | App\Models\Service |       |


### Response

No response supported.

### Example Code

```php
add_hook('SuspendServiceError', 1, function($admin) {
    // Perform hook code here...
});
```
---

## SuspendServiceSuccess

Executes upon successful service suspending.

### Parameters

| Variable | Type               | Notes |
|----------|--------------------|-------|
| $service | App\Models\Service |       |


### Response

No response supported.

### Example Code

```php
add_hook('SuspendServiceSuccess', 1, function($admin) {
    // Perform hook code here...
});
```
---




## UnsuspendService

Executes before automation actions when unsuspending a service.

### Parameters

| Variable | Type               | Notes |
|----------|--------------------|-------|
| $service | App\Models\Service |       |


### Response

No response supported.

### Example Code

```php
add_hook('UnsuspendService', 1, function($admin) {
    // Perform hook code here...
});
```
---

## UnsuspendServiceError

Executes if automation fails during service unsuspending.

### Parameters

| Variable | Type               | Notes |
|----------|--------------------|-------|
| $service | App\Models\Service |       |


### Response

No response supported.

### Example Code

```php
add_hook('UnsuspendServiceError', 1, function($admin) {
    // Perform hook code here...
});
```
---

## UnsuspendServiceSuccess

Executes upon successful service unsuspending.

### Parameters

| Variable | Type               | Notes |
|----------|--------------------|-------|
| $service | App\Models\Service |       |


### Response

No response supported.

### Example Code

```php
add_hook('UnsuspendServiceSuccess', 1, function($admin) {
    // Perform hook code here...
});
```
---




## TerminationService

Executes before automation actions when termination a service.

### Parameters

| Variable | Type               | Notes |
|----------|--------------------|-------|
| $service | App\Models\Service |       |


### Response

No response supported.

### Example Code

```php
add_hook('TerminationService', 1, function($admin) {
    // Perform hook code here...
});
```
---

## TerminationServiceError

Executes if automation fails during service termination.

### Parameters

| Variable | Type               | Notes |
|----------|--------------------|-------|
| $service | App\Models\Service |       |


### Response

No response supported.

### Example Code

```php
add_hook('TerminationServiceError', 1, function($admin) {
    // Perform hook code here...
});
```
---

## TerminationServiceSuccess

Executes upon successful service termination.

### Parameters

| Variable | Type               | Notes |
|----------|--------------------|-------|
| $service | App\Models\Service |       |


### Response

No response supported.

### Example Code

```php
add_hook('TerminationServiceSuccess', 1, function($admin) {
    // Perform hook code here...
});
```
---




## CancellationService

Executes before automation actions when cancellation a service.

### Parameters

| Variable | Type               | Notes |
|----------|--------------------|-------|
| $service | App\Models\Service |       |


### Response

No response supported.

### Example Code

```php
add_hook('CancellationService', 1, function($admin) {
    // Perform hook code here...
});
```
---

## CancellationServiceError

Executes if automation fails during service cancellation.

### Parameters

| Variable | Type               | Notes |
|----------|--------------------|-------|
| $service | App\Models\Service |       |


### Response

No response supported.

### Example Code

```php
add_hook('CancellationServiceError', 1, function($admin) {
    // Perform hook code here...
});
```
---

## CancellationServiceSuccess

Executes upon successful service cancellation.

### Parameters

| Variable | Type               | Notes |
|----------|--------------------|-------|
| $service | App\Models\Service |       |


### Response

No response supported.

### Example Code

```php
add_hook('CancellationServiceSuccess', 1, function($admin) {
    // Perform hook code here...
});
```
---


