(function(angular, $, _) {

  angular.module('crmMailingAB', CRM.angRequires('crmMailingAB'));
  angular.module('crmMailingAB').config([
    '$routeProvider',
    function($routeProvider) {
      $routeProvider.when('/abtest', {
        templateUrl: '~/crmMailingAB/ListCtrl.html',
        controller: 'CrmMailingABListCtrl',
        resolve: {
          mailingABList: function($route, crmApi) {
            return crmApi('MailingAB', 'get', {rowCount: 0});
          },
          fields: function(crmMetadata) {
            return crmMetadata.getFields('MailingAB');
          }
        }
      });
      $routeProvider.when('/abtest/new', {
        template: '<p>' + ts('Initializing...') + '</p>',
        controller: 'CrmMailingABNewCtrl',
        resolve: {
          abtest: function($route, CrmMailingAB) {
            var abtest = new CrmMailingAB(null);
            return abtest.load().then(function() {
              return abtest.save();
            });
          }
        }
      });
      $routeProvider.when('/abtest/:id', {
        templateUrl: '~/crmMailingAB/EditCtrl/main.html',
        controller: 'CrmMailingABEditCtrl',
        resolve: {
          mailingFields: function(crmMetadata) {
            return crmMetadata.getFields('Mailing');
          },
          abtest: function($route, CrmMailingAB) {
            var abtest = new CrmMailingAB($route.current.params.id == 'new' ? null : $route.current.params.id);
            return abtest.load();
          }
        }
      });
    }
  ]);

})(angular, CRM.$, CRM._);
