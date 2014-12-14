(function (angular, $, _) {

  var partialUrl = function (relPath, module) {
    if (!module) {
      module = 'crmMailingAB2';
    }
    return CRM.resourceUrls['civicrm'] + '/partials/' + module + '/' + relPath;
  };

  angular.module('crmMailingAB2', ['ngRoute', 'ui.utils', 'ngSanitize', 'crmUi', 'crmMailing2']);
  angular.module('crmMailingAB2').config([
    '$routeProvider',
    function ($routeProvider) {
      $routeProvider.when('/abtest2', {
        templateUrl: partialUrl('list.html'),
        controller: 'CrmMailingAB2ListCtrl',
        resolve: {
          mailingABList: function ($route, crmApi) {
            return crmApi('MailingAB', 'get', {rowCount: 0});
          }
        }
      });
      $routeProvider.when('/abtest2/:id', {
        templateUrl: partialUrl('edit.html'),
        controller: 'CrmMailingAB2EditCtrl',
        resolve: {
          abtest: function ($route, CrmMailingAB) {
            var abtest = new CrmMailingAB($route.current.params.id == 'new' ? null : $route.current.params.id);
            return abtest.load();
          }
        }
      });
    }
  ]);

  angular.module('crmMailingAB2').controller('CrmMailingAB2ListCtrl', function ($scope, mailingABList, crmMailingABCriteria) {
    $scope.mailingABList = mailingABList.values;
    $scope.testing_criteria = crmMailingABCriteria.getAll();
  })


  angular.module('crmMailingAB2').controller('CrmMailingAB2EditCtrl', function ($scope, abtest, crmMailingABCriteria, crmMailingMgr) {
    $scope.abtest = abtest;
    $scope.ts = CRM.ts('CiviMail');
    $scope.crmMailingABCriteria = crmMailingABCriteria;
    $scope.crmMailingConst = CRM.crmMailing;;
    $scope.partialUrl = partialUrl;

    $scope.sync = function sync() {
      abtest.mailings.a.name = ts('Test A (%1)', {1: abtest.ab.name});
      abtest.mailings.b.name = ts('Test B (%1)', {1: abtest.ab.name});
      abtest.mailings.c.name = ts('Winner (%1)', {1: abtest.ab.name});

      var criteria = crmMailingABCriteria.get(abtest.ab.testing_criteria_id);
      if (criteria) {
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
      return abtest.save();
    };
    $scope.delete = function () {
      throw "Not implemented: EditCtrl.delete"
    };
    $scope.submit = function () {
      throw "Not implemented: EditCtrl.submit"
    };

    function updateCriteriaName() {
      $scope.criteriaName = crmMailingABCriteria.get($scope.abtest.ab.testing_criteria_id).name;
    }

    // initialize
    updateCriteriaName();
    $scope.sync();
    $scope.$watch('abtest.ab.testing_criteria_id', updateCriteriaName);
  });

})(angular, CRM.$, CRM._);
