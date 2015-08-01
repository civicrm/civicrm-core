This extension is an example of implementing a screen with CiviCRM+Angular. To view it:

 1. Install the extension
 2. Navigate to "http://mysite.com/civicrm/a/#/example"

There are a few key files involved:

 - [js/example.js](js/example.js) - An AngularJS module. This defines a route and a controller.
 - [partials/example.html](partials/example.html) - A view for the controller
 - [angularex.php](angularex.php) - Registers the module using *angularex_civicrm_angularModules()*.
