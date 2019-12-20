# Writing Forms: Afform as basic AngularJS templates

In AngularJS, the primary language for composing a screen is HTML. You can do interesting things in Angular
HTML, such as displaying variables and applying directives.

One key concept is *scope* -- the *scope* determines the list of variables which you can access.  By default, `afform`
creates a scope with these variables:

* `routeParams`: This is a reference to the [$routeParams](https://docs.angularjs.org/api/ngRoute/service/$routeParams)
  service. In the example, we used `routeParams` to get a reference to a `name` from the URL.
* `meta`: Object which for now contains just the form name but could potentially have other metadata if needed.
* `ts`: This is a utility function which translates strings, as in `{{ts('Hello world')}}`.

Additionally, AngularJS allows *directives* -- these are extensions to HTML (custom tags and attributes) which create behavior. For example:

* `ng-if` will conditionally create or destroy elements in the page.
* `ng-repeat` will loop through data.
* `ng-style` and `ng-class` will conditionally apply styling.

A full explanation of these features is out-of-scope for this document, but the key point is that you can use standard
AngularJS markup.

## Example: Contact record

Let's say we want `civicrm/hello-world` to become a basic "View Contact" page. A user
would request a URL like:

```
http://dmaster.localhost/civicrm/hello-world/#/?cid=123
```

How do we use the `cid` to get information about the contact?  Update `helloWorld.aff.html` to fetch data with
`Contact.get` API and call the [af-api3](https://github.com/totten/afform/blob/master/ang/afCore/Api3Ctrl.md) utility:

```html
<div ng-if="!routeParams.cid">
  {{ts('Please provide the "cid"')}}
</div>
<div ng-if="routeParams.cid"
  af-api3="['Contact', 'get', {id: routeParams.cid}]"
  af-api3-ctrl="apiData">

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

This example is useful pedagogically and may be useful in a crunch -- but
for typical user-managed forms, it would be better to use more high-level
directives.  You can create such directives by [embedding forms](embed.md)
or creating [conventional AngularJS directives](angular.md).
