(function (angular, $, _) {

  var partialUrl = function (relPath, module) {
    if (!module) {
      module = 'crmMailingAB';
    }
    return CRM.resourceUrls['civicrm'] + '/partials/' + module + '/' + relPath;
  };

  angular.module('crmMailingAB', ['ngRoute', 'ui.utils', 'ngSanitize', 'crmUi', 'crmAttachment', 'crmMailing']);
  angular.module('crmMailingAB').config([
    '$routeProvider',
    function ($routeProvider) {
      $routeProvider.when('/abtest', {
        templateUrl: partialUrl('list.html'),
        controller: 'CrmMailingABListCtrl',
        resolve: {
          mailingABList: function ($route, crmApi) {
            return crmApi('MailingAB', 'get', {rowCount: 0});
          }
        }
      });
      $routeProvider.when('/abtest/:id', {
        templateUrl: partialUrl('edit.html'),
        controller: 'CrmMailingABEditCtrl',
        resolve: {
          abtest: function ($route, CrmMailingAB) {
            var abtest = new CrmMailingAB($route.current.params.id == 'new' ? null : $route.current.params.id);
            return abtest.load();
          }
        }
      });
    }
  ]);

  angular.module('crmMailingAB').controller('CrmMailingABListCtrl', function ($scope, mailingABList, crmMailingABCriteria) {
    $scope.mailingABList = mailingABList.values;
    $scope.testing_criteria = crmMailingABCriteria.getAll();
  });

  angular.module('crmMailingAB').controller('CrmMailingABEditCtrl', function ($scope, abtest, crmMailingABCriteria, crmMailingMgr, crmMailingPreviewMgr, crmStatus) {
    window.ab = abtest;
    $scope.abtest = abtest;
    var ts = $scope.ts = CRM.ts('CiviMail');
    $scope.crmMailingABCriteria = crmMailingABCriteria;
    $scope.crmMailingConst = CRM.crmMailing;
    $scope.partialUrl = partialUrl;

    $scope.sync = function sync() {
      abtest.mailings.a.name = ts('Test A (%1)', {1: abtest.ab.name});
      abtest.mailings.b.name = ts('Test B (%1)', {1: abtest.ab.name});
      abtest.mailings.c.name = ts('Winner (%1)', {1: abtest.ab.name});

      var criteria = crmMailingABCriteria.get(abtest.ab.testing_criteria_id);
      if (criteria) {
        // TODO review fields exposed in UI and make sure the sync rules match
        switch (criteria.name) {
          case 'Subject lines':
            crmMailingMgr.mergeInto(abtest.mailings.b, abtest.mailings.a, ['name', 'subject']);
            break;
          case 'From names':
            crmMailingMgr.mergeInto(abtest.mailings.b, abtest.mailings.a, ['name', 'from_name', 'from_email']);
            break;
          case 'Two different emails':
            crmMailingMgr.mergeInto(abtest.mailings.b, abtest.mailings.a, [
              'name',
              'subject',
              'from_name',
              'from_email',
              'body_html',
              'body_text'
            ]);
            break;
          default:
            throw "Unrecognized testing_criteria";
        }
      }
      crmMailingMgr.mergeInto(abtest.mailings.c, abtest.mailings.a, ['name']);
    };
    $scope.save = function save() {
      $scope.sync();
      return crmStatus({start: ts('Saving...'), success: ts('Saved')}, abtest.save());
    };
    // @return Promise
    $scope.previewMailing = function previewMailing(mailingName, mode) {
      return crmMailingPreviewMgr.preview(abtest.mailings[mailingName], mode);
    };

    // @return Promise
    $scope.sendTest = function sendTest(mailingName, recipient) {
      return crmStatus({start: ts('Saving...'), success: ''}, abtest.save())
        .then(function () {
          crmMailingPreviewMgr.sendTest(abtest.mailings[mailingName], recipient);
        });
    };
    $scope.delete = function () {
      throw "Not implemented: EditCtrl.delete"
    };
    $scope.submit = function () {
      throw "Not implemented: EditCtrl.submit"
    };

    function updateCriteriaName() {
      var criteria = crmMailingABCriteria.get($scope.abtest.ab.testing_criteria_id)
      $scope.criteriaName = criteria ? criteria.name : null;
    }

    // initialize
    updateCriteriaName();
    $scope.sync();
    $scope.$watch('abtest.ab.testing_criteria_id', updateCriteriaName);
  });

})(angular, CRM.$, CRM._);
