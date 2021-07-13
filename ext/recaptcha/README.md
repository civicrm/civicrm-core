# ReCAPTCHA

Core extension to extract the reCAPTCHA functionality from CiviCRM core so it can be disabled/replaced.

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Using ReCAPTCHA

To add ReCAPTCHA to your own form add the following code to the `buildForm()` method on your form:
```php
  // Add reCAPTCHA
  if (is_callable(['CRM_Utils_ReCAPTCHA', 'enableCaptchaOnForm'])) {
    CRM_Utils_ReCAPTCHA::enableCaptchaOnForm($this);
  }
```

This will check the "standard" conditions for adding to a form which are currently:
* Do not add if user is logged in.

It will be added immediately before the last `<div class=crm-submit-buttons>` on the form.
