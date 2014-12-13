(function (angular, $, _) {
  var partialUrl = function partialUrl(relPath) {
    return CRM.resourceUrls['civicrm'] + '/partials/crmMailing2/' + relPath;
  };

  var crmMailing2 = angular.module('crmMailing2', [
    'crmUtil', 'crmAttachment', 'ngRoute', 'ui.utils', 'crmUi', 'dialogService'
  ]); // TODO ngSanitize, unsavedChanges

  // Time to wait before triggering AJAX update to recipients list
  var RECIPIENTS_DEBOUNCE_MS = 100;
  var RECIPIENTS_PREVIEW_LIMIT = 10000;

  crmMailing2.config([
    '$routeProvider',
    function ($routeProvider) {
      $routeProvider.when('/mailing2', {
        template: '<div></div>',
        controller: 'ListMailingsCtrl'
      });
      $routeProvider.when('/mailing2/:id', {
        templateUrl: partialUrl('edit.html'),
        controller: 'EditMailingCtrl',
        resolve: {
          selectedMail: function selectedMail($route, crmMailingMgr) {
            return crmMailingMgr.getOrCreate($route.current.params.id);
          }
        }
      });
      $routeProvider.when('/mailing2/:id/unified', {
        templateUrl: partialUrl('edit-unified.html'),
        controller: 'EditMailingCtrl',
        resolve: {
          selectedMail: function selectedMail($route, crmMailingMgr) {
            return crmMailingMgr.getOrCreate($route.current.params.id);
          }
        }
      });
      $routeProvider.when('/mailing2/:id/unified2', {
        templateUrl: partialUrl('edit-unified2.html'),
        controller: 'EditMailingCtrl',
        resolve: {
          selectedMail: function selectedMail($route, crmMailingMgr) {
            return crmMailingMgr.getOrCreate($route.current.params.id);
          }
        }
      });
      $routeProvider.when('/mailing2/:id/wizard', {
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

  crmMailing2.controller('ListMailingsCtrl', function ListMailingsCtrl() {
    // We haven't implemented this in Angular, but some users may get clever
    // about typing URLs, so we'll provide a redirect.
    window.location = CRM.url('civicrm/mailing/browse/unscheduled', {
      reset: 1,
      scheduled: 'false'
    });
  });

  crmMailing2.controller('EditMailingCtrl', function EditMailingCtrl($scope, selectedMail, $location, crmMailingMgr, crmFromAddresses, crmStatus, CrmAttachments) {
    $scope.mailing = selectedMail;
    $scope.attachments = new CrmAttachments(function () {
      return {entity_table: 'civicrm_mailing', entity_id: $scope.mailing.id};
    });
    $scope.attachments.load();
    $scope.crmMailingConst = CRM.crmMailing;
    $scope.crmFromAddresses = crmFromAddresses;

    $scope.partialUrl = partialUrl;
    var ts = $scope.ts = CRM.ts('CiviMail');

    // @return Promise
    $scope.submit = function submit() {
      var promise = crmMailingMgr.save($scope.mailing)
        .then(function () {
          // pre-condition: the mailing exists *before* saving attachments to it
          return $scope.attachments.save();
        })
        .then(function () {
          return crmMailingMgr.submit($scope.mailing);
        });
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
      );
    };
    // @return Promise
    $scope.delete = function cancel() {
      return crmStatus({start: ts('Deleting...'), success: ts('Deleted')},
        crmMailingMgr.delete($scope.mailing)
      );
    };
    $scope.leave = function leave() {
      window.location = CRM.url('civicrm/mailing/browse/unscheduled', {
        reset: 1,
        scheduled: 'false'
      });
    };

    // Transition URL "/mailing2/new" => "/mailing2/123" as soon as ID is known
    $scope.$watch('mailing.id', function (newValue, oldValue) {
      if (newValue && newValue != oldValue) {
        var parts = $location.path().split('/'); // e.g. "/mailing2/new" or "/mailing2/123/wizard"
        parts[2] = newValue;
        $location.path(parts.join('/'));
        $location.replace();
        // FIXME: Angular unnecessarily refreshes UI
      }
    });

    $scope.fromPlaceholder = {
      label: crmFromAddresses.getByAuthorEmail($scope.mailing.from_name, $scope.mailing.from_email, true).label
    };
    $scope.$watch('fromPlaceholder.label', function (newValue) {
      var addr = crmFromAddresses.getByLabel(newValue);
      $scope.mailing.from_name = addr.author;
      $scope.mailing.from_email = addr.email;
    });
  });

  // Controller for the edit-recipients fields (
  // WISHLIST: Move most of this to a (cache-enabled) service
  // Scope members:
  //  - [input] mailing: object
  //  - [output] recipients: array of recipient records
  crmMailing2.controller('EditRecipCtrl', function EditRecipCtrl($scope, dialogService, crmApi, crmMailingMgr) {
    var ts = $scope.ts = CRM.ts('CiviMail');
    $scope.recipients = null;
    $scope.getRecipientsEstimate = function () {
      var ts = $scope.ts;
      if ($scope.recipients == null) {
        return ts('(Estimating)');
      }
      if ($scope.recipients.length == 0) {
        return ts('No recipients');
      }
      if ($scope.recipients.length == 1) {
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
  crmMailing2.controller('PreviewRecipCtrl', function ($scope) {
    $scope.ts = CRM.ts('CiviMail');
  });

  // Controller for the "Preview Mailing" segment
  // Note: Expects $scope.model to be an object with properties:
  //   - mailing: object
  //   - attachments: object
  crmMailing2.controller('PreviewMailingCtrl', function ($scope, dialogService, crmMailingMgr, crmStatus) {
    var ts = $scope.ts = CRM.ts('CiviMail');

    $scope.testContact = {email: CRM.crmMailing.defaultTestEmail};
    $scope.testGroup = {gid: null};

    $scope.previewHtml = function previewHtml() {
      $scope.previewDialog(partialUrl('dialog/previewHtml.html'));
    };
    $scope.previewText = function previewText() {
      $scope.previewDialog(partialUrl('dialog/previewText.html'));
    };
    $scope.previewFull = function previewFull() {
      $scope.previewDialog(partialUrl('dialog/previewFull.html'));
    };
    // Open a dialog with a preview of the current mailing
    // @param template string URL of the template to use in the preview dialog
    $scope.previewDialog = function previewDialog(template) {
      var p = crmMailingMgr
        .preview($scope.mailing)
        .then(function (content) {
          var options = {
            autoOpen: false,
            modal: true,
            title: ts('Subject: %1', {
              1: content.subject
            })
          };
          dialogService.open('previewDialog', template, content, options);
        });
      CRM.status({start: ts('Previewing'), success: ''}, CRM.toJqPromise(p));
    };
    $scope.sendTestToContact = function sendTestToContact() {
      $scope.sendTest($scope.mailing, $scope.attachments, $scope.testContact.email, null);
    };
    $scope.sendTestToGroup = function sendTestToGroup() {
      $scope.sendTest($scope.mailing, $scope.attachments, null, $scope.testGroup.gid);
    };
    $scope.sendTest = function sendTest(mailing, attachments, testEmail, testGroup) {
      var promise = crmMailingMgr.save(mailing)
          .then(function () {
            return attachments.save();
          })
          .then(function () {
            return crmMailingMgr.sendTest(mailing, testEmail, testGroup);
          })
          .then(function (deliveryInfos) {
            var count = Object.keys(deliveryInfos).length;
            if (count === 0) {
              CRM.alert(ts('Could not identify any recipients. Perhaps the group is empty?'));
            }
          })
        ;
      return crmStatus({start: ts('Sending...'), success: ts('Sent')}, promise);
    };
  });

  // Controller for the "Preview Mailing" dialog
  // Note: Expects $scope.model to be an object with properties:
  //   - "subject"
  //   - "body_html"
  //   - "body_text"
  crmMailing2.controller('PreviewMailingDialogCtrl', function PreviewMailingDialogCtrl($scope, crmMailingMgr) {
    $scope.ts = CRM.ts('CiviMail');
  });

  // Controller for the "Preview Mailing Component" segment
  // which displays header/footer/auto-responder
  crmMailing2.controller('PreviewComponentCtrl', function PreviewMailingDialogCtrl($scope, dialogService) {
    var ts = $scope.ts = CRM.ts('CiviMail');

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
  crmMailing2.controller('PreviewComponentDialogCtrl', function PreviewMailingDialogCtrl($scope) {
    $scope.ts = CRM.ts('CiviMail');
  });

  // Controller for the in-place msg-template management
  // Scope members:
  //  - [input] mailing: object
  crmMailing2.controller('MsgTemplateCtrl', function MsgTemplateCtrl($scope, crmMsgTemplates, dialogService, $parse) {
    var ts = $scope.ts = CRM.ts('CiviMail');
    $scope.crmMsgTemplates = crmMsgTemplates;

    // @return Promise MessageTemplate (per APIv3)
    $scope.saveTemplate = function saveTemplate() {
      var model = {
        selected_id: $scope.mailing.msg_template_id,
        tpl: {
          msg_title: '',
          msg_subject: $scope.mailing.subject,
          msg_text: $scope.mailing.body_text,
          msg_html: $scope.mailing.body_html
        }
      };
      var options = {
        autoOpen: false,
        modal: true,
        title: ts('Save Template')
      };
      return dialogService.open('saveTemplateDialog', partialUrl('dialog/saveTemplate.html'), model, options)
        .then(function (item) {
          $parse('mailing.msg_template_id').assign($scope, item.id);
          return item;
        });
    };

    // @param int id
    // @return Promise
    $scope.loadTemplate = function loadTemplate(id) {
      return crmMsgTemplates.get(id).then(function (tpl) {
        $scope.mailing.subject = tpl.msg_subject;
        $scope.mailing.body_text = tpl.msg_text;
        $scope.mailing.body_html = tpl.msg_html;
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
  crmMailing2.controller('SaveMsgTemplateDialogCtrl', function SaveMsgTemplateDialogCtrl($scope, crmMsgTemplates, dialogService) {
    var ts = $scope.ts = CRM.ts('CiviMail');
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

  // Controller for schedule-editing widget.
  // Scope members:
  //  - [input] mailing: object
  //     - scheduled_date: null|string(YYYY-MM-DD hh:mm)
  crmMailing2.controller('EditScheduleCtrl', function EditScheduleCtrl($scope, $parse) {
    var schedModelExpr = 'mailing.scheduled_date';
    var schedModel = $parse(schedModelExpr);

    $scope.schedule = {
      mode: 'now',
      datetime: ''
    };
    var updateChildren = (function () {
      var sched = schedModel($scope);
      if (sched) {
        $scope.schedule.mode = 'at';
        $scope.schedule.datetime = sched;
      }
      else {
        $scope.schedule.mode = 'now';
      }
    });
    var updateParent = (function () {
      switch ($scope.schedule.mode) {
        case 'now':
          schedModel.assign($scope, null);
          break;
        case 'at':
          schedModel.assign($scope, $scope.schedule.datetime);
          break;
        default:
          throw 'Unrecognized schedule mode: ' + $scope.schedule.mode;
      }
    });

    $scope.$watch(schedModelExpr, updateChildren);
    $scope.$watch('schedule.mode', updateParent);
    $scope.$watch('schedule.datetime', function (newValue, oldValue) {
      // automatically switch mode based on datetime entry
      if (oldValue != newValue) {
        if (!newValue || newValue == " ") {
          $scope.schedule.mode = 'now';
        }
        else {
          $scope.schedule.mode = 'at';
        }
      }
      updateParent();
    });
  });

})(angular, CRM.$, CRM._);
