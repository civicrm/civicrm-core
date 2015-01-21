(function (angular, $, _) {
  var partialUrl = function partialUrl(relPath) {
    return '~/crmMailing/' + relPath;
  };

  angular.module('crmMailing', [
    'crmUtil', 'crmAttachment', 'ngRoute', 'ui.utils', 'crmUi', 'dialogService'
  ]); // TODO ngSanitize, unsavedChanges

  // Time to wait before triggering AJAX update to recipients list
  var RECIPIENTS_DEBOUNCE_MS = 100;
  var RECIPIENTS_PREVIEW_LIMIT = 10000;

  angular.module('crmMailing').config([
    '$routeProvider',
    function ($routeProvider) {
      $routeProvider.when('/mailing', {
        template: '<div></div>',
        controller: 'ListMailingsCtrl'
      });
      $routeProvider.when('/mailing/:id', {
        templateUrl: partialUrl('edit.html'),
        controller: 'EditMailingCtrl',
        resolve: {
          selectedMail: function selectedMail($route, crmMailingMgr) {
            return crmMailingMgr.getOrCreate($route.current.params.id);
          }
        }
      });
      $routeProvider.when('/mailing/:id/unified', {
        templateUrl: partialUrl('edit-unified.html'),
        controller: 'EditMailingCtrl',
        resolve: {
          selectedMail: function selectedMail($route, crmMailingMgr) {
            return crmMailingMgr.getOrCreate($route.current.params.id);
          }
        }
      });
      $routeProvider.when('/mailing/:id/unified2', {
        templateUrl: partialUrl('edit-unified2.html'),
        controller: 'EditMailingCtrl',
        resolve: {
          selectedMail: function selectedMail($route, crmMailingMgr) {
            return crmMailingMgr.getOrCreate($route.current.params.id);
          }
        }
      });
      $routeProvider.when('/mailing/:id/wizard', {
        templateUrl: partialUrl('edit-wizard.html'),
        controller: 'EditMailingCtrl',
        resolve: {
          selectedMail: function selectedMail($route, crmMailingMgr) {
            return crmMailingMgr.getOrCreate($route.current.params.id);
          }
        }
      });
    }
  ]);

  angular.module('crmMailing').controller('ListMailingsCtrl', ['crmLegacy', 'crmNavigator', function ListMailingsCtrl(crmLegacy, crmNavigator) {
    // We haven't implemented this in Angular, but some users may get clever
    // about typing URLs, so we'll provide a redirect.
    var new_url = crmLegacy.url('civicrm/mailing/browse/unscheduled', {reset: 1, scheduled: 'false'});
    crmNavigator.redirect(new_url);
  }]);

  angular.module('crmMailing').controller('EditMailingCtrl', function EditMailingCtrl($scope, selectedMail, $location, crmMailingMgr, crmStatus, CrmAttachments, crmMailingPreviewMgr) {
    $scope.mailing = selectedMail;
    $scope.attachments = new CrmAttachments(function () {
      return {entity_table: 'civicrm_mailing', entity_id: $scope.mailing.id};
    });
    $scope.attachments.load();
    $scope.crmMailingConst = CRM.crmMailing;

    $scope.partialUrl = partialUrl;
    var ts = $scope.ts = CRM.ts(null);

    $scope.isSubmitted = function isSubmitted() {
      return _.size($scope.mailing.jobs) > 0;
    };

    // @return Promise
    $scope.previewMailing = function previewMailing(mailing, mode) {
      return crmMailingPreviewMgr.preview(mailing, mode);
    };

    // @return Promise
    $scope.sendTest = function sendTest(mailing, attachments, recipient) {
      var savePromise = crmMailingMgr.save(mailing)
        .then(function () {
          return attachments.save();
        })
        .then(updateUrl);
      return crmStatus({start: ts('Saving...'), success: ''}, savePromise)
        .then(function () {
          crmMailingPreviewMgr.sendTest(mailing, recipient);
        });
    };

    // @return Promise
    $scope.submit = function submit() {
      var promise = crmMailingMgr.save($scope.mailing)
          .then(function () {
            // pre-condition: the mailing exists *before* saving attachments to it
            return $scope.attachments.save();
          })
          .then(function () {
            return crmMailingMgr.submit($scope.mailing);
          })
          .then(function () {
            leave('scheduled');
          })
        ;
      return crmStatus({start: ts('Submitting...'), success: ts('Submitted')}, promise);
    };

    // @return Promise
    $scope.save = function save() {
      return crmStatus(null,
        crmMailingMgr
          .save($scope.mailing)
          .then(function () {
            // pre-condition: the mailing exists *before* saving attachments to it
            return $scope.attachments.save();
          })
          .then(updateUrl)
      );
    };

    // @return Promise
    $scope.delete = function cancel() {
      return crmStatus({start: ts('Deleting...'), success: ts('Deleted')},
        crmMailingMgr.delete($scope.mailing)
          .then(function () {
            leave('unscheduled');
          })
      );
    };

    // @param string listingScreen 'archive', 'scheduled', 'unscheduled'
    function leave(listingScreen) {
      switch (listingScreen) {
        case 'archive':
          window.location = CRM.url('civicrm/mailing/browse/archived', {
            reset: 1
          });
          break;
        case 'scheduled':
          window.location = CRM.url('civicrm/mailing/browse/scheduled', {
            reset: 1,
            scheduled: 'true'
          });
          break;
        case 'unscheduled':
          /* falls through */
        default:
          window.location = CRM.url('civicrm/mailing/browse/unscheduled', {
            reset: 1,
            scheduled: 'false'
          });
      }
    }

    // Transition URL "/mailing/new" => "/mailing/123"
    function updateUrl() {
      var parts = $location.path().split('/'); // e.g. "/mailing/new" or "/mailing/123/wizard"
      if (parts[2] != $scope.mailing.id) {
        parts[2] = $scope.mailing.id;
        $location.path(parts.join('/'));
        $location.replace();
        // FIXME: Angular unnecessarily refreshes UI
        // WARNING: Changing the URL triggers a full reload. Any pending AJAX operations
        // could be inconsistently applied. Run updateUrl() after other changes complete.
      }
    }
  });

  // Controller for the edit-recipients fields (
  // WISHLIST: Move most of this to a (cache-enabled) service
  // Scope members:
  //  - [input] mailing: object
  //  - [output] recipients: array of recipient records
  angular.module('crmMailing').controller('EditRecipCtrl', function EditRecipCtrl($scope, dialogService, crmApi, crmMailingMgr) {
    var ts = $scope.ts = CRM.ts(null);
    $scope.recipients = null;
    $scope.getRecipientsEstimate = function () {
      var ts = $scope.ts;
      if ($scope.recipients === null) {
        return ts('(Estimating)');
      }
      if ($scope.recipients.length === 0) {
        return ts('No recipients');
      }
      if ($scope.recipients.length === 1) {
        return ts('~1 recipient');
      }
      if (RECIPIENTS_PREVIEW_LIMIT > 0 && $scope.recipients.length >= RECIPIENTS_PREVIEW_LIMIT) {
        return ts('>%1 recipients', {1: RECIPIENTS_PREVIEW_LIMIT});
      }
      return ts('~%1 recipients', {1: $scope.recipients.length});
    };
    $scope.getIncludesAsString = function () {
      var first = true;
      var names = '';
      _.each($scope.mailing.groups.include, function (id) {
        if (!first) {
          names = names + ', ';
        }
        var group = _.where(CRM.crmMailing.groupNames, {id: '' + id});
        names = names + group[0].title;
        first = false;
      });
      _.each($scope.mailing.mailings.include, function (id) {
        if (!first) {
          names = names + ', ';
        }
        var oldMailing = _.where(CRM.crmMailing.civiMails, {id: '' + id});
        names = names + oldMailing[0].name;
        first = false;
      });
      return names;
    };
    $scope.getExcludesAsString = function () {
      var first = true;
      var names = '';
      _.each($scope.mailing.groups.exclude, function (id) {
        if (!first) {
          names = names + ', ';
        }
        var group = _.where(CRM.crmMailing.groupNames, {id: '' + id});
        names = names + group[0].title;
        first = false;
      });
      _.each($scope.mailing.mailings.exclude, function (id) {
        if (!first) {
          names = names + ', ';
        }
        var oldMailing = _.where(CRM.crmMailing.civiMails, {id: '' + id});
        names = names + oldMailing[0].name;
        first = false;
      });
      return names;
    };

    // We monitor four fields -- use debounce so that changes across the
    // four fields can settle-down before AJAX.
    var refreshRecipients = _.debounce(function () {
      $scope.$apply(function () {
        $scope.recipients = null;
        crmMailingMgr.previewRecipients($scope.mailing, RECIPIENTS_PREVIEW_LIMIT).then(function (recipients) {
          $scope.recipients = recipients;
        });
      });
    }, RECIPIENTS_DEBOUNCE_MS);
    $scope.$watchCollection("mailing.groups.include", refreshRecipients);
    $scope.$watchCollection("mailing.groups.exclude", refreshRecipients);
    $scope.$watchCollection("mailing.mailings.include", refreshRecipients);
    $scope.$watchCollection("mailing.mailings.exclude", refreshRecipients);

    $scope.previewRecipients = function previewRecipients() {
      var model = {
        recipients: $scope.recipients
      };
      var options = {
        autoOpen: false,
        modal: true,
        title: ts('Preview (%1)', {
          1: $scope.getRecipientsEstimate()
        })
      };
      dialogService.open('recipDialog', partialUrl('dialog/recipients.html'), model, options);
    };
  });

  // Controller for the "Preview Recipients" dialog
  // Note: Expects $scope.model to be an object with properties:
  //   - recipients: array of contacts
  angular.module('crmMailing').controller('PreviewRecipCtrl', function ($scope) {
    $scope.ts = CRM.ts(null);
  });

  // Controller for the "Preview Mailing" dialog
  // Note: Expects $scope.model to be an object with properties:
  //   - "subject"
  //   - "body_html"
  //   - "body_text"
  angular.module('crmMailing').controller('PreviewMailingDialogCtrl', function PreviewMailingDialogCtrl($scope) {
    $scope.ts = CRM.ts(null);
  });

  // Controller for the "Preview Mailing Component" segment
  // which displays header/footer/auto-responder
  angular.module('crmMailing').controller('PreviewComponentCtrl', function PreviewMailingDialogCtrl($scope, dialogService) {
    var ts = $scope.ts = CRM.ts(null);

    $scope.previewComponent = function previewComponent(title, componentId) {
      var component = _.where(CRM.crmMailing.headerfooterList, {id: "" + componentId});
      if (!component || !component[0]) {
        CRM.alert(ts('Invalid component ID (%1)', {
          1: componentId
        }));
        return;
      }
      var options = {
        autoOpen: false,
        modal: true,
        title: title // component[0].name
      };
      dialogService.open('previewComponentDialog', partialUrl('dialog/previewComponent.html'), component[0], options);
    };
  });

  // Controller for the "Preview Mailing" dialog
  // Note: Expects $scope.model to be an object with properties:
  //   - "name"
  //   - "subject"
  //   - "body_html"
  //   - "body_text"
  angular.module('crmMailing').controller('PreviewComponentDialogCtrl', function PreviewMailingDialogCtrl($scope) {
    $scope.ts = CRM.ts(null);
  });

  // Controller for the in-place msg-template management
  angular.module('crmMailing').controller('MsgTemplateCtrl', function MsgTemplateCtrl($scope, crmMsgTemplates, dialogService) {
    var ts = $scope.ts = CRM.ts(null);
    $scope.crmMsgTemplates = crmMsgTemplates;

    // @return Promise MessageTemplate (per APIv3)
    $scope.saveTemplate = function saveTemplate(mailing) {
      var model = {
        selected_id: mailing.msg_template_id,
        tpl: {
          msg_title: '',
          msg_subject: mailing.subject,
          msg_text: mailing.body_text,
          msg_html: mailing.body_html
        }
      };
      var options = {
        autoOpen: false,
        modal: true,
        title: ts('Save Template')
      };
      return dialogService.open('saveTemplateDialog', partialUrl('dialog/saveTemplate.html'), model, options)
        .then(function (item) {
          mailing.msg_template_id = item.id;
          return item;
        });
    };

    // @param int id
    // @return Promise
    $scope.loadTemplate = function loadTemplate(mailing, id) {
      return crmMsgTemplates.get(id).then(function (tpl) {
        mailing.subject = tpl.msg_subject;
        mailing.body_text = tpl.msg_text;
        mailing.body_html = tpl.msg_html;
      });
    };
  });

  // Controller for the "Save Message Template" dialog
  // Scope members:
  //   - [input] "model": Object
  //     - "selected_id": int
  //     - "tpl": Object
  //       - "msg_subject": string
  //       - "msg_text": string
  //       - "msg_html": string
  angular.module('crmMailing').controller('SaveMsgTemplateDialogCtrl', function SaveMsgTemplateDialogCtrl($scope, crmMsgTemplates, dialogService) {
    var ts = $scope.ts = CRM.ts(null);
    $scope.saveOpt = {mode: '', newTitle: ''};
    $scope.selected = null;

    $scope.save = function save() {
      var tpl = _.extend({}, $scope.model.tpl);
      switch ($scope.saveOpt.mode) {
        case 'add':
          tpl.msg_title = $scope.saveOpt.newTitle;
          break;
        case 'update':
          tpl.id = $scope.selected.id;
          tpl.msg_title = $scope.selected.msg_title;
          break;
        default:
          throw 'SaveMsgTemplateDialogCtrl: Unrecognized mode: ' + $scope.saveOpt.mode;
      }
      return crmMsgTemplates.save(tpl)
        .then(function (item) {
          CRM.status(ts('Saved'));
          return item;
        });
    };

    function scopeApply(f) {
      return function () {
        var args = arguments;
        $scope.$apply(function () {
          f.apply(args);
        });
      };
    }

    function init() {
      crmMsgTemplates.get($scope.model.selected_id).then(
        function (tpl) {
          $scope.saveOpt.mode = 'update';
          $scope.selected = tpl;
        },
        function () {
          $scope.saveOpt.mode = 'add';
          $scope.selected = null;
        }
      );
      // When using dialogService with a button bar, the major button actions
      // need to be registered with the dialog widget (and not embedded in
      // the body of the dialog).
      var buttons = {};
      buttons[ts('Save')] = function () {
        $scope.save().then(function (item) {
          dialogService.close('saveTemplateDialog', item);
        });
      };
      buttons[ts('Cancel')] = function () {
        dialogService.cancel('saveTemplateDialog');
      };
      dialogService.setButtons('saveTemplateDialog', buttons);
    }

    setTimeout(scopeApply(init), 0);
  });

  angular.module('crmMailing').controller('EmailAddrCtrl', function EmailAddrCtrl($scope, crmFromAddresses) {
    $scope.crmFromAddresses = crmFromAddresses;
  });
})(angular, CRM.$, CRM._);
