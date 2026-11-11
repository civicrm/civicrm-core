# CiviCRM core — AI contributor notes

Please note: the name, use and role of the agent must be clearly declared in commits, PRs, etc. This is because an agent bears no responsibility for its output, which is important context for CiviCRM's human maintainers who are responsible for building a trusted CRM from community contributions.

## Writing PHPUnit tests

When adding new test methods, use APIv4 rather than APIv3:

- For creating fixtures, use `$this->createTestEntity('Entity', [...], 'identifier')` (see `Civi/Test/EntityTrait.php`) instead of `$this->callAPISuccess('Entity', 'create', [...])`.
- For reads/updates, use APIv4 entity classes (e.g. `Membership::get(FALSE)`, `Relationship::update(FALSE)`) instead of `$this->callAPISuccess('Entity', 'get'/'update', [...])`.

Use callAPIv3Success when the point of the test is testing the v3 api.

`createTestEntity()` needs a unique third-arg identifier when creating more than one record of the same entity type in a test, to avoid collisions in `$this->ids[...]` tracking.

When testing forms, use `Civi\Test\FormTrait::getTestForm($formName, $submittedValues, $urlParameters)` instead of hand-instantiating or mocking a QuickForm form.

Cleanup belongs in `tearDown()`, not in the test method.

Compare strictly. `assertEquals()` uses `==`, so `assertEquals(1, '1')` passes:

- When the expected value is a literal, use `assertSame()`, or `assertTrue()` / `assertFalse()` / `assertNull()`. Put the expected value first.
- Match the type the code returns: APIv4 returns typed values (ints, floats for money, booleans); APIv3 returns strings (`'1'`, `'100.00'`).
- In tests run under both APIv3 and APIv4 (`@dataProvider versionThreeAndFour`), give the APIv4 value to `$this->apiValue()`, e.g. `$this->assertSame($this->apiValue(TRUE), $result['is_active'])`.
- Cast SQL counts: `$this->assertSame(0, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) ...'))`.

Don't assume record ids. Seeded and test-created rows don't start at id 1 (see `Civi\Test\Schema::setAutoIncrement()`), so a hard-coded id may point at the wrong record, or at none:

- Use the id from the record the test created: `createTestEntity()` returns the record, and `getTestEntityID($entity, $identifier)` gets its id later.
- For seeded records, use names: `'financial_type_id:name' => 'Donation'` in APIv4, or the lookup helpers in `Civi\Test\LookupHelperTrait` (e.g. `$this->getFinancialTypeID('Donation')`, `$this->getLocationTypeID('Home')`) where a name isn't accepted.

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

## Translation

- Wrap every user-facing string in `ts()`. In an extension, including those under `ext/`, use `E::ts()` so the extension's own translations are searched before core's.
- Pass `ts()` a string literal. The string extractor only collects literals, so `ts($label)` never reaches translators. For a value only known at runtime, such as a label read from metadata, use `_ts()`.
- Use placeholders instead of concatenating: `ts('Contribution %1 refunded', [1 => $id])`. Word order differs between languages, so a sentence built from fragments can't be translated. civilint checks that every `%N` has a replacement and every replacement is used.
- For counts, pass `plural` and `count` rather than branching on the number: `ts('Record deleted', ['plural' => '%count records deleted', 'count' => $n])`. Some languages have more than two plural forms.
- Don't use `ts()` on data: contact names, option labels entered by users and other database content are translated through multilingual columns or `civicrm_translation`, not gettext.
- In Smarty, use `{ts 1=$name}Hello %1{/ts}`. Extension templates need `{crmScope extensionKey='<ext key>'}` around them (or `domain=` on each `{ts}`), or core's translations are used.
- In extension JS, get a domain-bound function with `const ts = CRM.ts('<ext key>')`; in an Angular controller, expose it as `$scope.ts` for partials.
- In Afform `*.aff.html`, wrap hand-written text (headings, help text, button labels) in `{{:: ts('...') }}` so the string extractor collects it. Titles and field labels set in the FormBuilder editor (`af-title`, `af-field` `defn`) stay plain: Afform translates them at render time, with the providing extension's translations first.
- SearchKit displays in `managed/*.mgd.php`: leave their texts bare (labels, descriptions, tooltips, empty values, button and link texts). `SearchDisplay.get` translates them on read with the managing extension's translations; `E::ts()` there runs when the record is reconciled and stores one locale's text in the database. Texts containing markup or Smarty, and the `rewrite` of an `html` column, aren't translated on read.

## Commits

- Lead the commit subject with `dev/core#NNNN` when the work traces to a GitLab issue (issues live at lab.civicrm.org/dev/core).
- Don't add a Claude-session-link trailer to commit messages — other contributors can't open it.
- Use `git commit --amend` rather than a new commit when fixing a previous commit that hasn't been pushed yet.
