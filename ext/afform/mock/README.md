# org.civicrm.afform-mock

This is a dummy extension used for integration testing. It should only
enabled on development sites.

## Basic Usage

```
cd afform/mock
cv en afform_mock
phpunit6 --group headless
phpunit6 --group e2e
```

## File Organization

Here are a few key folders:

* `ang/*`: These are example forms. Each example has 1-3 fils:
    * `FORMNAME.aff.html` (the layout/markup)
    * `FORMANME.aff.json` (metadata describing the form)
    * `FORMNAME.test.php` (PHPUnit class which uses the form)
* `tests/phpunit/api/v4/*`: These tests are focused on the behavior/dynamics
   of the API. (To wit: if you update a `server_route`, does the live
   `server_route` change accordingly?)

## PHPUnit Logical Organization

For tests in `ang/FORMNAME.test.php`:

* The test focuses on using the specific form.
* The test declares `@group e2e` and `@group ang`.
* The test extends `Civi\AfformMock\FormTestCase`. This has helpers like `prefill(...)` and `submit(...)` (which call
  the AJAX interface for `Afform.prefill` and `Afform.submit` respectively). It builds on `HttpTestTrait`.

For tests in `tests/phpunit/api/v4`:

* The tests focuses on Afform's APIv4 contract (entities/actions/parameters)
* The tests may use either `@group e2e` (`EndToEndInterface`) or `@group headless`
  (`HeadlessInterface`).
