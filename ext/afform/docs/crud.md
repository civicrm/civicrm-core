# Form CRUD: Updating forms via programmatic API

Now that we've defined a baseline form, it's possible for administrators and
GUI applications to inspect the form using the API:

```
$ cv api4 afform.get +w name=helloWorld
{
    "0": {
        "name": "helloWorld",
        "requires": [
            "afCore"
        ],
        "title": "",
        "description": "",
        "is_dashlet": false,
        "is_public": false,
        "is_token": false,
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
$ cv api4 afform.update +w name=helloWorld +v title="The Foo Bar Screen"
{
    "0": {
        "name": "helloWorld",
        "title": "The Foo Bar Screen"
    }
}
```

A few important things to note about this:

* The changes made through the API are only applied on this site.
* Once you make a change with the CRUD API, there will be two copies of the form:
    * `[myextension]/ang/helloWorld.aff.html` is the default, canonical version.
    * `[civicrm.files]/ang/helloWorld.aff.html` is the local, custom version.
* The `layout` field is stored as an Angular-style HTML document (`helloWorld.aff.html`), so you can edit it on disk like
  normal Angular code. However, when CRUD'ing the `layout` through the API, it is presented in JSON-style.

To undo the change, you can use the `revert` API.  This will remove any local overrides so that the canonical content
(`[myextension]/ang/helloWorld.aff.html`) is activated.

```
$ cv api4 afform.revert +w name=helloWorld
```
