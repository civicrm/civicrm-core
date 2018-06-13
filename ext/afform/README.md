# org.civicrm.afform

![Screenshot](/images/screenshot.png)

(*FIXME: In one or two paragraphs, describe what the extension does and why one would download it. *)

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

* PHP v5.4+
* CiviCRM (*FIXME: Version number*)

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

## Usage (Developers): Create a form

As an upstream publisher of a form, you can define the default, canonical
substance of the form by creating a folder named `afform/<MY-FORM>`. In
this example, we create a form named `foobar`:

```
$ cd /path/to/my/own/extension
$ mkdir -p afform/foobar
$ echo '{"route": "civicrm/foobar"}' > afform/foobar/meta.json
$ echo '<div>Hello {{param.name}}</div>' > afform/foobar/layout.html
$ cv flush
$ cv url civicrm/foobar?name=world
```

You can open the given URL in a web-browser.

## Usage (Developers): Programmatically read and write forms

Downstream, administrators may customize the form.

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
            "Hello {{param.name}}"
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

## Usage (Developers): Render a form

(* FIXME *)

## Known Issues

(* FIXME *)
