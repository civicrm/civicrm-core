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

## Known Issues (`org.civicrm.afform`)

* There are several `FIXME`/`TODO` declarations in the code for checking pre-conditions, reporting errors, handling edge-cases, etc.
* Although afforms are can be used in AngularJS, they don't fully support tooling like `cv ang:html:list`
  and `hook_civicrm_alterAngular` changesets. We'll need a core patch to allow that. (Ex: Define partials via callback.)
* We generally need to provide more services for managing/accessing data (e.g. `crm-api3`).
* We need a formal way to enumerate the library of available tags/directives/attributes. This, in turn, will drive the
  drag-drop UI and any validation/auditing.
* Need to implement the `Afform.revert` API to undo local customizations.
* Haven't decided if we should support a `client_route` property (i.e. defining a skeletal controller and route for any form).
  On the plus side, make it easier to add items to the `civicrm/a` base-page. On the flipside, we don't currently have
  a strong use-case, and developers can get the same effect with `civix generate:angular-page` and embedding `<div afform-helloworld/>`.
