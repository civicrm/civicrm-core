# org.civicrm.afform (Early Proof of Concept)

![Screenshot](/images/screenshot.png)

The Affable Administrative Angular Form Framework (`afform`) is a system for administering AngularJS-based forms
in CiviCRM which:

1. Allows developers to declaratively define a canonical, baseline form.
2. Allows administrators (or administrative tools) to use the CRUD API to customize the forms.
3. Allows developers to embed these forms in other CiviCRM-AngularJS apps.
4. (WIP; pending upstream support) Allow developers to apply [change sets](https://docs.civicrm.org/dev/en/latest/framework/angular/changeset/).

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

* PHP v5.4+
* CiviCRM v5.3+

## Installation (Web UI)

This extension has not yet been published for installation via the web UI.

## Installation (CLI, Zip)

Sysadmins and developers may download the `.zip` file for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
cd <extension-dir>
cv dl org.civicrm.afform@https://github.com/FIXME/org.civicrm.afform/archive/master.zip
```

## Installation (CLI, Git)

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git) repo for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone https://github.com/FIXME/org.civicrm.afform.git
cv en afform
```

## Development: Quick Start

As an extension author, you can define a form along with its default,
canonical content. Simply create a folder named  `afform/<MY-FORM>`. In
this example, we create a form named `helloworld`:

```
$ cd /path/to/my/own/extension
$ mkdir -p afform/helloworld
$ echo '{"server_route": "civicrm/hello-world"}' > afform/helloworld/meta.json
$ echo '<div>Hello {{routeParams.name}}</div>' > afform/helloworld/layout.html
$ cv flush
```

A few things to note:

* We defined a route `civicrm/hello-world`. This is defined in the same routing system used by CiviCRM forms.
* The file `layout.html` is an AngularJS HTML document. It has access to all the general features of Angular HTML (discussed more later).
* After creating a new form or file, we should flush the cache.
* If you're going to actively edit/revise the content of the file, then you should navigate
  to **Administer > System Settings > Debugging** and disable asset caching.

Now that we've created a form, we'll want to determine its URL. As with most
CiviCRM forms, the URL depends on the CMS configuration. Here is an example
from a local Drupal 7 site:

```
$ cv url "civicrm/hello-world"
"http://dmaster.localhost/civicrm/hello-world"
$ cv url "civicrm/hello-world/#/?name=world"
"http://dmaster.localhost/civicrm/hello-world/#/?name=world"
```

Open the URLs and see what you get.

## Development: Writing HTML templates

In AngularJS, the primary language for orchestrating a screen is HTML. You can embed code in here.

One key concept *scope* -- the *scope* defines the list of variables which you can access.  By default, `afform`
provides a few variables within the scope of every form:

* `routeParams`: This is a reference to the [$routeParams](https://docs.angularjs.org/api/ngRoute/service/$routeParams)
  service. In the example, we used `routeParams` to get a reference to a `name` from the URL.
* `meta`: The stored meta data (`meta.json`) for this form.
* `ts`: This is a utility function which translates strings, as in `{{ts('Hello world')}}`.

Additionally, AngularJS allows *directives* -- these are extra HTML attributes and HTML tags. For example:

* `ng-if` will conditionally create or destroy elements in the page.
* `ng-repeat` will loop through data.
* `ng-style` and `ng-class` will conditionally apply styling.

A full explanation of these features is out-of-scope for this document, but the key point is that you can use standard
AngularJS features.

## Development: Writing HTML templates: Contact record example

Let's say we want `helloworld` to become a basic "View Contact" page. A user
would request a URL like:

```
http://dmaster.localhost/civicrm/hello-world/#/?cid=123
```

How do we use the `cid` to get information about the contact? Update `layout.html` to include data from APIv3:

```html
<div ng-if="routeParams.cid"
  afform-api3="['Contact', 'get', {id: routeParams.cid}]"
  afform-api3-ctrl="apiData">

  <div ng-repeat="contact in apiData.result.values">
    <h1 crm-page-title="">{{contact.display_name}}</h1>

    <h3>Key Contact Fields</h3>

    <div><strong>Contact ID</strong>: {{contact.contact_id}}</div>
    <div><strong>Contact Type</strong>: {{contact.contact_type}}</div>
    <div><strong>Display Name</strong>: {{contact.display_name}}</div>
    <div><strong>First Name</strong>: {{contact.first_name}}</div>
    <div><strong>Last Name</strong>: {{contact.last_name}}</div>

    <h3>Full Contact record</h3>

    <pre>{{contact|json}}</pre>
  </div>
</div>
```

This example is useful pedagogically and may be useful in a crunch -- but in the longer term,
we should have a richer library of directives so that typical user-managed forms don't drill-down
at this level of detail.

## Development: Form CRUD API

Now that we've defined a baseline form, it's possible for administrators and
GUI applications to inspect the form using the API:

```
$ cv api afform.getsingle name=helloworld
{
    "name": "helloworld",
    "requires": [
        "afformCore"
    ],
    "title": "",
    "description": "",
    "layout": {
        "#tag": "div",
        "#children": [
            "Hello {{routeParams.name}}"
        ]
    },
    "id": "helloworld"
}
```

Additionally, you can also update the forms:

```
$ cv api afform.create name=helloworld title="The Foo Bar Screen"
{
    "is_error": 0,
    "version": 3,
    "count": 2,
    "values": {
        "name": "helloworld",
        "title": "The Foo Bar Screen"
    }
}
```

A few important things to note about this:

* The changes are only applied on this site.
* Once you make a change with the CRUD API, there will be two copies of the form:
    * `[myextension]/afform/helloworld/` is the default, canonical version.
    * `[civicrm.files]/afform/helloworld/` is the local, custom version.
* The `layout` field is stored as an Angular-style HTML document (`layout.html`), so you can edit it on disk like
  normal Angular code. However, when CRUD'ing the `layout` through the API, it is presented in JSON-style.

## Development: Embedding forms

In the quick-start example, we registered a new route (`"server_route": "civicrm/hello-world"`) -- this created a
simple, standalone page with the sole purpose of displaying the `helloworld` form.  What if we want to embed the form
somewhere else -- e.g. as a dialog inside an event-listing or membership directory?  Afforms are actualy *re-usable
sub-forms*.

How does this work?  Every `afform` is an *AngularJS directive*.  For example, `helloworld` can be embedded with:

```html
<div afform-hellworld=""></div>
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

First, let's make a few building-blocks:

1. `afform/contactName/layout.html` displays a sub-form for editing first name, lastname, prefix, suffix, etc.
2. `afform/contactAddressess/layout.html` displays a sub-form for editing street addresses.
3. `afform/contactEmails/layout.html` displays a sub-form for editing email addresses.

Now you can create an overall `afform/contact/layout.html` which uses these building-blocks:

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

## Development: Full AngularJS

Afform is really only a subset of AngularJS -- it emphasizes the use of *directives* as a way to *pick and arrange* the
parts of your form.  There is more to AngularJS -- such as client-side routing, controllers, services, etc.  What to do
if you need these? Here are few tricks:

* You can create your own applications and pages with full AngularJS. (See also: [CiviCRM Developer Guide: AngularJS: Quick Start](https://docs.civicrm.org/dev/en/latest/framework/angular/quickstart/)).
  Then embed the afform (like `helloworld`) in your page with these steps:
    * Declare a dependency on module (`afformHelloworld`). This is usually done in `ang/MYMODULE.ang.php` and/or `ang/MYMODULE.js`.
    * In your HTML template, use the directive `<div afform-helloworld=""></div>`. If you want to provide extra data or services for the form author, pass them along.
* You can write your own directives with full AngularJS (e.g. `civix generate:angular-directive`). These directives become available for use in other afforms.
* If you start out distributing an `afform` and later find it too limiting, then you can change your mind and convert it to static code in full AngularJS.
  As long as you name it consistently (e.g. `afform-helloworld`), downstream consumers can use the static version as a drop-in replacement.
    *(FIXME: But if you do this, could you still permit downstream folks customize the HTML?)

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
