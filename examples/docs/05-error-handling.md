# Example 05: Error Handling

**File:** `examples/05-error-handling.php`
**Run:** `php examples/05-error-handling.php`

Handle every error category correctly, with the right response for each type
(alert admin, retry with backoff, log and skip).

---

## Error Category Map

```
kwtSMS API Error
  │
  ├─ Auth / Account  (ERR001, ERR003, ERR004, ERR005)
  │     Stop sending. Alert admin. Do NOT retry.
  │
  ├─ Balance  (ERR010, ERR011)
  │     Stop sending. Alert admin to top up.
  │
  ├─ Number / content  (ERR006-009, ERR025-027)
  │     Log the number/content. Do NOT retry with same input.
  │     Fix the input, then retry.
  │
  ├─ Duplicate SMS protection  (ERR028, opt-in per account)
  │     Wait for the account's window (10 s by default). Retry once.
  │     Entire request is rejected if any number got the same message within the window.
  │
  ├─ Queue full  (ERR013)
  │     Wait 30 seconds. Retry with exponential backoff.
  │     Handled automatically by the library in bulk sends.
  │
  ├─ IP lockdown  (ERR024)
  │     Add server IP to whitelist at kwtsms.com → API → IP Lockdown.
  │     Do NOT retry automatically.
  │
  └─ Spam / bad language  (ERR031, ERR032)
        Review message content.
        Do NOT retry with same message.
```

---

## Step-by-Step

### The `action` field

The library enriches every error response with an `action` field:

```php
$result = $sms->send($phone, $message);

if ($result['result'] !== 'OK') {
    $code        = $result['code'];        // "ERR003"
    $description = $result['description']; // "Authentication error..."
    $action      = $result['action'];      // "Check your API username and password"
}
```

Use `action` directly in admin dashboards or logs. No need to maintain your own
error-to-action mapping.

### Handling ERR028 (duplicate SMS protection)

```php
if ($result['code'] === 'ERR028') {
    sleep(10); // your account's window, 10 seconds by default
    $retry = $sms->send($phone, $message);
    // Do not retry more than once
}
```

ERR028 is opt-in per account (Duplicate SMS protection in the account's API
settings). When it is on and any number in the request was sent the same message
within the account's window (10 seconds by default, configurable per account),
the **entire request** is rejected with ERR028 and nothing is charged. For OTP,
always send to one number per request.

### Inspect all mapped error codes

```php
use KwtSMS\ApiErrors;

foreach (ApiErrors::ERRORS as $code => $info) {
    echo "[{$code}] {$info['description']} ({$info['action']})\n";
}
```

### Production error handling pattern

```php
function send_with_alerting(KwtSMS $sms, string $phone, string $msg): bool
{
    $result = $sms->send($phone, $msg);

    if ($result['result'] === 'OK') {
        save_balance($result['balance-after']); // persist after every send
        save_msg_id($result['msg-id']);          // needed for status/DLR lookups
        return true;
    }

    $code = $result['code'] ?? '';
    error_log("[kwtSMS] [{$code}] {$result['description']}");

    $fatal = ['ERR001', 'ERR003', 'ERR004', 'ERR005', 'ERR010', 'ERR011'];
    if (in_array($code, $fatal, true)) {
        alert_admin("kwtSMS error [{$code}]: {$result['description']}");
    }

    return false;
}
```

---

## Full Error Code Reference

See [reference.md](reference.md#error-code-reference) for the complete table of
all error codes (ERR001-ERR033) with descriptions and recommended actions.
