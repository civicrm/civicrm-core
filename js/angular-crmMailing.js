(function (angular, $, _) {

  angular.module('crmMailing', [
    'crmUtil', 'crmAttachment', 'crmAutosave', 'ngRoute', 'ui.utils', 'crmUi', 'dialogService'
  ]);

  // Time to wait before triggering AJAX update to recipients list
  var RECIPIENTS_DEBOUNCE_MS = 100;
  var RECIPIENTS_PREVIEW_LIMIT = 10000;

  var APPROVAL_STATUSES = {'Approved': 1, 'Rejected': 2, 'None': 3};

  angular.module('crmMailing').config([
    '$routeProvider',
    function ($routeProvider) {
      $routeProvider.when('/mailing', {
        template: '<div></div>',
        controller: 'ListMailingsCtrl'
      });

      var editorPaths = {
        '': '~/crmMailing/edit.html',
        '/unified': '~/crmMailing/edit-unified.html',
        '/unified2': '~/crmMailing/edit-unified2.html',
        '/wizard': '~/crmMailing/edit-wizard.html'
      };
      angular.forEach(editorPaths, function(editTemplate, pathSuffix) {
        if (CRM && CRM.crmMailing && CRM.crmMailing.workflowEnabled) {
          editTemplate = '~/crmMailing/edit-workflow.html'; // override
        }
        $routeProvider.when('/mailing/new' + pathSuffix, {
          template: '<p>' + ts('Initializing...') + '</p>',
          controller: 'CreateMailingCtrl',
          resolve: {
            selectedMail: function(crmMailingMgr) {
              var m = crmMailingMgr.create();
              return crmMailingMgr.save(m);
            }
          }
        });
        $routeProvider.when('/mailing/:id' + pathSuffix, {
          templateUrl: editTemplate,
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
      });
    }
  ]);

  angular.module('crmMailing').controller('ListMailingsCtrl', ['crmLegacy', 'crmNavigator', function ListMailingsCtrl(crmLegacy, crmNavigator) {
    // We haven't implemented this in Angular, but some users may get clever
    // about typing URLs, so we'll provide a redirect.
    var new_url = crmLegacy.url('civicrm/mailing/browse/unscheduled', {reset: 1, scheduled: 'false'});
    crmNavigator.redirect(new_url);
  }]);

  angular.module('crmMailing').controller('CreateMailingCtrl', function EditMailingCtrl($scope, selectedMail, $location) {
    // Transition URL "/mailing/new/foo" => "/mailing/123/foo"
    var parts = $location.path().split('/'); // e.g. "/mailing/new" or "/mailing/123/wizard"
    parts[2] = selectedMail.id;
    $location.path(parts.join('/'));
    $location.replace();
  });

  angular.module('crmMailing').controller('EditMailingCtrl', function EditMailingCtrl($scope, selectedMail, $location, crmMailingMgr, crmStatus, attachments, crmMailingPreviewMgr, crmBlocker, CrmAutosaveCtrl, $timeout, crmUiHelp) {
    $scope.mailing = selectedMail;
    $scope.attachments = attachments;
    $scope.crmMailingConst = CRM.crmMailing;
    $scope.checkPerm = CRM.checkPerm;

    var ts = $scope.ts = CRM.ts(null);
    $scope.hs = crmUiHelp({file: 'CRM/Mailing/MailingUI'});
    var block = $scope.block = crmBlocker();
    var myAutosave = null;

    $scope.isSubmitted = function isSubmitted() {
      return _.size($scope.mailing.jobs) > 0;
    };

    // usage: approve('Approved')
    $scope.approve = function approve(status, options) {
      $scope.mailing.approval_status_id = APPROVAL_STATUSES[status];
      return myAutosave.suspend($scope.submit(options));
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
        });
      return block(crmStatus({start: ts('Saving...'), success: ''}, savePromise)
        .then(function () {
          crmMailingPreviewMgr.sendTest(mailing, recipient);
        }));
    };

    // @return Promise
    $scope.submit = function submit(options) {
      options = options || {};
      if (block.check() || $scope.crmMailing.$invalid) {
        return;
      }

      var promise = crmMailingMgr.save($scope.mailing)
          .then(function () {
            // pre-condition: the mailing exists *before* saving attachments to it
            return $scope.attachments.save();
          })
          .then(function () {
            return crmMailingMgr.submit($scope.mailing);
          })
          .then(function () {
            if (!options.stay) {
              $scope.leave('scheduled');
            }
          })
        ;
      return block(crmStatus({start: ts('Submitting...'), success: ts('Submitted')}, promise));
    };

    // @return Promise
    $scope.save = function save() {
      return block(crmStatus(null,
        crmMailingMgr
          .save($scope.mailing)
          .then(function () {
            // pre-condition: the mailing exists *before* saving attachments to it
            return $scope.attachments.save();
          })
      ));
    };

    // @return Promise
    $scope.delete = function cancel() {
      return block(crmStatus({start: ts('Deleting...'), success: ts('Deleted')},
        crmMailingMgr.delete($scope.mailing)
          .then(function () {
            $scope.leave('unscheduled');
          })
      ));
    };

    // @param string listingScreen 'archive', 'scheduled', 'unscheduled'
    $scope.leave = function leave(listingScreen) {
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
    };

    myAutosave = new CrmAutosaveCtrl({
      save: $scope.save,
      saveIf: function() {
        return true;
      },
      model: function() {
        return [$scope.mailing, $scope.attachments.getAutosaveSignature()];
      },
      form: function() {
        return $scope.crmMailing;
      }
    });
    $timeout(myAutosave.start);
    $scope.$on('$destroy', myAutosave.stop);
  });

  angular.module('crmMailing').controller('ViewRecipCtrl', function EditRecipCtrl($scope) {
    $scope.getIncludesAsString = function(mailing) {
      var first = true;
      var names = '';
      _.each(mailing.recipients.groups.include, function (id) {
        if (!first) {
          names = names + ', ';
        }
        var group = _.where(CRM.crmMailing.groupNames, {id: '' + id});
        names = names + group[0].title;
        first = false;
      });
      _.each(mailing.recipients.mailings.include, function (id) {
        if (!first) {
          names = names + ', ';
        }
        var oldMailing = _.where(CRM.crmMailing.civiMails, {id: '' + id});
        names = names + oldMailing[0].name;
        first = false;
      });
      return names;
    };
    $scope.getExcludesAsString = function (mailing) {
      var first = true;
      var names = '';
      _.each(mailing.recipients.groups.exclude, function (id) {
        if (!first) {
          names = names + ', ';
        }
        var group = _.where(CRM.crmMailing.groupNames, {id: '' + id});
        names = names + group[0].title;
        first = false;
      });
      _.each(mailing.recipients.mailings.exclude, function (id) {
        if (!first) {
          names = names + ', ';
        }
        var oldMailing = _.where(CRM.crmMailing.civiMails, {id: '' + id});
        names = names + oldMailing[0].name;
        first = false;
      });
      return names;
    };
  });

  // Controller for the edit-recipients fields (
  // WISHLIST: Move most of this to a (cache-enabled) service
  // Scope members:
  //  - [input] mailing: object
  //  - [output] recipients: array of recipient records
  angular.module('crmMailing').controller('EditRecipCtrl', function EditRecipCtrl($scope, dialogService, crmApi, crmMailingMgr, $q, crmMetadata) {
    var ts = $scope.ts = CRM.ts(null);

    $scope.isMailingList = function isMailingList(group) {
      var GROUP_TYPE_MAILING_LIST = '2';
      return _.contains(group.group_type, GROUP_TYPE_MAILING_LIST);
    };

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

    // We monitor four fields -- use debounce so that changes across the
    // four fields can settle-down before AJAX.
    var refreshRecipients = _.debounce(function () {
      $scope.$apply(function () {
        $scope.recipients = null;
        if (!$scope.mailing) return;
        crmMailingMgr.previewRecipients($scope.mailing, RECIPIENTS_PREVIEW_LIMIT).then(function (recipients) {
          $scope.recipients = recipients;
        });
      });
    }, RECIPIENTS_DEBOUNCE_MS);
    $scope.$watchCollection("mailing.recipients.groups.include", refreshRecipients);
    $scope.$watchCollection("mailing.recipients.groups.exclude", refreshRecipients);
    $scope.$watchCollection("mailing.recipients.mailings.include", refreshRecipients);
    $scope.$watchCollection("mailing.recipients.mailings.exclude", refreshRecipients);

    $scope.previewRecipients = function previewRecipients() {
      var model = {
        recipients: $scope.recipients
      };
      var options = CRM.utils.adjustDialogDefaults({
        width: '40%',
        autoOpen: false,
        title: ts('Preview (%1)', {
          1: $scope.getRecipientsEstimate()
        })
      });
      dialogService.open('recipDialog', '~/crmMailing/dialog/recipients.html', model, options);
    };

    // Open a dialog for editing the advanced recipient options.
    $scope.editOptions = function editOptions(mailing) {
      var options = CRM.utils.adjustDialogDefaults({
        autoOpen: false,
        width: '40%',
        height: 'auto',
        title: ts('Edit Options')
      });
      $q.when(crmMetadata.getFields('Mailing')).then(function(fields) {
        var model = {
          fields: fields,
          mailing: mailing
        };
        dialogService.open('previewComponentDialog', '~/crmMailing/dialog/recipientOptions.html', model, options);
      });
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

  // Controller for the "Recipients: Edit Options" dialog
  // Note: Expects $scope.model to be an object with properties:
  //   - "mailing" (APIv3 mailing object)
  //   - "fields" (list of fields)
  angular.module('crmMailing').controller('EditRecipOptionsDialogCtrl', function EditRecipOptionsDialogCtrl($scope, crmUiHelp) {
    $scope.ts = CRM.ts(null);
    $scope.hs = crmUiHelp({file: 'CRM/Mailing/MailingUI'});
  });

  // Controller for the "Preview Mailing Component" segment
  // which displays header/footer/auto-responder
  angular.module('crmMailing').controller('PreviewComponentCtrl', function PreviewComponentCtrl($scope, dialogService) {
    var ts = $scope.ts = CRM.ts(null);

    $scope.previewComponent = function previewComponent(title, componentId) {
      var component = _.where(CRM.crmMailing.headerfooterList, {id: "" + componentId});
      if (!component || !component[0]) {
        CRM.alert(ts('Invalid component ID (%1)', {
          1: componentId
        }));
        return;
      }
      var options = CRM.utils.adjustDialogDefaults({
        autoOpen: false,
        title: title // component[0].name
      });
      dialogService.open('previewComponentDialog', '~/crmMailing/dialog/previewComponent.html', component[0], options);
    };
  });

  // Controller for the "Preview Mailing Component" dialog
  // Note: Expects $scope.model to be an object with properties:
  //   - "name"
  //   - "subject"
  //   - "body_html"
  //   - "body_text"
  angular.module('crmMailing').controller('PreviewComponentDialogCtrl', function PreviewComponentDialogCtrl($scope) {
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
      var options = CRM.utils.adjustDialogDefaults({
        autoOpen: false,
        height: 'auto',
        width: '40%',
        title: ts('Save Template')
      });
      return dialogService.open('saveTemplateDialog', '~/crmMailing/dialog/saveTemplate.html', model, options)
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
      var buttons = [
        {
          text: ts('Save'),
          icons: {primary: 'ui-icon-check'},
          click: function () {
            $scope.save().then(function (item) {
              dialogService.close('saveTemplateDialog', item);
            });
          }
        },
        {
          text: ts('Cancel'),
          icons: {primary: 'ui-icon-close'},
          click: function () {
            dialogService.cancel('saveTemplateDialog');
          }
        }
      ];
      dialogService.setButtons('saveTemplateDialog', buttons);
    }

    setTimeout(scopeApply(init), 0);
  });

  angular.module('crmMailing').controller('EmailAddrCtrl', function EmailAddrCtrl($scope, crmFromAddresses, crmUiAlert) {
    var ts = CRM.ts(null);
    function changeAlert(winnerField, loserField) {
      crmUiAlert({
        title: ts('Conflict'),
        text: ts('The "%1" option conflicts with the "%2" option. The "%2" option has been disabled.', {
          1: winnerField,
          2: loserField
        })
      });
    }

    $scope.crmFromAddresses = crmFromAddresses;
    $scope.checkReplyToChange = function checkReplyToChange(mailing) {
      if (!_.isEmpty(mailing.replyto_email) && mailing.override_verp == '0') {
        mailing.override_verp = '1';
        changeAlert(ts('Reply-To'), ts('Track Replies'));
      }
    };
    $scope.checkVerpChange = function checkVerpChange(mailing) {
      if (!_.isEmpty(mailing.replyto_email) && mailing.override_verp == '0') {
        mailing.replyto_email = '';
        changeAlert(ts('Track Replies'), ts('Reply-To'));
      }
    };
  });

  var lastEmailTokenAlert = null;
  angular.module('crmMailing').controller('EmailBodyCtrl', function EmailBodyCtrl($scope, crmMailingMgr, crmUiAlert, $timeout) {
    var ts = CRM.ts(null);

    // ex: if (!hasAllTokens(myMailing, 'body_text)) alert('Oh noes!');
    $scope.hasAllTokens = function hasAllTokens(mailing, field) {
      return _.isEmpty(crmMailingMgr.findMissingTokens(mailing, field));
    };

    // ex: checkTokens(myMailing, 'body_text', 'insert:body_text')
    // ex: checkTokens(myMailing, '*')
    $scope.checkTokens = function checkTokens(mailing, field, insertEvent) {
      if (lastEmailTokenAlert) {
        lastEmailTokenAlert.close();
      }
      var missing, insertable;
      if (field == '*') {
        insertable = false;
        missing = angular.extend({},
          crmMailingMgr.findMissingTokens(mailing, 'body_html'),
          crmMailingMgr.findMissingTokens(mailing, 'body_text')
        );
      } else {
        insertable = !_.isEmpty(insertEvent);
        missing = crmMailingMgr.findMissingTokens(mailing, field);
      }
      if (!_.isEmpty(missing)) {
        lastEmailTokenAlert = crmUiAlert({
          type: 'error',
          title: ts('Required tokens'),
          templateUrl: '~/crmMailing/dialog/tokenAlert.html',
          scope: angular.extend($scope.$new(), {
            insertable: insertable,
            insertToken: function(token) {
              $timeout(function(){
                $scope.$broadcast(insertEvent, '{' + token + '}');
                $timeout(function(){
                  checkTokens(mailing, field, insertEvent);
                });
              });
            },
            missing: missing
          })
        });
      }
    };
  });

  angular.module('crmMailing').controller('EditUnsubGroupCtrl', function EditUnsubGroupCtrl($scope) {
    // CRM.crmMailing.groupNames is a global constant - since it doesn't change, we can digest & cache.
    var mandatoryIds = [];
    _.each(CRM.crmMailing.groupNames, function(grp){
      if (grp.is_hidden == "1") {
        mandatoryIds.push(parseInt(grp.id));
      }
    });

    $scope.isUnsubGroupRequired = function isUnsubGroupRequired(mailing) {
      return _.intersection(mandatoryIds, mailing.recipients.groups.include).length > 0;
    };
  });
})(angular, CRM.$, CRM._);
