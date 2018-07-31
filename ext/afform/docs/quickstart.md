# Quick Start: Creating the canonical definition of a basic form

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
