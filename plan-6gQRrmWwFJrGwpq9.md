# Plan: Fix Registration Page Not Found Error

**Task:** Registration is not working - gives a Page not Found error  
**Task ID:** 6gQRrmWwFJrGwpq9

---

## Root Cause Analysis

There are **two compounding bugs** that together make registration completely broken.

### Bug 1: Missing `index()` method on `Register_Controller` (the 404 cause)

**File:** `application/controllers/register.php`

Kohana's Router defaults `Router::$method = 'index'` (see `system/libraries/Router.php`, line 28). When a user visits `/register` or `/register/index`, Kohana calls `index()` on the controller. Since `Register_Controller` has no `index()` method, execution falls through to `Controller_Core::__call()` (`system/libraries/Controller.php`, line 45–48), which fires `Event::run('system.404')` — producing the "Page not Found" error.

The same problem exists inside the `register()` method itself: it calls `$this->index()` at line 60 of `register.php` to re-render the form after submission. That call also 404s.

**Comparison:** `Login_Controller` (application/controllers/login.php, line 22–26) has a correctly defined `index()` method that renders the template. `Register_Controller` is missing the equivalent.

**Fix:** Add an `index()` method to `Register_Controller` that simply renders the template (the constructor already sets up all the required view variables):

```php
public function index() {
    // Template, navi, and content view are already set in __construct().
    // Nothing more is needed — the template controller renders automatically.
}
```

This is a one-line fix. No architectural change needed.

---

### Bug 2: Registration form is entirely commented out in the view

**File:** `application/views/registerCont.php`, lines 6–19

The entire registration form is wrapped in a `/* ... */` block comment, and line 21 renders a hard-coded German string: `<p> Die Registrierung ist momentan deaktiviert!</p>` ("Registration is currently disabled!").

Even after fixing the 404, visiting the registration page will show no form — just that message. The `register()` action will never receive a POST submission.

**Fix:** Uncomment the form block (lines 6–19) and remove (or conditionally display) the "disabled" message.

---

## Implementation Plan

### Step 1 — Add `index()` to `Register_Controller`

**File:** `application/controllers/register.php`

Add after the `__construct()` method (around line 20):

```php
/**
 * Displays the registration form.
 */
public function index() {
    // Template variables are initialised in __construct().
}
```

This mirrors the pattern used by `Login_Controller::index()`.

### Step 2 — Restore the registration form in the view

**File:** `application/views/registerCont.php`

Remove the `/*` and `*/` comment delimiters around the form (lines 6 and 19), and remove or replace the "disabled" paragraph on line 21. The restored view should be:

```php
<?php
    if(!empty($error)){
        echo error::display($error);
    }
    echo form::open('register/register', array("id" => "regForm"))   .' <br> ';
    echo form::label('name', 'Name:')                                .' <br> ';
    echo form::input('name')                                         .' <br> ';
    echo form::label('password', 'Passwort:')                        .' <br> ';
    echo form::password('password')                                  .' <br> ';
    echo form::label('passwordRep', 'Passwort wiederholen:')         .' <br> ';
    echo form::password('passwordRep')                               .' <br> ';
    echo form::label('email', 'E-Mail:')                             .' <br> ';
    echo form::input('email')                                        .' <br> ';
    echo form::label('captcha_response', 'Sicherheitsüberprüfung:')  .' <br> ';
    echo $captcha->render()                                          .' <br> ';
    echo form::input('captcha_response')                             .' <br> ';
    echo form::submit('submit', 'Registrieren')                      .' <br> ';
    echo form::close();
?>
```

### Step 3 — Remove debug output from `sendActivationEmail()`

**File:** `application/controllers/register.php`, line 119

`echo $verifyLink;` is a debug leftover that dumps the activation link directly into the HTTP response. This is a security issue in any non-local environment — it leaks registration tokens. Remove this line. The TODO comment on line 125 (`//TODO Test email::send(...)`) indicates the real email send is also still disabled; that should be addressed separately, but the `echo` must go now regardless.

---

## Secondary Issues (not blocking the fix, but noted)

### Logic bug: password mismatch check is inverted

**File:** `application/controllers/register.php`, line 42

```php
}elseif(!$password == $passwordRep){
```

The `!` negates `$password` (casting it to bool first) rather than negating the equality check. The correct form is:

```php
}elseif($password !== $passwordRep){
```

As written, this check never correctly catches a mismatch when both passwords are non-empty strings. This is a pre-existing bug but should be fixed alongside the routing fix since it makes the registration form silently accept mismatched passwords.

### `$this->index()` call at end of `register()` is a bad pattern

**File:** `application/controllers/register.php`, line 60

Calling `$this->index()` from within `register()` is unusual and fragile. The `Template_Controller` base class renders the template automatically after the action method returns — so the explicit call to `$this->index()` is redundant and was likely added as a workaround for the missing method. Once `index()` is added as a proper empty method, the call at line 60 is harmless (the template just renders twice in theory, though in practice the Template_Controller handles this). It should be removed for clarity.

---

## Summary of Changes

| File | Change |
|---|---|
| `application/controllers/register.php` | Add `index()` method |
| `application/controllers/register.php` | Fix password comparison operator (`!$password ==` → `!==`) |
| `application/controllers/register.php` | Remove `echo $verifyLink;` debug line |
| `application/controllers/register.php` | Remove redundant `$this->index()` call at line 60 |
| `application/views/registerCont.php` | Uncomment the registration form |
| `application/views/registerCont.php` | Remove "registration disabled" paragraph |

The 404 is fixed by Steps 1 alone. Step 2 is required for the page to actually be usable. Steps 3 and the secondary fixes are correctness and security improvements that should be done in the same pass.
