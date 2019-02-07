# Form CRUD: Updating forms via programmatic API

Now that we've defined a baseline form, it's possible for administrators and
GUI applications to inspect the form using the API:

```
$ cv api4 afform.get +w name=helloworld
{
    "0": {
        "name": "helloworld",
        "requires": [
            "afformCore"
        ],
        "title": "",
        "description": "",
        "is_public": false,
        "server_route": "civicrm/hello-world",
        "layout": {
            "#tag": "div",
            "#children": [
                "Hello {{routeParams.name}}"
            ]
        },
    }
}
```

Additionally, you can also update the forms:

```
$ cv api4 afform.update +w name=helloworld +v title="The Foo Bar Screen"
{
    "0": {
        "name": "helloworld",
        "title": "The Foo Bar Screen"
    }
}
```

A few important things to note about this:

* The changes made through the API are only applied on this site.
* Once you make a change with the CRUD API, there will be two copies of the form:
    * `[myextension]/afform/helloworld/` is the default, canonical version.
    * `[civicrm.files]/afform/helloworld/` is the local, custom version.
* The `layout` field is stored as an Angular-style HTML document (`layout.html`), so you can edit it on disk like
  normal Angular code. However, when CRUD'ing the `layout` through the API, it is presented in JSON-style.
