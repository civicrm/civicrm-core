# Quick Start: Creating the canonical definition of a basic form

As an extension author, you can define a form along with its default,
canonical content. Simply create a file  `ang/MYFORM.aff.html`. In
this example, we create a form named `helloWorld`:

```
$ cd /path/to/my/own/extension
$ mkdir ang
$ echo '<div>Hello {{routeParams.name}}</div>' > ang/helloWorld.aff.html
$ echo '{"server_route": "civicrm/hello-world"}' > ang/helloWorld.aff.json
$ cv flush
```

A few things to note:

* The `ang` folder is the typical location for AngularJS modules in CiviCRM extensions.
* We defined a route `civicrm/hello-world`. This appears in the same routing system used by CiviCRM forms. It also supports properties such as `title` (page title) and `is_public` (defaults to `false`).
* After creating a new form or file, we should flush the cache.
* If you're going to actively edit/revise the content of the file, then you should navigate
  to **Administer > System Settings > Debugging** and disable asset caching.
* The extension `*.aff.html` represents an AngularJS HTML document. It has access to all the general features of Angular HTML (discussed more later).
* In AngularJS, there is a distinction between a "module" (unit-of-code to be shared; usually appears as `camelCase`) and a "directive" (a custom
  HTML element; may appear as `camelCase` or as `kebab-case` depending on context). Afform supports a [tactical simplification](angular.md) in which one
  `*.aff.html` corresponds to one eponymous module and one eponymous directive.

Now that we've created a form, we'll want to determine its URL. As with most
CiviCRM forms, the URL depends on the CMS configuration. Here is an example
from a local Drupal 7 site:

```
$ cv url "civicrm/hello-world"
"http://dmaster.localhost/civicrm/hello-world"
$ cv url "civicrm/hello-world/#!/?name=world"
"http://dmaster.localhost/civicrm/hello-world/#/?name=world"
```

Open the URLs and see what you get.
