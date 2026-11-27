# test_facades

Test extension for entity facades, used by `tests/phpunit/Civi/Schema/EntityFacadeTest.php`.

It defines a `TestThing` entity (table `civicrm_test_thing`) with two facades, each declared in its own
`schema/*.entityType.php` file:

- `TestWidget`: things with `type = 'widget'`, with its own icon, label field and searchability.
- `TestGizmo`: things with `type = 'gizmo'`, which leaves out `description` using `alterFields`.

It also registers `TestThing` and `TestWidget` for custom fields (`managed/OptionValue_cg_extends_objects.mgd.php`),
and adds a calculated field to `TestThing` (`Civi/TestFacades/TestThingSpecProvider.php`) to check that it applies
to the facades too.

This is an [extension for CiviCRM](https://docs.civicrm.org/sysadmin/en/latest/customize/extensions/), licensed under AGPL-3.0.
