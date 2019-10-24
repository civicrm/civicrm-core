(function (angular, $, _) {

  angular.module('crmMailing', CRM.angRequires('crmMailing'));

  angular.module('crmMailing').config([
    '$routeProvider',
    function ($routeProvider) {
      $routeProvider.when('/mailing', {
        template: '<div></div>',
        controller: 'ListMailingsCtrl'
      });

      if (!CRM || !CRM.crmMailing) {
        return;
      }

      $routeProvider.when('/mailing/new', {
        template: '<p>' + ts('Initializing...') + '</p>',
        controller: 'CreateMailingCtrl',
        resolve: {
          selectedMail: function(crmMailingMgr) {
            var m = crmMailingMgr.create({
              template_type: CRM.crmMailing.templateTypes[0].name
            });
            return crmMailingMgr.save(m);
          }
        }
      });

      $routeProvider.when('/mailing/new/:templateType', {
        template: '<p>' + ts('Initializing...') + '</p>',
        controller: 'CreateMailingCtrl',
        resolve: {
          selectedMail: function($route, crmMailingMgr) {
            var m = crmMailingMgr.create({
              template_type: $route.current.params.templateType
            });
            return crmMailingMgr.save(m);
          }
        }
      });

      $routeProvider.when('/mailing/:id', {
        templateUrl: '~/crmMailing/EditMailingCtrl/base.html',
        controller: 'EditMailingCtrl',
        resolve: {
          selectedMail: function($route, crmMailingMgr) {
            return crmMailingMgr.get($route.current.params.id);
          },
          attachments: function($route, CrmAttachments) {
            var attachments = new CrmAttachments(function () {
              return {entity_table: 'civicrm_mailing', entity_id: $route.current.params.id};
            });
            return attachments.load();
          }
        }
      });
    }
  ]);

})(angular, CRM.$, CRM._);
