# Form Hooks: Updating forms via declarative selector

> __WIP__ This describes functionality that is intended but not actual.
> The hooks/change-sets currently work with core's `*.html` files; but we need
> a little patching to make them work with afform's `*.html` files.

Form hooks offer a different style of customization than [Form CRUD](docs/crud.md).  In the CRUD style, you use an API
to save an updated form -- which stores the exact form (as designed by the user).  In the hook style, you wait until a
form is being displayed; and, as part of the rendering process, you filter the output.

## Example

In this example, we use `hook_civicrm_alterAngular` to apply a filter to the file `~/crmMailing/BlockSummary.html` --
the filter selects an element by CSS class (`crm-group`) and then appends a new field to the bottom of that element:

```php
function mailwords_civicrm_alterAngular(\Civi\Angular\Manager $angular) {
  $changeSet = \Civi\Angular\ChangeSet::create('inject_mailwords')
    // ->requires('crmMailing', 'mailwords')
    ->alterHtml('~/crmMailing/BlockSummary.html',
      function (phpQueryObject $doc) {
        $doc->find('.crm-group')->append('
          <div crm-ui-field="{name: \'subform.mailwords\', title: ts(\'Keywords\')}">
            <input crm-ui-id="subform.mailwords" class="crm-form-text" name="mailwords" ng-model="mailing.template_options.keywords">
          </div>
        ');
      });
  $angular->add($changeSet);
}
```

## Comparison: CRUD vs Hook

Similarities:

* Both styles have safe and unsafe use-cases.
* The safety generally depends on the specific components being used.
    * Ex: If you add a new `crm-ui-field` (via either style), that could be safe/maintainable (if `crm-ui-field` is supported, stable, widely used) or it could be risky/hard-to-maintain
      (if `crm-ui-field` is undocumented and rarely used).
* In either style, there are legitimate scenarios for someone to reference a safe or risky component.
* In either style, it is difficult for a person to keep track of whether the referenced components are safe or risky. The list of experimental/supported/deprecated components will be long and will change over time. We'd rather have tooling to keep track of this.
* Provided you abide by the code-style of the example, it is possible for the framework to build a list of all CRUD'd forms and all change-sets. In turn, it's possibile to loop through them, audit them, and warn about anything which appears unsafe.

Differences:

* CRUD is concrete. It saves what you tell it to save, and it doesn't do anything else. This makes it more suitable as the conceptual model for the users' GUI editor.
* Change-sets are abstract. They can change many components (e.g. converting every `<h1>` to a `<div class="my-header-1">`).
* The good and bad consequences of CRUD are limited to the specific things you manipulated.
* The good and bad consequences of change-sets can apply across a wider range of things.
* CRUD locks-in your changes and preferences. If there's an upgrade, your form should remain safely as it was before (provided the form relied on supported components).
* Change-sets are adaptable. If there's an upgrade or edit to the underlying form, the change-set will be used on top of the latest revision. If the change-set is revised, then the latest change-set will be used without needing to edit the forms individually.

I expect future people will develop better insight on the trade-offs (and may design other, more nuanced options). For an initial first-read on when to use each, my expectations are that:

* CRUD is more appropriate when an *administrator* is expressing a *policy opinion* about the importance of information on a page.
* Change-sets are more appropriate when a *developer* is changing an *implementation detail* (such as the CSS classes used to style all calendar widgets).

## See also

https://docs.civicrm.org/dev/en/latest/framework/angular/changeset/
