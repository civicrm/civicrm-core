# Full AngularJS: Integrating between Afform and vanilla AngularJS

Afform is a subset of AngularJS -- it emphasizes the use of *directives* as a way to *choose and arrange* the parts of
your form.  There is more to AngularJS -- such as client-side routing, controllers, services, etc.  What to do if you
need these?  Here are few tricks:

* You can create your own applications and pages with full AngularJS. (See also: [CiviCRM Developer Guide: AngularJS: Quick Start](https://docs.civicrm.org/dev/en/latest/framework/angular/quickstart/)).
  Then embed the afform (like `hello-world`) in your page with these steps:
    * Declare a dependency on module (`helloWorld`). This is usually done in `ang/MYMODULE.ang.php` and/or `ang/MYMODULE.js`.
    * In your HTML template, use the directive `<div hello-world=""></div>`.
    * If you want to provide extra data, services, or actions for the form author -- then pass them along.
* You can write your own directives with full AngularJS (e.g. `civix generate:angular-directive`). These directives become available for use in other afforms.
* If you start out distributing an `afform` and later find it too limiting, then you can change your mind and convert it to static code in full AngularJS.
  As long as you name it consistently (`angular.module('helloWorld').directive('helloWorld')`), downstream consumers can use the static version as a drop-in replacement.

> *(FIXME: But if you do convert to static, could you still permit downstream folks customize the HTML?  Let's
> re-assess after we've patched core to allow full participation in the lifecycle of HTML partials.)*
