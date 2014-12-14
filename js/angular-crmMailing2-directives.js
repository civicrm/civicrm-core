(function (angular, $, _) {
  var partialUrl = function (relPath) {
    return CRM.resourceUrls['civicrm'] + '/partials/crmMailing2/' + relPath;
  };

  var crmMailing2 = angular.module('crmMailing2');

  // The following directives have the same simple implementation -- load
  // a template and export a "mailing" object into scope.
  var simpleBlocks = {
    crmMailingBlockHeaderFooter: partialUrl('headerFooter.html'),
    crmMailingBlockMailing: partialUrl('mailing.html'),
    crmMailingBlockPreview: partialUrl('preview.html'),
    crmMailingBlockPublication: partialUrl('publication.html'),
    crmMailingBlockResponses: partialUrl('responses.html'),
    crmMailingBlockReview: partialUrl('review.html'),
    crmMailingBlockSchedule: partialUrl('schedule.html'),
    crmMailingBlockSummary: partialUrl('summary.html'),
    crmMailingBlockTracking: partialUrl('tracking.html'),
    crmMailingBodyHtml: partialUrl('body_html.html'),
    crmMailingBodyText: partialUrl('body_text.html')
  };
  _.each(simpleBlocks, function(templateUrl, directiveName){
    crmMailing2.directive(directiveName, function ($parse) {
      return {
        scope: {
          crmMailing: '@'
        },
        templateUrl: templateUrl,
        link: function (scope, elm, attr) {
          var model = $parse(attr.crmMailing);
          scope.mailing = model(scope.$parent);
          scope.crmMailingConst = CRM.crmMailing;
          scope.ts = CRM.ts('CiviMail');
        }
      };
    });
  });

  // Convert between a mailing "From Address" (mailing.from_name,mailing.from_email) and a unified label ("Name" <e@ma.il>)
  // example: <span crm-mailing-from-address="myPlaceholder" crm-mailing="myMailing"><select ng-model="myPlaceholder.label"></select></span>
  // NOTE: This really doesn't belong in a directive. I've tried (and failed) to make this work with a getterSetter binding, eg
  // <select ng-model="mailing.convertFromAddress" ng-model-options="{getterSetter: true}">
  crmMailing2.directive('crmMailingFromAddress', function ($parse, crmFromAddresses) {
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

  crmMailing2.directive('crmMailingReviewBool', function () {
    return {
      scope: {
        crmOn: '@',
        crmTitle: '@'
      },
      template: '<span ng-class="spanClasses"><span class="icon" ng-class="iconClasses"></span>{{crmTitle}} </span>',
      link: function (scope, element, attrs) {
        function refresh() {
          if (scope.$parent.$eval(attrs.crmOn)) {
            scope.spanClasses = {'crmMailing2-active': true};
            scope.iconClasses = {'ui-icon-check': true};
          }
          else {
            scope.spanClasses = {'crmMailing2-inactive': true};
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
  crmMailing2.directive('crmMailingToken', function (crmUiId) {
    return {
      scope: {
        crmFor: '@'
      },
      template: '<input type="text" class="crmMailingToken" />',
      link: function (scope, element, attrs) {
        // 1. Find the corresponding input element (crmFor)

        var form = $(element).closest('form');
        var crmForEl = $('input[name="' + attrs.crmFor + '"],textarea[name="' + attrs.crmFor + '"]', form);
        if (form.length != 1 || crmForEl.length != 1) {
          if (console.log) {
            console.log('crmMailingToken cannot be matched to input element. Expected to find one form and one input.', form.length, crmForEl.length);
          }
          return;
        }

        // 2. Setup the token selector
        $(element).select2({
          width: "10em",
          dropdownAutoWidth: true,
          data: CRM.crmMailing.mailTokens,
          placeholder: ts('Insert')
        });
        $(element).on('select2-selecting', function (e) {
          var id = crmUiId(crmForEl);
          if (CKEDITOR.instances[id]) {
            CKEDITOR.instances[id].insertText(e.val);
            $(element).select2('close').select2('val', '');
            CKEDITOR.instances[id].focus();
          }
          else {
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
  crmMailing2.directive('crmMailingRecipients', function () {
    return {
      restrict: 'AE',
      scope: {
        crmAvailGroups: '@', // available groups
        crmAvailMailings: '@', // available mailings
        crmMailing: '@' // the mailing for which we are choosing recipients
      },
      templateUrl: partialUrl('directive/recipients.html'),
      link: function (scope, element, attrs) {
        scope.mailing = scope.$parent.$eval(attrs.crmMailing);
        scope.groups = scope.$parent.$eval(attrs.crmAvailGroups);
        scope.mailings = scope.$parent.$eval(attrs.crmAvailMailings);

        scope.ts = CRM.ts('CiviMail');

        /// Convert MySQL date ("yyyy-mm-dd hh:mm:ss") to JS date object
        scope.parseDate = function (date) {
          if (!angular.isString(date)) {
            return date;
          }
          var p = date.split(/[\- :]/);
          return new Date(p[0], p[1], p[2], p[3], p[4], p[5]);
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
          $(element).select2('val', convertMailingToValues(scope.mailing));
        }

        /// @return string HTML representingn an option
        function formatItem(item) {
          if (!item.id) {
            // return `text` for optgroup
            return item.text;
          }
          var option = convertValueToObj(item.id);
          var icon = (option.entity_type === 'civicrm_mailing') ? 'EnvelopeIn.gif' : 'group.png';
          var spanClass = (option.mode == 'exclude') ? 'crmMailing2-exclude' : 'crmMailing2-include';
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
