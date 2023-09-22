# Roadmap

The `afform` extension is a proof-of-concept.  It aims to demonstrate the core model/concept in which AngularJS
provides the standard data-format and component-model shared by developers (working in code files) and administrators
(working in a programmatic GUI).

As a proof-of-concept, it is necessarily incomplete.  In particular: (a) some functionality is envisioned for
additional extensions and (b) within this extension, there are known issues.

## Suite of Extensions

This extension is expected to be the base for a suite of related extensions:

* `afform`: Base framework and runtime. Provides APIs for developers.
* `afform_html`: Present web-based editor for customizing forms in HTML notation. Use Monaco or ACE.
  (In the near term, it's a simple demonstration that forms can be edited by users in a browser; in the
  long term, it occupies a smaller niche as a developer/power-admin tool.)
* `afform_gui`: Present a web-based editor for customizing forms with drag-drop/multi-pane UX.
  (In the long term, this is the main UI for admins.)
* `afform_auditor`: Report on upgrade compatibility. Test changesets. Highlight dangerous/unknown/unsupported form elements.
   Score maintainability of the system.

## Documentation

* Development and Refactoring: A guide for making changes to supported markup in a maintainable fashion.
  (*What if I need to rename a tag? What if I need to deprecate a tag? Ad nauseum*)

## Known Issues

Within this extension, there are things which need updating/addressing:

* Test coverage for key Angular directives (e.g. `af-api4-ctrl`, `af-api4-action`)
* There are several `FIXME`/`TODO` declarations in the code for checking pre-conditions, reporting errors, handling edge-cases, etc.
* Although afforms can be used in AngularJS, they don't fully support tooling like `cv ang:html:list`
  and `hook_civicrm_alterAngular` changesets. We'll need a core patch to allow that. (Ex: Define partials via callback.)
* We generally need to provide more services for managing/accessing data (e.g. `crm-api3`).
* We need a formal way to enumerate the library of available tags/directives/attributes. This, in turn, will drive the
  drag-drop UI and any validation/auditing.
* Haven't decided if we should support a `client_route` property (i.e. defining a skeletal controller and route for any form).
  On the plus side, make it easier to add items to the `civicrm/a` base-page. On the flipside, we don't currently have
  a strong use-case, and developers can get the same effect with `civix generate:angular-page` and embedding `<div hello-world/>`.
* Injecting an afform onto an existing Civi page is currently as difficult as injecting any other AngularJS widget --
  which is to say that (a) it's fine for a Civi-Angular page and (b) it's lousy on a non-Angular page.
* The data-storage of user-edited forms supports primitive branching and no merging or rebasing.  In an ideal world
  (OHUPA-4), we'd incorporate a merge or rebase mechanism (and provide the diff/export on web+cli).  To reduce unnecessary
  merge-conflicts and allow structured UI for bona-fide merge-conflicts, the diff/merge should be based on HTML elements and
  IDs (rather than lines-of-text).
* API Request Batching -- If a page makes multiple API calls at the same time, they fire as separate HTTP requests. This concern is somewhat
  mitigated by HTTP/2, but not really -- because each subrequest requires a separate CMS+CRM bootstrap. Instead, the JS API adapter should
  support batching (i.e. all API calls issued within a 5ms window are sent as a batch).
* Default CSS: There's no mechanism for defining adhoc CSS. This is arguably a feature, though, because the CSS classes
  should be validated (to ensure theme interoperability).
* `Civi/Angular/ChangeSet.php` previously had an integrity check that activated in developer mode
  (`\CRM_Core_Config::singleton()->debug && $coder->checkConsistentHtml($html)`). This has been removed because it was a bit brittle
  about self-closing HTML tags. However, the general concept of HTML validation should be reinstated as part of the `afform_auditor`.
* `hook_alterAngular` is used to inject APIv4 metadata for certain tags. This behavior needs a unit-test.
