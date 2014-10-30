(function (angular, $, _) {
  var partialUrl = function (relPath) {
    return CRM.resourceUrls['civicrm'] + '/partials/crmMailing2/' + relPath;
  };

  var crmMailing2 = angular.module('crmMailing2', ['ngRoute', 'ui.utils', 'crmUi']); // TODO ngSanitize, unsavedChanges

  /**
   * Initialize a new mailing
   * TODO Move to separate file or service
   */
  var createMailing = function () {
    return {
      visibility: "Public Pages",
      url_tracking: "1",
      dedupe_email: "1",
      forward_replies: "0",
      auto_responder: "0",
      open_tracking: "1"
    };
  };

  crmMailing2.config(['$routeProvider',
    function ($routeProvider) {
      $routeProvider.when('/mailing2', {
        template: '<div></div>',
        controller: 'ListMailingsCtrl'
      });

      $routeProvider.when('/mailing2/:id', {
        templateUrl: partialUrl('edit.html'),
        controller: 'EditMailingCtrl',
        resolve: {
          selectedMail: function ($route, crmApi) {
            return ($route.current.params.id == 'new')
                    ? createMailing()
                    : crmApi('Mailing', 'getsingle', {id: $route.current.params.id});
          }
        }
      });
    }
  ]);

  crmMailing2.controller('ListMailingsCtrl', function ($scope) {
    // We haven't implemented this in Angular, but some users may get clever
    // about typing URLs, so we'll provide a redirect.
    window.location = CRM.url('civicrm/mailing/browse/unscheduled', {
      reset: 1,
      scheduled: 'false'
    });
  });

  crmMailing2.controller('EditMailingCtrl', function ($scope, selectedMail) {
    $scope.partialUrl = partialUrl;
    $scope.ts = CRM.ts('CiviMail');
    $scope.deleteMe = 'deleteMe';
  });

})(angular, CRM.$, CRM._);
