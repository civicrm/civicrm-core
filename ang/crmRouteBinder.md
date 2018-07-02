# crmRouteBinder

Live-update the URL to stay in sync with controller data.

## Example

```js
angular.module('sandbox').config(function($routeProvider) {
  $routeProvider.when('/example-route', {
    reloadOnSearch: false,
    template: '<input ng-model="filters.foo" />',
    controller: function($scope) {
      $scope.$bindToRoute({
        param: 'f',
        expr: 'filters',
        default: {foo: 'default-value'}
      });
    }
  });
});
```

Things to try out:

 * Navigate to `#/example-route`. Observe that the URL automatically
   updates to `#/example-route?f={"foo":"default-value"}`.
 * Edit the content in the `<input>` field. Observe that the URL changes.
 * Initiate a change in the browser -- by editing the URL bar or pressing
   the "Back" button.  The page should refresh.

## Functions

**`$scope.$bindToRoute(options)`**
*The `options` object should contain keys:*

 * `expr` (string): The name of a scoped variable to sync.
 * `param` (string): The name of a query-parameter to sync. (If the `param` is included in the URL, it will initialize the expr.)
 * `format` (string): The type of data to put in `param`. May be one of:
    * `json` (default): The `param` is JSON, and the `expr` is a decoded object.
    * `raw`: The `param` is string, and the `expr` is a string.
    * `int`: the `param` is an integer-like string, and the expr is an integer.
    * `bool`: The `param` is '0'/'1', and the `expr` is false/true.
 * `default` (object): The default data. (If the `param` is not included in the URL, it will initialize the expr.)
 * `deep` (boolean): By default the json format will be watched using a shallow comparison. For nested objects and arrays enable this option.

## Suggested Usage

`$bindToRoute()` was written for a complicated routing scenario with
multiple parameters, e.g.  `caseFilters:Object`, `caseId:Int`, `tab:String`,
`activityFilters:Object`, `activityId:Int`.  If you're use-case is one or
two scalar values, then stick to vanilla `ngRoute`. This is only for
complicated scenarios.

If you are using `$bindToRoute()`, should you split up parameters -- with
some using `ngRoute` and some using `$bindToRoute()`?  I'd pick one style
and stick to it.  You're in a complex use-case where `$bindToRoute()` makes
sense, then you already need to put thought into the different
flows/input-combinations.  Having two technical styles will increase the
mental load.

A goal of `bindToRoute()` is to accept inputs interchangably from the URL or
HTML fields.  Using `ngRoute`'s `resolve:` option only addresses the URL
half.  If you want one piece of code handling all inputs the same way, you
should avoid `resolve:` and instead write a controller focused on
orchestrating I/O:

```js
angular.module('sandbox').config(function($routeProvider) {
  $routeProvider.when('/example-route', {
    reloadOnSearch: false,
    template:
      '<div filter-toolbar-a="filterSetA" />'
      + '<div filter-toolbar-b="filterSetB" />'
      + '<div filter-toolbar-c="filterSetC" />'
      + '<div data-set-a="dataSetA" />'
      + '<div data-set-b="dataSetB" />'
      + '<div data-set-c="dataSetC" />',
    controller: function($scope) {
      $scope.$bindToRoute({expr:'filterSetA', param:'a', default:{}});
      $scope.$watchCollection('filterSetA', function(){
        crmApi(...).then(function(...){
          $scope.dataSetA = ...;
        });
      });

      $scope.$bindToRoute({expr:'filterSetB', param:'b', default:{}});
      $scope.$watchCollection('filterSetB', function(){
        crmApi(...).then(function(...){
          $scope.dataSetB = ...;
        });
      });

      $scope.$bindToRoute({expr:'filterSetC', param:'c', default:{}});
      $scope.$watchCollection('filterSetC', function(){
        crmApi(...).then(function(...){
          $scope.dataSetC = ...;
        });
      });
    }
  });
});
```

(This example is a little more symmetric than a real one -- because the A,
B, and C datasets look independent.  In practice, their loading may be
intermingled.)
