angular-jquery-dialog-service
=============================

# Overview
This service allows you to easily work with jQuery UI dialogs from Angular.js. A working sample can be viewed on [Plunker][2].

# Methods
The service exposes three methods for controlling the dialogs. These methods are `open()`, `close()`, and `cancel()`.

## open(id, template, model, options)
The open method displays a dialog. The `id` argument is a unique name to identify this dialog when calling other methods on the service such as close and cancel.


The `template` argument specifies the id of the script block that contains the template to use for the dialog or a url to a template fragment on the web server. Here is an example script block template:

```
<script type="text/ng-template" id="dialogTemplate.html">

	<!-- Controller for Dialog -->
	<div ng-controller="dialogCtrl">

		<!-- The form -->
		First Name<br>
		<input type="text" ng-model="model.firstName" /><br>
		Last Name<br>
		<input type="text" ng-model="model.lastName" /><br>

		<!-- The buttons -->
		<button ng-click="cancelClick()">Cancel</button>
		<button ng-click="saveClick()">Save</button>
		<button ng-click="confirmClick()">Confirm</button>
	</div>
</script>
```

In the case above, `template` would be set to "dialogTemplate.html".

The `model` argument contains the data that should be passed to the dialog controller's scope. It is actually injected into the dialog controller's parent scope, but it is available as `$scope.model` within the dialog.

Finally, the `options` argument contains all of the [jQuery UI dialog options][1] that you would normally pass in the call to `dialog(options)`.

The open method returns a promise that is resolved when the user closes the dialog. If the dialog controller calls dialogService.close(model), the resolve function will be called. If `cancel()` is called or the user closed the dialog using the X or ESC, the reject function will be called.

Here is an example of an open call that opens a dialog whose template is in a script block assigned an id of "dialogTemplate.html":

```javascript
dialogService.open("myDialog","dialogTemplate.html",
		model: {
			firstName: "Jason",
			lastName: "Stadler",
			update: false
		},
		options: {
			autoOpen: false,
			modal: true
		}
	}).then(
			function(result) {
				console.log("Closed");
				console.log(result);
			},
			function(error) {
				console.log("Cancelled");
			}
	);
```

## close(id, model)

This method is typically called by the dialog controller to close the dialog. The `id` argument is the same string passed to the open method. The `model` is the data the dialog should pass back in the promise to the caller.

## cancel(id)

This method is typically called by the dialog controller to cancel the dialog. The `id` argument is the same string passed to the open method.


[1]: http://api.jquery.ui/dialog  "JQuery UI Dialog Documentation"
[2]: http://plnkr.co/edit/ADYEsplnYr8NHqASCDgS  "Plunker sample"

