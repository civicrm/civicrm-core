# Code Style

## Naming

The following naming conventions apply to directives defined within `afform.git`:

* Standalone directives (e.g. `afEntity` or `afField`), including forms (e.g. `afHtmlEditor`)
    * The directive name must begin with the `af` prefix.
    * Supplemental attributes SHOULD NOT begin with the `af` prefix.
    * Example: `<af-entity type="Activity" name="myPhoneCall">`

* Mix-in directives (e.g. `afMonaco` or `afApi4Action`)
    * The directive name must begin with the `af` prefix.
    * Supplemental attributes SHOULD begin with a prefix that matches the directive.
    * Example: `<button af-api4-action="['Job', 'process_mailings', {}]` af-api4-success-msg="ts('Processed pending mailings')">

__Discussion__: These differ in two ways:

* Namespacing
    * Standalone directives form an implicit namespace.
      (*Anything passed to `<af-entity>` is implicitly about `af-entity`.)
    * Mix-in directives must share a namespace with other potential mix-ins.
      (*The *)
* Directive arguments
    * Standalone directives only take input on the supplemental attributes (`type="..."`).
    * Mix-ins take inputs via the directive's attribute (`af-api4-action="..."`) and the supplemental attributes (`af-api4-success-msg="..."`).
