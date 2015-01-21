(function (angular, $, _) {

  var partialUrl = function (relPath, module) {
    if (!module) {
      module = 'crmMailingAB';
    }
    return '~/' + module + '/' + relPath;
  };

  angular.module('crmMailingAB', ['ngRoute', 'ui.utils', 'ngSanitize', 'crmUi', 'crmAttachment', 'crmMailing', 'crmD3']);
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
      $routeProvider.when('/abtest/:id/report', {
        templateUrl: partialUrl('report.html'),
        controller: 'CrmMailingABReportCtrl',
        resolve: {
          abtest: function ($route, CrmMailingAB) {
            var abtest = new CrmMailingAB($route.current.params.id);
            return abtest.load();
          }
        }
      });
    }
  ]);

  angular.module('crmMailingAB').controller('CrmMailingABListCtrl', function ($scope, mailingABList, crmMailingABCriteria, crmMailingABStatus) {
    var ts = $scope.ts = CRM.ts(null);
    $scope.mailingABList = mailingABList.values;
    $scope.crmMailingABCriteria = crmMailingABCriteria;
    $scope.crmMailingABStatus = crmMailingABStatus;
  });

  angular.module('crmMailingAB').controller('CrmMailingABEditCtrl', function ($scope, abtest, crmMailingABCriteria, crmMailingMgr, crmMailingPreviewMgr, crmStatus, $q, $location) {
    $scope.abtest = abtest;
    var ts = $scope.ts = CRM.ts(null);
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
            crmMailingMgr.mergeInto(abtest.mailings.b, abtest.mailings.a, [
              'name',
              'groups',
              'mailings',
              'subject'
            ]);
            break;
          case 'From names':
            crmMailingMgr.mergeInto(abtest.mailings.b, abtest.mailings.a, [
              'name',
              'groups',
              'mailings',
              'from_name',
              'from_email'
            ]);
            break;
          case 'Two different emails':
            crmMailingMgr.mergeInto(abtest.mailings.b, abtest.mailings.a, [
              'name',
              'groups',
              'mailings',
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
      return $q.when(true);
    };

    // @return Promise
    $scope.save = function save() {
      $scope.sync();
      return crmStatus({start: ts('Saving...'), success: ts('Saved')}, abtest.save().then(updateUrl));
    };

    // @return Promise
    $scope.previewMailing = function previewMailing(mailingName, mode) {
      $scope.sync();
      return crmMailingPreviewMgr.preview(abtest.mailings[mailingName], mode);
    };

    // @return Promise
    $scope.sendTest = function sendTest(mailingName, recipient) {
      $scope.sync();
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
      $scope.sync();
      return crmStatus({start: ts('Saving...'), success: ''}, abtest.save())
        .then(function () {
          return crmStatus({start: ts('Submitting...'), success: ts('Submitted')}, abtest.submitTest());
          // Note: We're going to leave, so we don't care that submit() modifies several server-side records.
          // If we stayed on this page, then we'd care about updating and call: abtest.submitTest().then(...abtest.load()...)
        })
        .then(leave);
    };

    function leave() {
      $location.path('abtest');
      $location.replace();
    }

    function updateCriteriaName() {
      var criteria = crmMailingABCriteria.get($scope.abtest.ab.testing_criteria_id);
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

  angular.module('crmMailingAB').controller('CrmMailingABReportCtrl', function ($scope, abtest, crmApi, crmMailingPreviewMgr, dialogService) {
    var ts = $scope.ts = CRM.ts(null);

    $scope.abtest = abtest;

    $scope.stats = {};
    crmApi('Mailing', 'stats', {mailing_id: abtest.ab.mailing_id_a}).then(function(data){
      $scope.stats.a = data.values[abtest.ab.mailing_id_a];
    });
    crmApi('Mailing', 'stats', {mailing_id: abtest.ab.mailing_id_b}).then(function(data){
      $scope.stats.b = data.values[abtest.ab.mailing_id_b];
    });
    crmApi('Mailing', 'stats', {mailing_id: abtest.ab.mailing_id_c}).then(function(data){
      $scope.stats.c = data.values[abtest.ab.mailing_id_c];
    });

    $scope.previewMailing = function previewMailing(mailingName, mode) {
      return crmMailingPreviewMgr.preview(abtest.mailings[mailingName], mode);
    };
    $scope.selectWinner = function selectWinner(mailingName) {
      var model = {
        abtest: abtest,
        mailingName: mailingName
      };
      var options = {
        autoOpen: false,
        modal: true,
        title: ts('Select Winner (%1)', {
          1: mailingName.toUpperCase()
        })
      };
      return dialogService.open('selectWinnerDialog', partialUrl('selectWinner.html'), model, options);
    };
  });


  angular.module('crmMailingAB').controller('CrmMailingABWinnerDialogCtrl', function ($scope, $timeout, dialogService, crmMailingMgr, crmStatus) {
    var ts = $scope.ts = CRM.ts(null);
    var abtest = $scope.abtest = $scope.model.abtest;
    var mailingName = $scope.model.mailingName;

    var titles = {a: ts('Mailing A'), b: ts('Mailing B')};
    $scope.mailingTitle = titles[mailingName];

    function init() {
      // When using dialogService with a button bar, the major button actions
      // need to be registered with the dialog widget (and not embedded in
      // the body of the dialog).
      var buttons = {};
      buttons[ts('Select Winner')] = function () {
        crmMailingMgr.mergeInto(abtest.mailings.c, abtest.mailings[mailingName], [
          'name',
          'groups',
          'mailings',
          'scheduled_date'
        ]);
        crmStatus({start: ts('Saving...'), success: ''}, abtest.save())
          .then(function () {
            return crmStatus({start: ts('Submitting...'), success: ts('Submitted')},
              abtest.submitFinal().then(function(){
                return abtest.load();
              }));
          })
          .then(function(){
            dialogService.close('selectWinnerDialog', abtest);
          });
      };
      buttons[ts('Cancel')] = function () {
        dialogService.cancel('selectWinnerDialog');
      };
      dialogService.setButtons('selectWinnerDialog', buttons);
    }

    $timeout(init);
  });

})(angular, CRM.$, CRM._);
