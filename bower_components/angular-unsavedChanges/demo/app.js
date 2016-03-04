angular
    .module('app', ['unsavedChanges', 'ngRoute'])
    .config(['$routeProvider', 'unsavedWarningsConfigProvider',
        function($routeProvider, unsavedWarningsConfigProvider) {

            $routeProvider
                .when('/page1', {
                    templateUrl: 'page1.html'
                })
                .when('/page2', {
                    templateUrl: 'page2.html'
                })
                .otherwise({
                    redirectTo: '/page1'
                });

            // We can turn on logging through this provider method

            // We uncomment out the below line in order to watch for angular-ui router events
            // rather than standard Angular router events. The default event is $locationChangeStart
            //unsavedWarningsConfigProvider.setRouteEventToWatchFor('$stateChangeStart');

            // We uncomment out the below line in order to change the navigate message
            //unsavedWarningsConfigProvider.setNavigateMessage('Leaving now will lose your unsaved work');

            // We uncomment out the below line in order to change the refresh message
            //unsavedWarningsConfigProvider.setReloadMessage('Refreshing now will lose your unsaved work');

            // We use the below line in order to override the default and tell unsavedWarning to NOT
            // use the awesome angular-translate library for some reason
            unsavedWarningsConfigProvider.useTranslateService = false;
        }
    ])
    .controller('demoCtrl', function($scope) {
        $scope.user = {};
        $scope.demoFormSubmit = function() {
            $scope.message = 'Form Saved';
            //$scope.user = {};
        }
        $scope.clearChanges = function() {
            $scope.user = {};
        }
    });
