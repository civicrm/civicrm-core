To run the tests you need to configure it as described in the wiki:

https://wiki.civicrm.org/confluence/display/CRMDOC/Testing

## Configuration

If the test environment has been created by a common build-profile using
[buildkit](https://github.com/civicrm/civicrm-buildkit/)'s `civibuild`
command (such as `drupal-clean` or `wp-demo`), then you should be able to
execute tests without further configuration.

Otherwise, you need to install [`cv`](https://github.com/civicrm/cv)
and fill in missing test data:

 * `cd` into your Drupal/WordPress site and run `cv vars:fill`. This will create a file `~/.cv.json`.
   * Tip: If you need to share this installation with other local users, you may specify `export CV_CONFIG=/path/to/shared/file.json`
 * Edit the file `~/.cv.json`. You may need to fill some or all of these details:
   * Credentials for an administrative CMS user (`ADMIN_USER`, `ADMIN_PASS`, `ADMIN_EMAIL`)
   * Credentials for a non-administrative CMS user (`DEMO_USER`, `DEMO_PASS`, `DEMO_EMAIL`)
   * Credentials for an empty test database (`TEST_DB_DSN`)

## Suites

`civicrm-core` includes multiple test suites. Each suite makes use of the environment differently:

| Runner | Suite | Type | CMS | Typical Base Class | Comment |
| ------ | ----- | ---- | --- | ------------------ | ----------- |
| PHPUnit |`api_v3_AllTests`|`headless`|Agnostic|`CiviUnitTestCase`|Requires `CIVICRM_UF=UnitTests`|
| PHPUnit |`Civi\AllTests`|`headless`|Agnostic|`CiviUnitTestCase`|Requires `CIVICRM_UF=UnitTests`|
| PHPUnit |`CRM_AllTests`|`headless`|Agnostic|`CiviUnitTestCase`|Requires `CIVICRM_UF=UnitTests`|
| PHPUnit |`E2E_AllTests`|`e2e`|Agnostic|`CiviEndToEndTestCase`|Useful for command-line scripts and web-services|
| PHPUnit |`WebTest_AllTests`|`e2e`|Drupal|`CiviSeleniumTestCase`|Useful for tests which require a full web-browser|
| Karma ||`unit`|Agnostic|||
| QUnit ||`e2e`|Agnostic||Run each test in a browser. See README.|

Headless test suites like `CRM_AllTests` run on a secondary, headless CiviCRM database.  They use a
fake CMS/UF (named `UnitTests`) and aggressively manipulate the content of the database (e.g.
truncating, dropping, or creating tables at a whim).

E2E tests run against a full installation of CiviCRM with an active, integrated CMS.  These tests
may do some manipulation on the database, so be careful to only run these on developmental
systems...  and have a fallback-plan in case the tests screw-up your database.

## PHPUnit Usage

You may invoke the PHPUnit tests using the legacy wrapper command (`tools/scripts/phpunit`), e.g.

```bash
## Invoke "CRM_AllTests" with the legacy wrapper
cd tools
./scripts/phpunit CRM_AllTests

## Invoke "E2E_AllTests" with the legacy wrapper
cd tools
./scripts/phpunit E2E_AllTests

```

The advantage of using the legacy wrapper is that works with multiple versions of CiviCRM (e.g.
`4.4` or `4.7`) and has shorter commands.  However, if you try to use it with an IDE, it
may not work well.

Alternatively, you may invoke the PHPUnit tests with a standalone copy of PHPUnit (e.g. `phpunit4`), e.g.

```bash
## Invoke "CRM_AllTests" using a standalone copy of PHPUnit
env CIVICRM_UF=UnitTests phpunit4 ./tests/phpunit/CRM/AllTests.php

## Invoke "E2E_AllTests" using a standalone copy of PHPUnit
phpunit4 ./tests/phpunit/E2E/AllTests.php
```

The advantage of using a standalone copy of PHPUnit is that integrates better with an IDE.
However, it's only supported on CiviCRM 4.7+, and you may need to set an environment variable.
