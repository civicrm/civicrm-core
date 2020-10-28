# Embedding Forms: Afform as reusable building-block

In the [quick-start example](quickstart.md), we registered a new route (`"server_route": "civicrm/hello-world"`) -- this created a
simple, standalone page with the sole purpose of displaying the `helloWorld` form.  What if we want to embed the form
somewhere else -- e.g. as a dialog inside an event-listing or membership directory?  Afforms are actually *re-usable
sub-forms*.

How does this work?  Every `afform` is an *AngularJS directive*.  For example, `hello-world` can be embedded with:

```html
<div hello-world=""></div>
```

Moreover, you can pass options to `helloWorld`:

```html
<div hello-world="{phaseOfMoon: 'waxing'}"></div>
```

Now, in `ang/helloWorld.aff.html`, you can use `options.phaseOfMoon`:

```html
Hello, {{routeParams.name}}. The moon is currently {{options.phaseOfMoon}}.
```

## Example: Contact record

Is this useful? Let's suppose you're building a contact record page.

First, we should make a few building-blocks:

1. `ang/myContactName.aff.html` displays a sub-form for editing first name, lastname, prefix, suffix, etc.
2. `ang/myContactAddresses.aff.html` displays a sub-form for editing street addresses.
3. `ang/myContactEmails.aff.html` displays a sub-form for editing email addresses.

Next, we should create an overall `ang/myContact.aff.html` which uses these building-blocks:

```html
<div ng-form="contactForm">
  <div crm-ui-accordion="{title: ts('Name')}">
    <div my-contact-name="{cid: routeParams.cid}"></div>
  </div>
  <div crm-ui-accordion="{title: ts('Street Addresses')}">
    <div my-contact-addresses="{cid: routeParams.cid}"></div>
  </div>
  <div crm-ui-accordion="{title: ts('Emails')}">
    <div my-contact-emails="{cid: routeParams.cid}"></div>
  </div>
</div>
```

And we should create a `ang/myContact.aff.json` looking like

```json
{
  "server_route": "civicrm/contact", 
  "requires" : ["myContactName", "myContactEmails", "myContactAddresses"]
}
```
> *(FIXME: In the parent form's `*.aff.json`, we need to manually add `myContactName`, `myContactAddresses`, `myContactEmails` to the `requires` list. We should autodetect these instead.)*

We've created new files, so we'll need to flush the file-index

```
cv flush
```

and now we can open the page

```
cv open 'civicrm/contact?cid=100'
```

What does this buy us?  It means that a downstream admin (using APIs/GUIs) can fork `ang/myContactName.aff.html` --
but all the other components can cleanly track the canonical release. This significantly reduces the costs and risks
of managing upgrades and changes.
