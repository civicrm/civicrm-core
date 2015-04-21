(function($, angular){
angular.module('dialogService', []).service('dialogService',
	['$rootScope', '$q', '$compile', '$templateCache', '$http',
	function($rootScope, $q, $compile, $templateCache, $http) {

			var _this = this;
			_this.dialogs = {};

			this.open = function(id, template, model, options) {

				// Check our required arguments
				if (!angular.isDefined(id)) {
					throw "dialogService requires id in call to open";
				}

				if (!angular.isDefined(template)) {
					throw "dialogService requires template in call to open";
				}

				// Set the defaults for model
				if (!angular.isDefined(model)) {
					model = null;
				}

				// Copy options so the change ot close isn't propogated back.
				// Extend is used instead of copy because window references are
				// often used in the options for positioning and they can't be deep
				// copied.
				var dialogOptions = {};
				if (angular.isDefined(options)) {
					angular.extend(dialogOptions, options);
				}

				// Initialize our dialog structure
				var dialog = { scope: null, ref: null, deferred: $q.defer() };

				// Get the template from teh cache or url
				loadTemplate(template).then(
					function(dialogTemplate) {

						// Create a new scope, inherited from the parent.
						dialog.scope = $rootScope.$new();
						dialog.scope.model = model;
						var dialogLinker = $compile(dialogTemplate);
						dialog.ref = $(dialogLinker(dialog.scope));

						// Handle the case where the user provides a custom close and also
						// the case where the user clicks the X or ESC and doesn't call
						// close or cancel.
						var customCloseFn = dialogOptions.close;
						dialogOptions.close = function(event, ui) {
							if (customCloseFn) {
								customCloseFn(event, ui);
							}
							cleanup(id);
						};

						// Initialize the dialog and open it
						dialog.ref.dialog(dialogOptions);
						dialog.ref.dialog("open");

						// Cache the dialog
						_this.dialogs[id] = dialog;

					}, function(error) {
						throw error;
					}
				);
				
				// Return our cached promise to complete later
				return dialog.deferred.promise;
			};

			this.close = function(id, result) {

				// Get the dialog and throw exception if not found
				var dialog = getExistingDialog(id);

				// Notify those waiting for the result
				// This occurs first because the close calls the close handler on the
				// dialog whose default action is to cancel.
				dialog.deferred.resolve(result);

				// Close the dialog (must be last)
				dialog.ref.dialog("close");
			};

			this.cancel = function(id) {

				// Get the dialog and throw exception if not found
				var dialog = getExistingDialog(id);

				// Notify those waiting for the result
				// This occurs first because the cancel calls the close handler on the
				// dialog whose default action is to cancel.
				dialog.deferred.reject();

				// Cancel and close the dialog (must be last)
				dialog.ref.dialog("close");
			};

			this.setButtons = function(id, buttons) {
				var dialog = getExistingDialog(id);
				dialog.ref.dialog("option", 'buttons', buttons);
			};

			function cleanup (id) {

				// Get the dialog and throw exception if not found
				var dialog = getExistingDialog(id);

				// This is only called from the close handler of the dialog
				// in case the x or escape are used to cancel the dialog. Don't
				// call this from close, cancel, or externally.
				dialog.deferred.reject();
				dialog.scope.$destroy();

				// Remove the object from the DOM
				dialog.ref.remove();

				// Delete the dialog from the cache
				delete _this.dialogs[id];
			};

			function getExistingDialog(id) {

				// Get the dialog from the cache
				var dialog = _this.dialogs[id];
				// Throw an exception if the dialog is not found
				if (!angular.isDefined(dialog)) {
					throw "DialogService does not have a reference to dialog id " + id;
				}
				return dialog;
			};

			// Loads the template from cache or requests and adds it to the cache
			function loadTemplate(template) {

				var deferred = $q.defer();
				var html = $templateCache.get(template);

				if (angular.isDefined(html)) {
					// The template was cached or a script so return it
					html = html.trim();
					deferred.resolve(html);
				} else {
					// Retrieve the template if it is a URL
					return $http.get(template, { cache : $templateCache }).then(
						function(response) {
							var html = response.data;
							if(!html || !html.length) {
								// Nothing was found so reject the promise
								return $q.reject("Template " + template + " was not found");
							}
							html = html.trim();
							// Add it to the template cache using the url as the key
							$templateCache.put(template, html);
							return html;
						}, function() {
							return $q.reject("Template " + template + " was not found");
			        	}
			        );
				}
			    return deferred.promise;
			}
		}
]);
})(jQuery, angular);
