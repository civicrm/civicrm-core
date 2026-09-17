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

## JavaScript & AngularJS

- Angular code lives in `ang/` (core) or `<ext>/ang/`. Each module is declared by a sibling `*.ang.php` listing its `js`, `partials`, `css` and `requires` — a new file has to be added there, and caches flushed (`cv flush`), before it will load.
- Write arrow functions rather than `function () { return ...; }`, including in older files that contain none — `.jshintrc` sets `esversion: 11`. In a controller that means referencing `this` directly and dropping any `ctrl`/`self` alias; keep the alias only where a callback can't be an arrow because a third-party API binds its own `this`.
- Don't add new lodash (`_.`) usage — use native `Array`/`Object` methods. Note that `_.each`/`_.map`/`_.filter` also accept plain objects, where the native equivalent needs `Object.entries()`/`Object.values()`.
- Prefer `!arr.includes(x)` to `arr.indexOf(x) < 0`, and `k in obj` to `Object.keys(obj).includes(k)`.
- Use APIv4, not APIv3: the `crmApi4` service in Angular (add `api4` to the module's `requires`), `CRM.api4()` elsewhere. Don't add new `crmApi`/`CRM.api3` calls.
- Wrap user-facing strings in `ts()`, in JS and in partials — `{{:: ts('Save') }}` — and use one-time binding (`::`) for anything that doesn't change.
- Keep jQuery DOM manipulation out of controllers; it belongs in a directive.

## Karma/Jasmine tests

- Specs live in `tests/karma/unit/` and are picked up by glob — no registration needed.
- `karma.conf.js` shells out to `cv` to build the module list, so the suite only runs against an installed site with `cv` on `PATH`.
- `npm test` runs karma in watch mode and never exits (`singleRun: false`); for a one-shot run use `node node_modules/karma/bin/karma start --single-run`.

## Commits

- Lead the commit subject with `dev/core#NNNN` when the work traces to a GitLab issue (issues live at lab.civicrm.org/dev/core).
- Don't add a Claude-session-link trailer to commit messages — other contributors can't open it.
- Use `git commit --amend` rather than a new commit when fixing a previous commit that hasn't been pushed yet.
