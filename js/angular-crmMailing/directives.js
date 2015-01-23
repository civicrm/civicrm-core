(function (angular, $, _) {

  // The following directives have the same simple implementation -- load
  // a template and export a "mailing" object into scope.
  var simpleBlocks = {
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
    angular.module('crmMailing').directive(directiveName, function ($parse) {
      return {
        scope: {
          crmMailing: '@'
        },
        templateUrl: templateUrl,
        link: function (scope, elm, attr) {
          var model = $parse(attr.crmMailing);
          scope.mailing = model(scope.$parent);
          scope.crmMailingConst = CRM.crmMailing;
          scope.ts = CRM.ts(null);
          scope[directiveName] = attr[directiveName] ? scope.$parent.$eval(attr[directiveName]) : {};
        }
      };
    });
  });

  // example: <div crm-mailing-block-preview crm-mailing="myMailing" on-preview="openPreview(myMailing, preview.mode)" on-send="sendEmail(myMailing,preview.recipient)">
  // note: the directive defines a variable called "preview" with any inputs supplied by the user (e.g. the target recipient for an example mailing)
  angular.module('crmMailing').directive('crmMailingBlockPreview', function ($parse) {
    return {
      templateUrl: '~/crmMailing/preview.html',
      link: function (scope, elm, attr) {
        var mailingModel = $parse(attr.crmMailing);
        scope.mailing = mailingModel(scope);
        scope.crmMailingConst = CRM.crmMailing;
        scope.ts = CRM.ts(null);
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
      }
    };
  });

  angular.module('crmMailing').directive('crmMailingBlockReview', function ($parse, crmMailingPreviewMgr) {
    return {
      scope: {
        crmMailing: '@'
      },
      templateUrl: '~/crmMailing/review.html',
      link: function (scope, elm, attr) {
        var mailingModel = $parse(attr.crmMailing);
        scope.mailing = mailingModel(scope.$parent);
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
  angular.module('crmMailing').directive('crmMailingFromAddress', function ($parse, crmFromAddresses) {
    return {
      link: function (scope, element, attrs) {
        var placeholder = attrs.crmMailingFromAddress;
        var model = $parse(attrs.crmMailing);
        var mailing = model(scope.$parent);
        scope[placeholder] = {
          label: crmFromAddresses.getByAuthorEmail(mailing.from_name, mailing.from_email, true).label
        };
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
  // example: <div crm-mailing-radio-date="mySchedule" crm-model="mailing.scheduled_date">...</div>
  // FIXME: use ngModel instead of adhoc crmModel
  angular.module('crmMailing').directive('crmMailingRadioDate', function ($parse) {
    return {
      link: function ($scope, element, attrs) {
        var schedModel = $parse(attrs.crmModel);

        var schedule = $scope[attrs.crmMailingRadioDate] = {
          mode: 'now',
          datetime: ''
        };
        var updateChildren = (function () {
          var sched = schedModel($scope);
          if (sched) {
            schedule.mode = 'at';
            schedule.datetime = sched;
          }
          else {
            schedule.mode = 'now';
          }
        });
        var updateParent = (function () {
          switch (schedule.mode) {
            case 'now':
              schedModel.assign($scope, null);
              break;
            case 'at':
              schedModel.assign($scope, schedule.datetime);
              break;
            default:
              throw 'Unrecognized schedule mode: ' + schedule.mode;
          }
        });

        $scope.$watch(attrs.crmModel, updateChildren);
        $scope.$watch(attrs.crmMailingRadioDate + '.mode', updateParent);
        $scope.$watch(attrs.crmMailingRadioDate + '.datetime', function (newValue, oldValue) {
          // automatically switch mode based on datetime entry
          if (oldValue != newValue) {
            if (!newValue || newValue == " ") {
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
      template: '<span ng-class="spanClasses"><span class="icon" ng-class="iconClasses"></span>{{crmTitle}} </span>',
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
          scope.crmTitle = scope.$parent.$eval(attrs.crmTitle);
        }

        refresh();
        scope.$parent.$watch(attrs.crmOn, refresh);
        scope.$parent.$watch(attrs.crmTitle, refresh);
      }
    };
  });

  // example: <input name="subject" /> <input crm-mailing-token crm-for="subject"/>
  // WISHLIST: Instead of global CRM.crmMailing.mailTokens, accept token list as an input
  angular.module('crmMailing').directive('crmMailingToken', function () {
    return {
      require: '^crmUiIdScope',
      scope: {
        crmFor: '@'
      },
      template: '<input type="text" class="crmMailingToken" />',
      link: function (scope, element, attrs, crmUiIdCtrl) {
        $(element).select2({
          width: "10em",
          dropdownAutoWidth: true,
          data: CRM.crmMailing.mailTokens,
          placeholder: ts('Insert')
        });
        $(element).on('select2-selecting', function (e) {
          var id = crmUiIdCtrl.get(attrs.crmFor);
          if (CKEDITOR.instances[id]) {
            CKEDITOR.instances[id].insertText(e.val);
            $(element).select2('close').select2('val', '');
            CKEDITOR.instances[id].focus();
          }
          else {
            var crmForEl = $('#' + id);
            var origVal = crmForEl.val();
            var origPos = crmForEl[0].selectionStart;
            var newVal = origVal.substring(0, origPos) + e.val + origVal.substring(origPos, origVal.length);
            crmForEl.val(newVal);
            var newPos = (origPos + e.val.length);
            crmForEl[0].selectionStart = newPos;
            crmForEl[0].selectionEnd = newPos;

            $(element).select2('close').select2('val', '');
            crmForEl.triggerHandler('change');
            crmForEl.focus();
          }

          e.preventDefault();
        });
      }
    };
  });

  // example: <select multiple crm-mailing-recipients crm-mailing="mymailing" crm-avail-groups="myGroups" crm-avail-mailings="myMailings"></select>
  // FIXME: participate in ngModel's validation cycle
  angular.module('crmMailing').directive('crmMailingRecipients', function () {
    return {
      restrict: 'AE',
      scope: {
        crmAvailGroups: '@', // available groups
        crmAvailMailings: '@', // available mailings
        crmMailing: '@' // the mailing for which we are choosing recipients
      },
      templateUrl: '~/crmMailing/directive/recipients.html',
      link: function (scope, element, attrs) {
        scope.mailing = scope.$parent.$eval(attrs.crmMailing);
        scope.groups = scope.$parent.$eval(attrs.crmAvailGroups);
        scope.mailings = scope.$parent.$eval(attrs.crmAvailMailings);

        scope.ts = CRM.ts(null);

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
        function convertMailingToValues(mailing) {
          var r = [];
          angular.forEach(mailing.groups.include, function (v) {
            r.push(v + " civicrm_group include");
          });
          angular.forEach(mailing.groups.exclude, function (v) {
            r.push(v + " civicrm_group exclude");
          });
          angular.forEach(mailing.mailings.include, function (v) {
            r.push(v + " civicrm_mailing include");
          });
          angular.forEach(mailing.mailings.exclude, function (v) {
            r.push(v + " civicrm_mailing exclude");
          });
          return r;
        }

        // Update $(element) view based on latest data
        function refreshUI() {
          if (scope.mailing) {
            $(element).select2('val', convertMailingToValues(scope.mailing));
          }
        }

        /// @return string HTML representingn an option
        function formatItem(item) {
          if (!item.id) {
            // return `text` for optgroup
            return item.text;
          }
          var option = convertValueToObj(item.id);
          var icon = (option.entity_type === 'civicrm_mailing') ? 'EnvelopeIn.gif' : 'group.png';
          var spanClass = (option.mode == 'exclude') ? 'crmMailing-exclude' : 'crmMailing-include';
          return "<img src='../../sites/all/modules/civicrm/i/" + icon + "' height=12 width=12 /> <span class='" + spanClass + "'>" + item.text + "</span>";
        }

        $(element).select2({
          dropdownAutoWidth: true,
          placeholder: "Groups or Past Recipients",
          formatResult: formatItem,
          formatSelection: formatItem,
          escapeMarkup: function (m) {
            return m;
          },
        });

        $(element).on('select2-selecting', function (e) {
          var option = convertValueToObj(e.val);
          var typeKey = option.entity_type == 'civicrm_mailing' ? 'mailings' : 'groups';
          if (option.mode == 'exclude') {
            scope.mailing[typeKey].exclude.push(option.entity_id);
            arrayRemove(scope.mailing[typeKey].include, option.entity_id);
          }
          else {
            scope.mailing[typeKey].include.push(option.entity_id);
            arrayRemove(scope.mailing[typeKey].exclude, option.entity_id);
          }
          scope.$apply();
          $(element).select2('close');
          e.preventDefault();
        });

        $(element).on("select2-removing", function (e) {
          var option = convertValueToObj(e.val);
          var typeKey = option.entity_type == 'civicrm_mailing' ? 'mailings' : 'groups';
          scope.$parent.$apply(function () {
            arrayRemove(scope.mailing[typeKey][option.mode], option.entity_id);
          });
          e.preventDefault();
        });

        scope.$watchCollection(attrs.crmMailing + ".groups.include", refreshUI);
        scope.$watchCollection(attrs.crmMailing + ".groups.exclude", refreshUI);
        scope.$watchCollection(attrs.crmMailing + ".mailings.include", refreshUI);
        scope.$watchCollection(attrs.crmMailing + ".mailings.exclude", refreshUI);
        setTimeout(refreshUI, 50);
      }
    };
  });

})(angular, CRM.$, CRM._);
