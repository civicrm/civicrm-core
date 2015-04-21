(function (angular, $, _) {

  angular.module('crmMailingAB', ['ngRoute', 'ui.utils', 'crmUi', 'crmAttachment', 'crmMailing', 'crmD3']);
  angular.module('crmMailingAB').config([
    '$routeProvider',
    function ($routeProvider) {
      $routeProvider.when('/abtest', {
        templateUrl: '~/crmMailingAB/list.html',
        controller: 'CrmMailingABListCtrl',
        resolve: {
          mailingABList: function ($route, crmApi) {
            return crmApi('MailingAB', 'get', {rowCount: 0});
          },
          fields: function(crmMetadata){
            return crmMetadata.getFields('MailingAB');
          }
        }
      });
      $routeProvider.when('/abtest/new', {
        template: '<p>' + ts('Initializing...') + '</p>',
        controller: 'CrmMailingABNewCtrl',
        resolve: {
          abtest: function ($route, CrmMailingAB) {
            var abtest = new CrmMailingAB(null);
            return abtest.load().then(function(){
              return abtest.save();
            });
          }
        }
      });
      $routeProvider.when('/abtest/:id', {
        templateUrl: '~/crmMailingAB/main.html',
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

  angular.module('crmMailingAB').controller('CrmMailingABListCtrl', function($scope, mailingABList, crmMailingABCriteria, crmMailingABStatus, fields) {
    var ts = $scope.ts = CRM.ts(null);
    $scope.mailingABList = _.values(mailingABList.values);
    $scope.crmMailingABCriteria = crmMailingABCriteria;
    $scope.crmMailingABStatus = crmMailingABStatus;
    $scope.fields = fields;
    $scope.filter = {};
  });

  angular.module('crmMailingAB').controller('CrmMailingABNewCtrl', function ($scope, abtest, $location) {
    // Transition URL "/abtest/new/foo" => "/abtest/123/foo"
    var parts = $location.path().split('/'); // e.g. "/mailing/new" or "/mailing/123/wizard"
    parts[2] = abtest.id;
    $location.path(parts.join('/'));
    $location.replace();
  });

  angular.module('crmMailingAB').controller('CrmMailingABEditCtrl', function ($scope, abtest, crmMailingABCriteria, crmMailingMgr, crmMailingPreviewMgr, crmStatus, $q, $location, crmBlocker, $interval, $timeout, CrmAutosaveCtrl, dialogService) {
    $scope.abtest = abtest;
    var ts = $scope.ts = CRM.ts(null);
    var block = $scope.block = crmBlocker();
    $scope.crmUrl = CRM.url;
    var myAutosave = null;
    $scope.crmMailingABCriteria = crmMailingABCriteria;
    $scope.crmMailingConst = CRM.crmMailing;
    $scope.checkPerm = CRM.checkPerm;

    $scope.isSubmitted = function isSubmitted() {
      return _.size(abtest.mailings.a.jobs) > 0 || _.size(abtest.mailings.b.jobs) > 0;
    };

    $scope.sync = function sync() {
      abtest.mailings.a.name = ts('Test A (%1)', {1: abtest.ab.name});
      abtest.mailings.b.name = ts('Test B (%1)', {1: abtest.ab.name});
      abtest.mailings.c.name = ts('Final (%1)', {1: abtest.ab.name});

      if (abtest.ab.testing_criteria) {
        // TODO review fields exposed in UI and make sure the sync rules match
        switch (abtest.ab.testing_criteria) {
          case 'subject':
            crmMailingMgr.mergeInto(abtest.mailings.b, abtest.mailings.a, [
              'name',
              'recipients',
              'subject'
            ]);
            break;
          case 'from':
            crmMailingMgr.mergeInto(abtest.mailings.b, abtest.mailings.a, [
              'name',
              'recipients',
              'from_name',
              'from_email'
            ]);
            break;
          case 'full_email':
            crmMailingMgr.mergeInto(abtest.mailings.b, abtest.mailings.a, [
              'name',
              'recipients',
              'subject',
              'from_name',
              'from_email',
              'replyto_email',
              'override_verp', // keep override_verp and replyto_Email linked
              'body_html',
              'body_text'
            ]);
            break;
          default:
            throw "Unrecognized testing_criteria";
        }
      }
      crmMailingMgr.mergeInto(abtest.mailings.c, abtest.mailings.a, ['name']);
      return true;
    };

    // @return Promise
    $scope.save = function save() {
      return block(crmStatus({start: ts('Saving...'), success: ts('Saved')}, abtest.save()));
    };

    // @return Promise
    $scope.previewMailing = function previewMailing(mailingName, mode) {
      return crmMailingPreviewMgr.preview(abtest.mailings[mailingName], mode);
    };

    // @return Promise
    $scope.sendTest = function sendTest(mailingName, recipient) {
      return block(crmStatus({start: ts('Saving...'), success: ''}, abtest.save())
        .then(function () {
          crmMailingPreviewMgr.sendTest(abtest.mailings[mailingName], recipient);
        }));
    };

    // @return Promise
    $scope.delete = function () {
      return block(crmStatus({start: ts('Deleting...'), success: ts('Deleted')}, abtest.delete().then($scope.leave)));
    };

    // @return Promise
    $scope.submit = function submit() {
      if (block.check() || $scope.crmMailingAB.$invalid) {
        return;
      }
      return block(crmStatus({start: ts('Saving...'), success: ''}, abtest.save())
          .then(function() {
            return crmStatus({start: ts('Submitting...'), success: ts('Submitted')}, myAutosave.suspend(abtest.submitTest()));
            // Note: We're going to leave, so we don't care that submit() modifies several server-side records.
            // If we stayed on this page, then we'd care about updating and call: abtest.submitTest().then(...abtest.load()...)
          })
      );
    };

    $scope.leave = function leave() {
      $location.path('abtest');
      $location.replace();
    };

    $scope.selectWinner = function selectWinner(mailingName) {
      var model = {
        abtest: $scope.abtest,
        mailingName: mailingName
      };
      var options = CRM.utils.adjustDialogDefaults({
        autoOpen: false,
        height: 'auto',
        width: '40%',
        title: ts('Select Final Mailing (Test %1)', {
          1: mailingName.toUpperCase()
        })
      });
      return myAutosave.suspend(dialogService.open('selectWinnerDialog', '~/crmMailingAB/selectWinner.html', model, options));
    };

    // initialize
    var syncJob = $interval($scope.sync, 333);
    $scope.$on('$destroy', function(){
      $interval.cancel(syncJob);
    });

    myAutosave = new CrmAutosaveCtrl({
      save: $scope.save,
      saveIf: function(){
        return abtest.ab.status == 'Draft' && $scope.sync();
      },
      model: function(){
        return abtest.getAutosaveSignature();
      },
      form: function() {
        return $scope.crmMailingAB;
      }
    });
    $timeout(myAutosave.start);
    $scope.$on('$destroy', myAutosave.stop);
  });

  angular.module('crmMailingAB').controller('CrmMailingABReportCtrl', function ($scope, crmApi, crmMailingStats) {
    var ts = $scope.ts = CRM.ts(null);

    var CrmMailingABReportCnt = 1, activeMailings = null;
    $scope.getActiveMailings = function() {
      if ($scope.abtest.$CrmMailingABReportCnt != CrmMailingABReportCnt) {
        $scope.abtest.$CrmMailingABReportCnt = ++CrmMailingABReportCnt;
        activeMailings = [
          {name: 'a', title: ts('Mailing A'), mailing: $scope.abtest.mailings.a, attachments: $scope.abtest.attachments.a},
          {name: 'b', title: ts('Mailing B'), mailing: $scope.abtest.mailings.b, attachments: $scope.abtest.attachments.b}
        ];
        if ($scope.abtest.ab.status == 'Final') {
          activeMailings.push({name: 'c', title: ts('Final'), mailing: $scope.abtest.mailings.c, attachments: $scope.abtest.attachments.c});
        }
      }
      return activeMailings;
    };

    crmMailingStats.getStats({
      a: $scope.abtest.ab.mailing_id_a,
      b: $scope.abtest.ab.mailing_id_b,
      c: $scope.abtest.ab.mailing_id_c
    }).then(function(stats) {
      $scope.stats = stats;
    });

    $scope.statTypes = crmMailingStats.getStatTypes();
    $scope.statUrl = function statUrl(mailing, statType, view) {
      return crmMailingStats.getUrl(mailing, statType, view, 'abtest/' + $scope.abtest.ab.id);
    };

    $scope.checkPerm = CRM.checkPerm;
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
      var buttons = [
        {
          text: ts('Submit final mailing'),
          icons: {primary: 'ui-icon-check'},
          click: function () {
            crmMailingMgr.mergeInto(abtest.mailings.c, abtest.mailings[mailingName], [
              'name',
              'recipients',
              'scheduled_date'
            ]);
            crmStatus({start: ts('Saving...'), success: ''}, abtest.save())
              .then(function () {
                return crmStatus({start: ts('Submitting...'), success: ts('Submitted')},
                  abtest.submitFinal().then(function(r){
                    delete abtest.$CrmMailingABReportCnt;
                    return r;
                  }));
              })
              .then(function(){
                dialogService.close('selectWinnerDialog', abtest);
              });
          }
        },
        {
          text: ts('Cancel'),
          icons: {primary: 'ui-icon-close'},
          click: function () {
            dialogService.cancel('selectWinnerDialog');
          }
        }
      ];
      dialogService.setButtons('selectWinnerDialog', buttons);
    }

    $timeout(init);
  });

})(angular, CRM.$, CRM._);
