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

  angular.module('crmMailingAB').controller('CrmMailingABEditCtrl', function ($scope, abtest, crmMailingABCriteria, crmMailingMgr, crmMailingPreviewMgr, crmStatus, $q, $location) {
    $scope.abtest = abtest;
    var ts = $scope.ts = CRM.ts('CiviMail');
    $scope.crmMailingABCriteria = crmMailingABCriteria;
    $scope.crmMailingConst = CRM.crmMailing;
    $scope.partialUrl = partialUrl;

    $scope.isSubmitted = function isSubmitted() {
      return _.size(abtest.mailings.a.jobs) > 0 || _.size(abtest.mailings.b.jobs) > 0;
    };
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

    // @return Promise
    $scope.save = function save() {
      $scope.sync();
      return crmStatus({start: ts('Saving...'), success: ts('Saved')}, abtest.save().then(updateUrl));
    };

    // @return Promise
    $scope.previewMailing = function previewMailing(mailingName, mode) {
      return crmMailingPreviewMgr.preview(abtest.mailings[mailingName], mode);
    };

    // @return Promise
    $scope.sendTest = function sendTest(mailingName, recipient) {
      return crmStatus({start: ts('Saving...'), success: ''}, abtest.save().then(updateUrl))
        .then(function () {
          crmMailingPreviewMgr.sendTest(abtest.mailings[mailingName], recipient);
        });
    };

    // @return Promise
    $scope.delete = function () {
      return crmStatus({start: ts('Deleting...'), success: ts('Deleted')}, abtest.delete().then(leave));
    };

    // @return Promise
    $scope.submit = function submit() {
      return crmStatus({start: ts('Saving...'), success: ''}, abtest.save())
        .then(function () {
          return crmStatus({start: ts('Submitting...'), success: ts('Submitted')}, $q.all([
            crmMailingMgr.submit(abtest.mailings.a),
            crmMailingMgr.submit(abtest.mailings.b)
          ]));
        })
        .then(leave);
    };

    function leave() {
      console.log('leave from', $location.path(), ' to abtest');
      $location.path('abtest');
      $location.replace();
    }

    function updateCriteriaName() {
      var criteria = crmMailingABCriteria.get($scope.abtest.ab.testing_criteria_id)
      $scope.criteriaName = criteria ? criteria.name : null;
    }

    // Transition URL "/abtest/new" => "/abtest/123"
    function updateUrl() {
      var parts = $location.path().split('/'); // e.g. "/abtest/new" or "/abtest/123/wizard"
      if (parts[2] != $scope.abtest.ab.id) {
        parts[2] = $scope.abtest.ab.id;
        $location.path(parts.join('/'));
        $location.replace();
        // FIXME: Angular unnecessarily refreshes UI
        // WARNING: Changing the URL triggers a full reload. Any pending AJAX operations
        // could be inconsistently applied. Run updateUrl() after other changes complete.
      }
    }

    // initialize
    updateCriteriaName();
    $scope.sync();
    $scope.$watch('abtest.ab.testing_criteria_id', updateCriteriaName);
  });

})(angular, CRM.$, CRM._);
