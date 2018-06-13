# org.civicrm.afform

![Screenshot](/images/screenshot.png)

The Affable Administrative Angular Form Framework (`afform`) is a system for administering AngularJS-based forms
in CiviCRM which:

1. Allows developers to declaratively define a canonical, baseline form.
2. Allows administrators (or administrative tools) to use the API to revise the forms.

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

As an upstream publisher of a form, you can define the default, canonical
substance of the form by creating a folder named `afform/<MY-FORM>`. In
this example, we create a form named `foobar`:

```
$ cd /path/to/my/own/extension
$ mkdir -p afform/foobar
$ echo '{"server_route": "civicrm/pretty-page"}' > afform/foobar/meta.json
$ echo '<div>Hello {{routeParams.name}}</div>' > afform/foobar/layout.html
$ cv flush
```

A few things to note:

* The file `layout.html` is an AngularJS HTML document. It has access to all the general templating features, such as variable substition
  (`{{routeParams.name}}`) and the standard library of directives (`ng-if`, `ng-style`, `ng-repeat`, etc).
* After creating a new form or file, we should flush the cache.
* If you're going to actively edit/revise the content of the file, then you should navigate
  to **Administer > System Settings > Debugging** and disable asset caching.

Now that we've created a form, we'll want to determine its URL:

```
$ cv url civicrm/pretty-page
"http://dmaster.localhost/civicrm/pretty-page"
$ cv url civicrm/pretty-page/#/?name=world
"http://dmaster.localhost/civicrm/pretty-page/#/?name=world"
```

You can open the given URL in a web-browser.

## Development: Form CRUD API

Now that we've defined a baseline form, it's possible for administrators and
GUI applications to inspect and revise this form using the API.

```
$ cv api afform.getsingle name=foobar
{
    "name": "foobar",
    "requires": [
        "afform",
        "crmUi",
        "crmUtil"
    ],
    "title": "",
    "description": "",
    "layout": {
        "#tag": "div",
        "#children": [
            "Hello {{routeParams.name}}"
        ]
    },
    "id": "foobar"
}
$ cv api afform.create name=foobar title="The Foo Bar Screen"
{
    "is_error": 0,
    "version": 3,
    "count": 2,
    "values": {
        "name": "foobar",
        "title": "The Foo Bar Screen"
    }
}
```

## Development: Scope variables and functions

In AngularJS, every component has its own *scope* -- which defines a list of variables you can access.

By default, `afform` provides a few variables in the scope of every form:

* `routeParams`: This is a reference to the [$routeParams](https://docs.angularjs.org/api/ngRoute/service/$routeParams)
  service. In the example, we used `routeParams` to get a reference to a `name` from the URL.
* `ts`: This is a utility function which translates strings, as in `{{ts('Hello world')}}`.

## Development: Every form is an AngularJS directive

In the quick-start example, we registered a new route (`"server_route": "civicrm/pretty-page"`) -- this created a
standalone page with the sole purpose of displaying the `foobar` form.

However, there's no obligation to use the `foobar` form in a standalone fashion.  Think of `foobar` as a *re-usable
sub-form* or as a *directive*.  If you've created an AngluarJS application, then you can embed `foobar` with
two small steps:

1. In your application, declare a dependency on module `afformFoobar`. This is usually done in `ang/MYMODULE.ang.php`
   and/or `ang/MYMODULE.js`.
2. In your HTML template, use the directive `<div afform-foobar=""></div>`.

This technique is particularly useful if you want to provide extra data for the form author to use.  For example, your
application might pass in the current phase of the moon:

```html
<div afform-foobar="{phaseOfMoon: 'waxing'}"></div>
```

Now, in `afform/foobar/layout.html`, you can use the `phaseOfMoon`:

```html
Hello, {{routeParams.name}}. The moon is currently {{options.phaseOfMoon}}.
```


```html
Hello, {{routeParams.name ? routeParams.name : 'anonymous'}}. The moon is currently {{options.phaseOfMoon ? options.phaseOfMoon : 'on hiatus'}}.
```

## Known Issues

* The code is currently written as a proof-of-concept. There are several `FIXME`/`TODO` declarations in the code
  for checking pre-conditions, reporting errors, handling edge-cases, etc.
* Although afforms are can be used in AngularJS, they don't fully support tooling like `cv ang:html:list`
  and `hook_civicrm_alterAngular`. We'll need a core patch to allow that.
* We generally need to provide more services for managing/accessing data (e.g. `crm-api3`).
