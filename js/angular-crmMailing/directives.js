(function (angular, $, _) {

  // The following directives have the same simple implementation -- load
  // a template and export a "mailing" object into scope.
  var simpleBlocks = {
    crmMailingBlockApprove: '~/crmMailing/approve.html',
    crmMailingBlockHeaderFooter: '~/crmMailing/headerFooter.html',
    crmMailingBlockMailing: '~/crmMailing/mailing.html',
    crmMailingBlockPublication: '~/crmMailing/publication.html',
    crmMailingBlockResponses: '~/crmMailing/responses.html',
    crmMailingBlockRecipients: '~/crmMailing/recipients.html',
    crmMailingBlockSchedule: '~/crmMailing/schedule.html',
    crmMailingBlockSummary: '~/crmMailing/summary.html',
    crmMailingBlockTracking: '~/crmMailing/tracking.html',
    crmMailingBodyHtml: '~/crmMailing/body_html.html',
    crmMailingBodyText: '~/crmMailing/body_text.html'
  };
  _.each(simpleBlocks, function(templateUrl, directiveName){
    angular.module('crmMailing').directive(directiveName, function ($q, crmMetadata, crmUiHelp) {
      return {
        scope: {
          crmMailing: '@'
        },
        templateUrl: templateUrl,
        link: function (scope, elm, attr) {
          scope.$parent.$watch(attr.crmMailing, function(newValue){
            scope.mailing = newValue;
          });
          scope.crmMailingConst = CRM.crmMailing;
          scope.ts = CRM.ts(null);
          scope.hs = crmUiHelp({file: 'CRM/Mailing/MailingUI'});
          scope[directiveName] = attr[directiveName] ? scope.$parent.$eval(attr[directiveName]) : {};
          $q.when(crmMetadata.getFields('Mailing'), function(fields) {
            scope.mailingFields = fields;
          });
        }
      };
    });
  });

  // example: <div crm-mailing-block-preview crm-mailing="myMailing" on-preview="openPreview(myMailing, preview.mode)" on-send="sendEmail(myMailing,preview.recipient)">
  // note: the directive defines a variable called "preview" with any inputs supplied by the user (e.g. the target recipient for an example mailing)
  angular.module('crmMailing').directive('crmMailingBlockPreview', function (crmUiHelp) {
    return {
      templateUrl: '~/crmMailing/preview.html',
      link: function (scope, elm, attr) {
        scope.$watch(attr.crmMailing, function(newValue){
          scope.mailing = newValue;
        });
        scope.crmMailingConst = CRM.crmMailing;
        scope.ts = CRM.ts(null);
        scope.hs = crmUiHelp({file: 'CRM/Mailing/MailingUI'});
        scope.testContact = {email: CRM.crmMailing.defaultTestEmail};
        scope.testGroup = {gid: null};

        scope.doPreview = function(mode) {
          scope.$eval(attr.onPreview, {
            preview: {mode: mode}
          });
        };
        scope.doSend = function doSend(recipient) {
          scope.$eval(attr.onSend, {
            preview: {recipient: recipient}
          });
        };

        scope.previewTestGroup = function(e) {
          var $dialog = $(this);
          $dialog.html('<div class="crm-loading-element"></div>').parent().find('button[data-op=yes]').prop('disabled', true);
          $dialog.dialog('option', 'title', ts('Send to %1', {1: _.pluck(_.where(scope.crmMailingConst.groupNames, {id: scope.testGroup.gid}), 'title')[0]}));
          CRM.api3('contact', 'get', {group: scope.testGroup.gid, options: {limit: 0}, return: 'display_name,email'}).done(function(data) {
            var count = 0,
              // Fixme: should this be in a template?
              markup = '<ol>';
            _.each(data.values, function(row) {
              // Fixme: contact api doesn't seem capable of filtering out contacts with no email, so we're doing it client-side
              if (row.email) {
                count++;
                markup += '<li>' + row.display_name + ' - ' + row.email + '</li>';
              }
            });
            markup += '</ol>';
            markup = '<h4>' + ts('A test message will be sent to %1 people:', {1: count}) + '</h4>' + markup;
            if (!count) {
              markup = '<div class="messages status"><div class="icon ui-icon-alert"></div> ' +
              (data.count ? ts('None of the contacts in this group have an email address.') : ts('Group is empty.')) +
              '</div>';
            }
            $dialog
              .html(markup)
              .trigger('crmLoad')
              .parent().find('button[data-op=yes]').prop('disabled', !count);
          });
        };
      }
    };
  });

  angular.module('crmMailing').directive('crmMailingBlockReview', function (crmMailingPreviewMgr) {
    return {
      scope: {
        crmMailing: '@'
      },
      templateUrl: '~/crmMailing/review.html',
      link: function (scope, elm, attr) {
        scope.$parent.$watch(attr.crmMailing, function(newValue){
          scope.mailing = newValue;
        });
        scope.crmMailingConst = CRM.crmMailing;
        scope.ts = CRM.ts(null);
        scope.previewMailing = function previewMailing(mailing, mode) {
          return crmMailingPreviewMgr.preview(mailing, mode);
        };
      }
    };
  });

  // Convert between a mailing "From Address" (mailing.from_name,mailing.from_email) and a unified label ("Name" <e@ma.il>)
  // example: <span crm-mailing-from-address="myPlaceholder" crm-mailing="myMailing"><select ng-model="myPlaceholder.label"></select></span>
  // NOTE: This really doesn't belong in a directive. I've tried (and failed) to make this work with a getterSetter binding, eg
  // <select ng-model="mailing.convertFromAddress" ng-model-options="{getterSetter: true}">
  angular.module('crmMailing').directive('crmMailingFromAddress', function (crmFromAddresses) {
    return {
      link: function (scope, element, attrs) {
        var placeholder = attrs.crmMailingFromAddress;
        var mailing = null;
        scope.$watch(attrs.crmMailing, function(newValue){
          mailing = newValue;
          scope[placeholder] = {
            label: crmFromAddresses.getByAuthorEmail(mailing.from_name, mailing.from_email, true).label
          };
        });
        scope.$watch(placeholder + '.label', function (newValue) {
          var addr = crmFromAddresses.getByLabel(newValue);
          mailing.from_name = addr.author;
          mailing.from_email = addr.email;
        });
        // FIXME: Shouldn't we also be watching mailing.from_name and mailing.from_email?
      }
    };
  });

  // Represent a datetime field as if it were a radio ('schedule.mode') and a datetime ('schedule.datetime').
  // example: <div crm-mailing-radio-date="mySchedule" ng-model="mailing.scheduled_date">...</div>
  angular.module('crmMailing').directive('crmMailingRadioDate', function () {
    return {
      require: 'ngModel',
      link: function ($scope, element, attrs, ngModel) {

        var schedule = $scope[attrs.crmMailingRadioDate] = {
          mode: 'now',
          datetime: ''
        };

        ngModel.$render = function $render() {
          var sched = ngModel.$viewValue;
          if (!_.isEmpty(sched)) {
            schedule.mode = 'at';
            schedule.datetime = sched;
          }
          else {
            schedule.mode = 'now';
            schedule.datetime = '';
          }
        };

        var updateParent = (function () {
          switch (schedule.mode) {
            case 'now':
              ngModel.$setViewValue(null);
              schedule.datetime = '';
              break;
            case 'at':
              schedule.datetime = schedule.datetime || '?';
              ngModel.$setViewValue(schedule.datetime);
              break;
            default:
              throw 'Unrecognized schedule mode: ' + schedule.mode;
          }
        });

        element
          // Open datepicker when clicking "At" radio
          .on('click', ':radio[value=at]', function() {
            $('.crm-form-date', element).focus();
          })
          // Reset mode if user entered an invalid date
          .on('change', '.crm-hidden-date', function(e, context) {
            if (context === 'userInput' && $(this).val() === '' && $(this).siblings('.crm-form-date').val().length) {
              schedule.mode = 'at';
              schedule.datetime = '?';
            }
          });

        $scope.$watch(attrs.crmMailingRadioDate + '.mode', updateParent);
        $scope.$watch(attrs.crmMailingRadioDate + '.datetime', function (newValue, oldValue) {
          // automatically switch mode based on datetime entry
          if (typeof oldValue === 'undefined') oldValue = '';
          if (typeof newValue === 'undefined') newValue = '';
          if (oldValue !== newValue) {
            if (_.isEmpty(newValue)) {
              schedule.mode = 'now';
            }
            else {
              schedule.mode = 'at';
            }
          }
          updateParent();
        });
      }
    };
  });

  angular.module('crmMailing').directive('crmMailingReviewBool', function () {
    return {
      scope: {
        crmOn: '@',
        crmTitle: '@'
      },
      template: '<span ng-class="spanClasses"><span class="icon" ng-class="iconClasses"></span>{{evalTitle}} </span>',
      link: function (scope, element, attrs) {
        function refresh() {
          if (scope.$parent.$eval(attrs.crmOn)) {
            scope.spanClasses = {'crmMailing-active': true};
            scope.iconClasses = {'ui-icon-check': true};
          }
          else {
            scope.spanClasses = {'crmMailing-inactive': true};
            scope.iconClasses = {'ui-icon-close': true};
          }
          scope.evalTitle = scope.$parent.$eval(attrs.crmTitle);
        }

        refresh();
        scope.$parent.$watch(attrs.crmOn, refresh);
        scope.$parent.$watch(attrs.crmTitle, refresh);
      }
    };
  });

  // example: <input name="subject" /> <input crm-mailing-token on-select="doSomething(token.name)" />
  // WISHLIST: Instead of global CRM.crmMailing.mailTokens, accept token list as an input
  angular.module('crmMailing').directive('crmMailingToken', function () {
    return {
      require: '^crmUiIdScope',
      scope: {
        onSelect: '@'
      },
      template: '<input type="text" class="crmMailingToken" />',
      link: function (scope, element, attrs, crmUiIdCtrl) {
        $(element).addClass('crm-action-menu action-icon-token').select2({
          width: "12em",
          dropdownAutoWidth: true,
          data: CRM.crmMailing.mailTokens,
          placeholder: ts('Tokens')
        });
        $(element).on('select2-selecting', function (e) {
          e.preventDefault();
          $(element).select2('close').select2('val', '');
          scope.$parent.$eval(attrs.onSelect, {
            token: {name: e.val}
          });
        });
      }
    };
  });

  // example: <select multiple crm-mailing-recipients crm-mailing="mymailing" crm-avail-groups="myGroups" crm-avail-mailings="myMailings"></select>
  // FIXME: participate in ngModel's validation cycle
  angular.module('crmMailing').directive('crmMailingRecipients', function (crmUiAlert) {
    return {
      restrict: 'AE',
      require: 'ngModel',
      scope: {
        crmAvailGroups: '@', // available groups
        crmAvailMailings: '@', // available mailings
        crmMandatoryGroups: '@', // hard-coded/mandatory groups
        ngRequired: '@'
      },
      templateUrl: '~/crmMailing/directive/recipients.html',
      link: function (scope, element, attrs, ngModel) {
        scope.recips = ngModel.$viewValue;
        scope.groups = scope.$parent.$eval(attrs.crmAvailGroups);
        scope.mailings = scope.$parent.$eval(attrs.crmAvailMailings);
        refreshMandatory();

        var ts = scope.ts = CRM.ts(null);

        /// Convert MySQL date ("yyyy-mm-dd hh:mm:ss") to JS date object
        scope.parseDate = function (date) {
          if (!angular.isString(date)) {
            return date;
          }
          var p = date.split(/[\- :]/);
          return new Date(parseInt(p[0]), parseInt(p[1])-1, parseInt(p[2]), parseInt(p[3]), parseInt(p[4]), parseInt(p[5]));
        };

        /// Remove {value} from {array}
        function arrayRemove(array, value) {
          var idx = array.indexOf(value);
          if (idx >= 0) {
            array.splice(idx, 1);
          }
        }

        // @param string id an encoded string like "4 civicrm_mailing include"
        // @return Object keys: entity_id, entity_type, mode
        function convertValueToObj(id) {
          var a = id.split(" ");
          return {entity_id: parseInt(a[0]), entity_type: a[1], mode: a[2]};
        }

        // @param Object mailing
        // @return array list of values like "4 civicrm_mailing include"
        function convertMailingToValues(recipients) {
          var r = [];
          angular.forEach(recipients.groups.include, function (v) {
            r.push(v + " civicrm_group include");
          });
          angular.forEach(recipients.groups.exclude, function (v) {
            r.push(v + " civicrm_group exclude");
          });
          angular.forEach(recipients.mailings.include, function (v) {
            r.push(v + " civicrm_mailing include");
          });
          angular.forEach(recipients.mailings.exclude, function (v) {
            r.push(v + " civicrm_mailing exclude");
          });
          return r;
        }

        function refreshMandatory() {
          if (ngModel.$viewValue && ngModel.$viewValue.groups) {
            scope.mandatoryGroups = _.filter(scope.$parent.$eval(attrs.crmMandatoryGroups), function(grp) {
              return _.contains(ngModel.$viewValue.groups.include, parseInt(grp.id));
            });
            scope.mandatoryIds = _.map(_.pluck(scope.$parent.$eval(attrs.crmMandatoryGroups), 'id'), function(n) {
              return parseInt(n);
            });
          }
          else {
            scope.mandatoryGroups = [];
            scope.mandatoryIds = [];
          }
        }

        function isMandatory(grpId) {
          return _.contains(scope.mandatoryIds, parseInt(grpId));
        }

        var refreshUI = ngModel.$render = function refresuhUI() {
          scope.recips = ngModel.$viewValue;
          if (ngModel.$viewValue) {
            $(element).select2('val', convertMailingToValues(ngModel.$viewValue));
            validate();
            refreshMandatory();
          }
        };

        // @return string HTML representing an option
        function formatItem(item) {
          if (!item.id) {
            // return `text` for optgroup
            return item.text;
          }
          var option = convertValueToObj(item.id);
          var icon = (option.entity_type === 'civicrm_mailing') ? 'EnvelopeIn.gif' : 'group.png';
          var spanClass = (option.mode == 'exclude') ? 'crmMailing-exclude' : 'crmMailing-include';
          if (option.entity_type != 'civicrm_mailing' && isMandatory(option.entity_id)) {
            spanClass = 'crmMailing-mandatory';
          }
          return '<img src="' + CRM.config.resourceBase + 'i/' + icon + '" height="12" width="12" /> <span class="' + spanClass + '">' + item.text + '</span>';
        }

        function validate() {
          if (scope.$parent.$eval(attrs.ngRequired)) {
            var empty = (_.isEmpty(ngModel.$viewValue.groups.include) && _.isEmpty(ngModel.$viewValue.mailings.include));
            ngModel.$setValidity('empty', !empty);
          } else {
            ngModel.$setValidity('empty', true);
          }
        }

        $(element).select2({
          dropdownAutoWidth: true,
          placeholder: "Groups or Past Recipients",
          formatResult: formatItem,
          formatSelection: formatItem,
          escapeMarkup: function (m) {
            return m;
          }
        });

        $(element).on('select2-selecting', function (e) {
          var option = convertValueToObj(e.val);
          var typeKey = option.entity_type == 'civicrm_mailing' ? 'mailings' : 'groups';
          if (option.mode == 'exclude') {
            ngModel.$viewValue[typeKey].exclude.push(option.entity_id);
            arrayRemove(ngModel.$viewValue[typeKey].include, option.entity_id);
          }
          else {
            ngModel.$viewValue[typeKey].include.push(option.entity_id);
            arrayRemove(ngModel.$viewValue[typeKey].exclude, option.entity_id);
          }
          scope.$apply();
          $(element).select2('close');
          validate();
          e.preventDefault();
        });

        $(element).on("select2-removing", function (e) {
          var option = convertValueToObj(e.val);
          var typeKey = option.entity_type == 'civicrm_mailing' ? 'mailings' : 'groups';
          if (typeKey == 'groups' && isMandatory(option.entity_id)) {
            crmUiAlert({
              text: ts('This mailing was generated based on search results. The search results cannot be removed.'),
              title: ts('Required')
            });
            e.preventDefault();
            return;
          }
          scope.$parent.$apply(function () {
            arrayRemove(ngModel.$viewValue[typeKey][option.mode], option.entity_id);
          });
          validate();
          e.preventDefault();
        });

        scope.$watchCollection("recips.groups.include", refreshUI);
        scope.$watchCollection("recips.groups.exclude", refreshUI);
        scope.$watchCollection("recips.mailings.include", refreshUI);
        scope.$watchCollection("recips.mailings.exclude", refreshUI);
        setTimeout(refreshUI, 50);

        scope.$watchCollection(attrs.crmAvailGroups, function() {
          scope.groups = scope.$parent.$eval(attrs.crmAvailGroups);
        });
        scope.$watchCollection(attrs.crmAvailMailings, function() {
          scope.mailings = scope.$parent.$eval(attrs.crmAvailMailings);
        });
        scope.$watchCollection(attrs.crmMandatoryGroups, function() {
          refreshMandatory();
        });
      }
    };
  });

})(angular, CRM.$, CRM._);
