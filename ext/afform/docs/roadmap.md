## Roadmap

The `afform` extension is a proof-of-concept.

## Known Issues

* The code is currently written as a proof-of-concept. There are several `FIXME`/`TODO` declarations in the code
  for checking pre-conditions, reporting errors, handling edge-cases, etc.
* Although afforms are can be used in AngularJS, they don't fully support tooling like `cv ang:html:list`
  and `hook_civicrm_alterAngular` changesets. We'll need a core patch to allow that.
* We generally need to provide more services for managing/accessing data (e.g. `crm-api3`).
* Need to implement the `Afform.revert` API to undo local customizations.
* Haven't decided if we should support a `client_route` property (i.e. defining a skeletal controller and route for any form).
  On the plus side, make it easier to add items to the `civicrm/a` base-page. On the flipside, we don't currently have
  a strong use-case, and developers can get the same effect with `civix generate:angular-page` and embedding `<div afform-helloworld/>`.
