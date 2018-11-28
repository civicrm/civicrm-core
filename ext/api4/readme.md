CiviCRM API Version 4
=====================

Welcome
-------

This is the latest version of the API (Application Programming Interface) for CiviCRM. If you are here because you're trying to install an extension that requires this, just install this and you're done!

If you are a developer, read on...

Using Api4
----------

Once installed you can navigate to **Support -> Developer -> Api4 Explorer** in the menu. This gives a live, interactive code generator in which you can build and test api calls:

![Screenshot](/images/ApiExplorer.png)

Output
------

The php binding returns an [arrayObject](http://php.net/manual/en/class.arrayobject.php). This gives immediate access to the results, plus allows returning additional metadata properties.


```php
$result = \Civi\Api4\Contact::get()->execute();

// you can loop through the results directly
foreach ($result as $contact) {}

// you can just grab the first one
$contact1 = $result->first();

// reindex results on-the-fly (replacement for sequential=1 in v3)
$result->indexBy('id');

// or fetch some metadata about the call
$entity = $result->entity; // "Contact"
```

We can do the something very similar in javascript thanks to js arrays also being objects:

```javascript
CRM.api4('Contact', 'get', params).done(function(result) {
  // you can loop through the results
  result.forEach(function(contact, n) {});

  // you can just grab the first one
  var contact1 = result[0];

  // or fetch some metadata about the call
  var entity = result.entity; // "Contact"
});
```

Notable changes from Version 3:
-------------------------------

* Instead of a single `$params` array, each api action has multiple methods to set various parameters.
* Output is an array with object properties rather than a nested array.
* Use the `Update` action to update an entity rather than `Create` with an id.
* Use `$result->indexBy('id');` rather than `sequential => 0`.
* `getSingle` is gone, use `$result->first()`.
* Custom fields are refered to by name rather than id. E.g. use `constituent_information.Most_Important_Issue` instead of `custom_4`.

Creating Apis for an Extension
------------------------------

If your extension creates one or more entities (sql tables with a DAO object) you can expose it to the api simply by creating a class (e.g. `\Civi\Api4\MyEntity`), and optionally declare permissions, set default values, and add custom actions.


Architecture
------------

* A series of **action classes** inherit from the base [`Action`](Civi/Api4/Action.php) class, e.g. [`Create`](Civi/Api4/Action/Create.php).
* Each entity may extend the generic action class to provide extra parameters or functionality.
* [`Update`](Civi/Api4/Action/Update.php), [`Replace`](Civi/Api4/Action/Replace.php) and [`Delete`](Civi/Api4/Action/Delete.php) actions extend the [`Get`](Civi/Api4/Action/Get.php) class allowing them to perform bulk operations.
* The `Action` class uses the magic [__call()](http://php.net/manual/en/language.oop5.overloading.php#object.call) method to `set`, `add` and `get` parameters.
* The base action `execute()` method calls the core [`civi_api_kernel`](https://github.com/civicrm/civicrm-core/blob/master/Civi/API/Kernel.php)
service `runRequest()` method. Action objects find their business access objects via [V3 API code](https://github.com/civicrm/civicrm-core/blob/master/api/v3/utils.php#L381).
* Each action object has a `_run()` method that accepts a decorated [arrayobject](http://php.net/manual/en/class.arrayobject.php) ([`Result`](Civi/API/Result.php)) as a parameter and is accessed by the action's `execute()` method.
* The **get** action class uses a [`Api4SelectQuery`](Civi/API/Api4SelectQuery.php) object
(based on the core
[SelectQuery](https://github.com/civicrm/civicrm-core/blob/master/Civi/API/SelectQuery.php) object.

Security
--------

Each `action` object has a `$checkPermissions` property. This always defaults to `TRUE`, and for calls from REST it cannot be disabled.

Tests
-----

Tests are located in the `tests` directory (surprise!)
To run the entire Api4 test suite go to the api4 extension directory and type `phpunit4` from the command line.
