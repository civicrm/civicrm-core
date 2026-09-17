# CiviCRM core — AI contributor notes

## Writing PHPUnit tests

When adding new test methods, use APIv4 rather than APIv3:

- For creating fixtures, use `$this->createTestEntity('Entity', [...], 'identifier')` (see `Civi/Test/EntityTrait.php`) instead of `$this->callAPISuccess('Entity', 'create', [...])`.
- For reads/updates, use APIv4 entity classes (e.g. `Membership::get(FALSE)`, `Relationship::update(FALSE)`) instead of `$this->callAPISuccess('Entity', 'get'/'update', [...])`.

Use callAPIv3Success when the point of the test is testing the v3 api.

`createTestEntity()` needs a unique third-arg identifier when creating more than one record of the same entity type in a test, to avoid collisions in `$this->ids[...]` tracking.

When testing forms, use `Civi\Test\FormTrait::getTestForm($formName, $submittedValues, $urlParameters)` instead of hand-instantiating or mocking a QuickForm form.

Cleanup belongs in `tearDown()`, not in the test method. Test fixtures should not to use unique strings to protect against poor cleanup.

Try to use methods and variable names to carry the meaning. Add comments if they genuinely add something. Don't use comments to explain the difference between WIP approaches that were tried along the way — comment the code as it stands.

## Commits

- Lead the commit subject with `dev/core#NNNN` when the work traces to a GitLab issue (issues live at lab.civicrm.org/dev/core).
- Don't add a Claude-session-link trailer to commit messages — other contributors can't open it.
- Use `git commit --amend` rather than a new commit when fixing a previous commit that hasn't been pushed yet.
