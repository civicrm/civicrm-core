var app = angular.module('dialogApp', ['dialogService']);

app.controller('buttonCtrl', ['$scope', '$log', 'dialogService',
	function($scope, $log, dialogService) {

		$scope.openFromScriptClick = function() {
			doDialog("template-from-script.html");
		};

		$scope.openFromUrlClick = function() {
			doDialog("template-from-url.html");
		};

		function doDialog(template) {

			// The data for the dialog
			var model = {
				firstName: "Jason",
				lastName: "Stadler"
			};

			// jQuery UI dialog options
			var options = {
				autoOpen: false,
				modal: true,
				close: function(event, ui) {
					$log.debug("Predefined close");
				}
			};

			// Open the dialog using template from script
			dialogService.open("myDialog", template, model, options).then(
				function(result) {
					$log.debug("Close");
					$log.debug(result);
				},
				function(error) {
					$log.debug("Cancelled");
				}
			);

		}
	}
]);

app.controller('dialogCtrl', ['$scope', 'dialogService',
	function($scope, dialogService) {

		// $scope.model contains the object passed to open in config.model

		$scope.saveClick = function() {
			dialogService.close("myDialog", $scope.model);
		};

		$scope.cancelClick = function() {
			dialogService.cancel("myDialog");
		};

		$scope.confirmClick = function() {
			// Open another dialog here
			dialogService.open("myConfirm", "confirmTemplate.html")
			.then(
				function(result) {
					console.log("Confirm");
				},
				function(error) {
					console.log("Cancel");
				}
			);
		};

	}
]);

app.controller('confirmCtrl', ['$scope', 'dialogService',
	function($scope, dialogService) {

		$scope.confirmClick = function() {
			dialogService.close("myConfirm");
		};

		$scope.cancelClick = function() {
			dialogService.cancel("myConfirm");
		};

	}
]);
