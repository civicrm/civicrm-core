# Form CRUD: Updating forms via programmatic API

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

* The changes made through the API are only applied on this site.
* Once you make a change with the CRUD API, there will be two copies of the form:
    * `[myextension]/afform/helloworld/` is the default, canonical version.
    * `[civicrm.files]/afform/helloworld/` is the local, custom version.
* The `layout` field is stored as an Angular-style HTML document (`layout.html`), so you can edit it on disk like
  normal Angular code. However, when CRUD'ing the `layout` through the API, it is presented in JSON-style.
