# Afform Auditor Backlog

The general design of validation mechanism is being discussed in https://github.com/totten/afform/issues/20

For the moment, it's good to keep some notes on specific issues for the
validator to catch. Loosely/informally:

* HTML partials should be well-formed/parseable XML
* Warnings about any unrecognized tags/attributes/classes.
* `<af-model-list>`, `<af-model-prop>`, `<af-model>`, `<af-field>` should have suitable relationships.
* `<af-model-prop>` should reference legit entities.
* `<af-field>` should reference legit fields.
    * Future consideration: how to validate when it's part of a subform?
* `<af-model>` should reference a declared model.
* `<af-field field-defn="...">` should contain an object.
* `<a>` should have `href` or `ng-click` or `aff-api4-action`
* Accept a restricted subset of HTML (e.g. `p h1 h2 h3` but not `script` or `[onclick]`)
* Accept a restricted subset of BootstrapCSS
* Accept a restricted subset of Angular HTML
* Accept directives created via Afform
