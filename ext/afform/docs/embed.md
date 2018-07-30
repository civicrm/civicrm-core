## Development: Embedding forms

In the [quick-start example](quickstart.md), we registered a new route (`"server_route": "civicrm/hello-world"`) -- this created a
simple, standalone page with the sole purpose of displaying the `helloworld` form.  What if we want to embed the form
somewhere else -- e.g. as a dialog inside an event-listing or membership directory?  Afforms are actualy *re-usable
sub-forms*.

How does this work?  Every `afform` is an *AngularJS directive*.  For example, `helloworld` can be embedded with:

```html
<div afform-helloworld=""></div>
```

Moreover, you can pass options to `helloworld`:

```html
<div afform-helloworld="{phaseOfMoon: 'waxing'}"></div>
```

Now, in `afform/helloworld/layout.html`, you can use `options.phaseOfMoon`:

```html
Hello, {{routeParams.name}}. The moon is currently {{options.phaseOfMoon}}.
```

## Development: Embedding forms: Contact record example

Is this useful? Let's suppose you're building a contact record page.

First, we should make a few building-blocks:

1. `afform/contactName/layout.html` displays a sub-form for editing first name, lastname, prefix, suffix, etc.
2. `afform/contactAddressess/layout.html` displays a sub-form for editing street addresses.
3. `afform/contactEmails/layout.html` displays a sub-form for editing email addresses.

Next, we should create an overall `afform/contact/layout.html` which uses these building-blocks:

```html
<div ng-form="contactForm">
  <div crm-ui-accordion="{title: ts('Name')}">
    <div afform-contact-name="{cid: routeParams.cid}"></div>
  </div>
  <div crm-ui-accordion="{title: ts('Street Addresses')}">
    <div afform-contact-addresses="{cid: routeParams.cid}"></div>
  </div>
  <div crm-ui-accordion="{title: ts('Emails')}">
    <div afform-contact-emails="{cid: routeParams.cid}"></div>
  </div>
</div>
```

> *(FIXME: In the parent form's `meta.json`, we need to manually add `afformContactName`, `afformContactAddresses`, `afformContactEmails` to the `requires` list. We should autodetect these instead.)*

What does this buy us?  It means that a downstream admin (using APIs/GUIs) can fork `afform/contactName/layout.html` --
but all the other components can cleanly track the canonical release. This significantly reduces the costs and risks
of manging upgrades and changes.
