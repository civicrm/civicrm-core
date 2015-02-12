/// crmUi: Sundry UI helpers
(function (angular, $, _) {

  var uidCount = 0;

  angular.module('crmUi', [])

    // example <div crm-ui-accordion crm-title="ts('My Title')" crm-collapsed="true">...content...</div>
    // WISHLIST: crmCollapsed should support two-way/continous binding
    .directive('crmUiAccordion', function() {
      return {
        scope: {
          crmTitle: '@',
          crmCollapsed: '@'
        },
        template: '<div class="crm-accordion-wrapper" ng-class="cssClasses"><div class="crm-accordion-header">{{$parent.$eval(crmTitle)}}</div><div class="crm-accordion-body" ng-transclude></div></div>',
        transclude: true,
        link: function (scope, element, attrs) {
          scope.cssClasses = {
            collapsed: scope.$parent.$eval(attrs.crmCollapsed)
          };
        }
      };
    })

    // Display a date widget.
    // example: <input crm-ui-date ng-model="myobj.datefield" />
    // example: <input crm-ui-date ng-model="myobj.datefield" crm-ui-date-format="yy-mm-dd" />
    .directive('crmUiDate', function ($parse, $timeout) {
      return {
        restrict: 'AE',
        require: 'ngModel',
        scope: {
          crmUiDateFormat: '@' // expression, date format (default: "yy-mm-dd")
        },
        link: function (scope, element, attrs, ngModel) {
          var fmt = attrs.crmUiDateFormat ? $parse(attrs.crmUiDateFormat)() : "yy-mm-dd";

          element.addClass('dateplugin');
          $(element).datepicker({
            dateFormat: fmt
          });

          ngModel.$render = function $render() {
            $(element).datepicker('setDate', ngModel.$viewValue);
          };
          var updateParent = (function() {
            $timeout(function () {
              ngModel.$setViewValue(element.val());
            });
          });

          element.on('change', updateParent);
        }
      };
    })

    // Display a date-time widget.
    // example: <div crm-ui-date-time ng-model="myobj.mydatetimefield"></div>
    .directive('crmUiDateTime', function ($parse) {
      return {
        restrict: 'AE',
        require: 'ngModel',
        scope: {
          ngRequired: '@'
        },
        templateUrl: '~/crmUi/datetime.html',
        link: function (scope, element, attrs, ngModel) {
          var ts = scope.ts = CRM.ts(null);
          scope.dateLabel = ts('Date');
          scope.timeLabel = ts('Time');
          element.addClass('crm-ui-datetime');

          ngModel.$render = function $render() {
            if (!_.isEmpty(ngModel.$viewValue)) {
              var dtparts = ngModel.$viewValue.split(/ /);
              scope.dtparts = {date: dtparts[0], time: dtparts[1]};
            }
            else {
              scope.dtparts = {date: '', time: ''};
            }
          };

          function updateParent() {
            var incompleteDateTime = _.isEmpty(scope.dtparts.date) ^ _.isEmpty(scope.dtparts.time);
            ngModel.$setValidity('incompleteDateTime', !incompleteDateTime);

            if (_.isEmpty(scope.dtparts.date) && _.isEmpty(scope.dtparts.time)) {
              ngModel.$setViewValue(' ');
            }
            else {
              //ngModel.$setViewValue(scope.dtparts.date + ' ' + scope.dtparts.time);
              ngModel.$setViewValue((scope.dtparts.date ? scope.dtparts.date : '') + ' ' + (scope.dtparts.time ? scope.dtparts.time : ''));
            }
          }

          scope.$watch('dtparts.date', updateParent);
          scope.$watch('dtparts.time', updateParent);

          function updateRequired() {
            scope.required = scope.$parent.$eval(attrs.ngRequired);
          }

          if (attrs.ngRequired) {
            updateRequired();
            scope.$parent.$watch(attrs.ngRequired, updateRequired);
          }

          scope.reset = function reset() {
            scope.dtparts = {date: '', time: ''};
            ngModel.$setViewValue('');
          };
        }
      };
    })

    // Display a field/row in a field list
    // example: <div crm-ui-field crm-title="My Field"> {{mydata}} </div>
    // example: <div crm-ui-field="subform.myfield" crm-title="'My Field'"> <input crm-ui-id="subform.myfield" name="myfield" /> </div>
    // example: <div crm-ui-field="subform.myfield" crm-title="'My Field'"> <input crm-ui-id="subform.myfield" name="myfield" required /> </div>
    .directive('crmUiField', function() {
      // Note: When writing new templates, the "label" position is particular. See/patch "var label" below.
      var templateUrls = {
        default: '~/crmUi/field.html',
        checkbox: '~/crmUi/field-cb.html'
      };

      return {
        require: '^crmUiIdScope',
        restrict: 'EA',
        scope: {
          crmUiField: '@',
          crmTitle: '@'
        },
        templateUrl: function(tElement, tAttrs){
          var layout = tAttrs.crmLayout ? tAttrs.crmLayout : 'default';
          return templateUrls[layout];
        },
        transclude: true,
        link: function (scope, element, attrs, crmUiIdCtrl) {
          $(element).addClass('crm-section');
          scope.crmUiField = attrs.crmUiField;
          scope.crmTitle = attrs.crmTitle;
        }
      };
    })

    // example: <div ng-form="subform" crm-ui-id-scope><label crm-ui-for="subform.foo">Foo:</label><input crm-ui-id="subform.foo" name="foo"/></div>
    .directive('crmUiId', function () {
      return {
        require: '^crmUiIdScope',
        restrict: 'EA',
        link: {
          pre: function (scope, element, attrs, crmUiIdCtrl) {
            var id = crmUiIdCtrl.get(attrs.crmUiId);
            element.attr('id', id);
          }
        }
      };
    })

    // example: <div ng-form="subform" crm-ui-id-scope><label crm-ui-for="subform.foo">Foo:</label><input crm-ui-id="subform.foo" name="foo"/></div>
    .directive('crmUiFor', function ($parse, $timeout) {
      return {
        require: '^crmUiIdScope',
        restrict: 'EA',
        template: '<span ng-class="cssClasses"><span ng-transclude/><span crm-ui-visible="crmIsRequired" class="crm-marker" title="This field is required.">*</span></span>',
        transclude: true,
        link: function (scope, element, attrs, crmUiIdCtrl) {
          scope.crmIsRequired = false;
          scope.cssClasses = {};

          if (!attrs.crmUiFor) return;

          var id = crmUiIdCtrl.get(attrs.crmUiFor);
          element.attr('for', id);
          var ngModel = null;

          var updateCss = function () {
            scope.cssClasses['crm-error'] = !ngModel.$valid && !ngModel.$pristine;
          };

          // Note: if target element is dynamically generated (eg via ngInclude), then it may not be available
          // immediately for initialization. Use retries/retryDelay to initialize such elements.
          var init = function (retries, retryDelay) {
            var input = $('#' + id);
            if (input.length === 0) {
              if (retries) {
                $timeout(function(){
                  init(retries-1, retryDelay);
                }, retryDelay);
              }
              return;
            }

            var tgtScope = scope;//.$parent;
            if (attrs.crmDepth) {
              for (var i = attrs.crmDepth; i > 0; i--) {
                tgtScope = tgtScope.$parent;
              }
            }

            if (input.attr('ng-required')) {
              scope.crmIsRequired = scope.$parent.$eval(input.attr('ng-required'));
              scope.$parent.$watch(input.attr('ng-required'), function (isRequired) {
                scope.crmIsRequired = isRequired;
              });
            }
            else {
              scope.crmIsRequired = input.prop('required');
            }

            ngModel = $parse(attrs.crmUiFor)(tgtScope);
            if (ngModel) {
              ngModel.$viewChangeListeners.push(updateCss);
            }
          };

          $timeout(function(){
            init(3, 100);
          });
        }
      };
    })

    // Define a scope in which a name like "subform.foo" maps to a unique ID.
    // example: <div ng-form="subform" crm-ui-id-scope><label crm-ui-for="subform.foo">Foo:</label><input crm-ui-id="subform.foo" name="foo"/></div>
    .directive('crmUiIdScope', function () {
      return {
        restrict: 'EA',
        scope: {},
        controllerAs: 'crmUiIdCtrl',
        controller: function($scope) {
          var ids = {};
          this.get = function(name) {
            if (!ids[name]) {
              ids[name] = "crmUiId_" + (++uidCount);
            }
            return ids[name];
          };
        },
        link: function (scope, element, attrs) {}
      };
    })

    // Display an HTML blurb inside an IFRAME.
    // example: <iframe crm-ui-iframe="getHtmlContent()"></iframe>
    .directive('crmUiIframe', function ($parse) {
      return {
        scope: {
          crmUiIframe: '@' // expression which evalutes to HTML content
        },
        link: function (scope, elm, attrs) {
          var iframe = $(elm)[0];
          iframe.setAttribute('width', '100%');
          iframe.setAttribute('frameborder', '0');

          var refresh = function () {
            // var iframeHtml = '<html><head><base target="_blank"></head><body onload="parent.document.getElementById(\'' + iframe.id + '\').style.height=document.body.scrollHeight + \'px\'"><scr' + 'ipt type="text/javascript" src="https://gist.github.com/' + iframeId + '.js"></sc' + 'ript></body></html>';
            var iframeHtml = scope.$parent.$eval(attrs.crmUiIframe);

            var doc = iframe.document;
            if (iframe.contentDocument) {
              doc = iframe.contentDocument;
            }
            else if (iframe.contentWindow) {
              doc = iframe.contentWindow.document;
            }

            doc.open();
            doc.writeln(iframeHtml);
            doc.close();
          };

          scope.$parent.$watch(attrs.crmUiIframe, refresh);
        }
      };
    })

    // Define a rich text editor.
    // example: <textarea crm-ui-id="myForm.body_html" crm-ui-richtext name="body_html" ng-model="mailing.body_html"></textarea>
    .directive('crmUiRichtext', function ($timeout) {
      return {
        require: '?ngModel',
        link: function (scope, elm, attr, ngModel) {
          var ck = CKEDITOR.replace(elm[0]);

          if (!ngModel) {
            return;
          }

          if (attr.ngBlur) {
            ck.on('blur', function(){
              $timeout(function(){
                scope.$eval(attr.ngBlur);
              })
            });
          }

          ck.on('pasteState', function () {
            scope.$apply(function () {
              ngModel.$setViewValue(ck.getData());
            });
          });

          ck.on('insertText', function () {
            $timeout(function () {
              ngModel.$setViewValue(ck.getData());
            });
          });

          ngModel.$render = function (value) {
            ck.setData(ngModel.$viewValue);
          };
        }
      };
    })

    // Display a lock icon (based on a boolean).
    // example: <a crm-ui-lock binding="mymodel.boolfield"></a>
    // example: <a crm-ui-lock
    //            binding="mymodel.boolfield"
    //            title-locked="ts('Boolfield is locked')"
    //            title-unlocked="ts('Boolfield is unlocked')"></a>
    .directive('crmUiLock', function ($parse, $rootScope) {
      var defaultVal = function (defaultValue) {
        var f = function (scope) {
          return defaultValue;
        };
        f.assign = function (scope, value) {
          // ignore changes
        };
        return f;
      };

      // like $parse, but accepts a defaultValue in case expr is undefined
      var parse = function (expr, defaultValue) {
        return expr ? $parse(expr) : defaultVal(defaultValue);
      };

      return {
        template: '',
        link: function (scope, element, attrs) {
          var binding = parse(attrs.binding, true);
          var titleLocked = parse(attrs.titleLocked, ts('Locked'));
          var titleUnlocked = parse(attrs.titleUnlocked, ts('Unlocked'));

          $(element).addClass('ui-icon lock-button');
          var refresh = function () {
            var locked = binding(scope);
            if (locked) {
              $(element)
                .removeClass('ui-icon-unlocked')
                .addClass('ui-icon-locked')
                .prop('title', titleLocked(scope))
              ;
            }
            else {
              $(element)
                .removeClass('ui-icon-locked')
                .addClass('ui-icon-unlocked')
                .prop('title', titleUnlocked(scope))
              ;
            }
          };

          $(element).click(function () {
            binding.assign(scope, !binding(scope));
            //scope.$digest();
            $rootScope.$digest();
          });

          scope.$watch(attrs.binding, refresh);
          scope.$watch(attrs.titleLocked, refresh);
          scope.$watch(attrs.titleUnlocked, refresh);

          refresh();
        }
      };
    })

    // Display a fancy SELECT (based on select2).
    // usage: <select crm-ui-select="{placeholder:'Something',allowClear:true,...}" ng-model="myobj.field"><option...></select>
    .directive('crmUiSelect', function ($parse, $timeout) {
      return {
        require: '?ngModel',
        scope: {
          crmUiSelect: '@'
        },
        link: function (scope, element, attrs, ngModel) {
          // In cases where UI initiates update, there may be an extra
          // call to refreshUI, but it doesn't create a cycle.

          ngModel.$render = function () {
            $timeout(function () {
              // ex: msg_template_id adds new item then selects it; use $timeout to ensure that
              // new item is added before selection is made
              element.select2('val', ngModel.$viewValue);
            });
          };
          function refreshModel() {
            var oldValue = ngModel.$viewValue, newValue = element.select2('val');
            if (oldValue != newValue) {
              scope.$parent.$apply(function () {
                ngModel.$setViewValue(newValue);
              });
            }
          }

          function init() {
            // TODO watch select2-options
            var options = attrs.crmUiSelect ? scope.$parent.$eval(attrs.crmUiSelect) : {};
            element.select2(options);
            element.on('change', refreshModel);
            $timeout(ngModel.$render);
          }

          init();
        }
      };
    })

    // example <div crm-ui-tab crm-title="ts('My Title')">...content...</div>
    // WISHLIST: use a full Angular component instead of an incomplete jQuery wrapper
    .directive('crmUiTab', function($parse) {
      return {
        require: '^crmUiTabSet',
        restrict: 'EA',
        scope: {
          crmTitle: '@',
          id: '@'
        },
        template: '<div ng-transclude></div>',
        transclude: true,
        link: function (scope, element, attrs, crmUiTabSetCtrl) {
          crmUiTabSetCtrl.add(scope);
        }
      };
    })

    // example: <div crm-ui-tab-set><div crm-ui-tab crm-title="Tab 1">...</div><div crm-ui-tab crm-title="Tab 2">...</div></div>
    .directive('crmUiTabSet', function() {
      return {
        restrict: 'EA',
        scope: {
          crmUiTabSet: '@'
        },
        templateUrl: '~/crmUi/tabset.html',
        transclude: true,
        controllerAs: 'crmUiTabSetCtrl',
        controller: function($scope, $parse) {
          var tabs = $scope.tabs = []; // array<$scope>
          this.add = function(tab) {
            if (!tab.id) throw "Tab is missing 'id'";
            tabs.push(tab);
          };
        },
        link: function (scope, element, attrs) {}
      };
    })

    // Display a time-entry field.
    // example: <input crm-ui-time ng-model="myobj.mytimefield" />
    .directive('crmUiTime', function ($parse, $timeout) {
      return {
        restrict: 'AE',
        require: 'ngModel',
        scope: {
        },
        link: function (scope, element, attrs, ngModel) {
          element.addClass('crm-form-text six');
          element.timeEntry({show24Hours: true});

          ngModel.$render = function $render() {
            element.timeEntry('setTime', ngModel.$viewValue);
          };

          var updateParent = (function () {
            $timeout(function () {
              ngModel.$setViewValue(element.val());
            });
          });
          element.on('change', updateParent);
        }
      };
    })

    // Generic, field-independent form validator.
    // example: <span ng-model="placeholder" crm-ui-validate="foo && bar || whiz" />
    // example: <span ng-model="placeholder" crm-ui-validate="foo && bar || whiz" crm-ui-validate-name="myError" />
    .directive('crmUiValidate', function() {
      return {
        restrict: 'EA',
        require: 'ngModel',
        link: function(scope, element, attrs, ngModel) {
          var validationKey = attrs.crmUiValidateName ? attrs.crmUiValidateName : 'crmUiValidate';
          scope.$watch(attrs.crmUiValidate, function(newValue){
            ngModel.$setValidity(validationKey, !!newValue);
          });
        }
      };
    })

    // like ng-show, but hides/displays elements using "visibility" which maintains positioning
    // example <div crm-ui-visible="false">...content...</div>
    .directive('crmUiVisible', function($parse) {
      return {
        restrict: 'EA',
        scope: {
          crmUiVisible: '@'
        },
        link: function (scope, element, attrs) {
          var model = $parse(attrs.crmUiVisible);
          function updatecChildren() {
            element.css('visibility', model(scope.$parent) ? 'inherit' : 'hidden');
          }
          updatecChildren();
          scope.$parent.$watch(attrs.crmUiVisible, updatecChildren);
        }
      };
    })

    // example: <div crm-ui-wizard="myWizardCtrl"><div crm-ui-wizard-step crm-title="ts('Step 1')">...</div><div crm-ui-wizard-step crm-title="ts('Step 2')">...</div></div>
    // Note: "myWizardCtrl" has various actions/properties like next() and $first().
    // WISHLIST: Allow each step to determine if it is "complete" / "valid" / "selectable"
    // WISHLIST: Allow each step to enable/disable (show/hide) itself
    .directive('crmUiWizard', function() {
      return {
        restrict: 'EA',
        scope: {
          crmUiWizard: '@'
        },
        templateUrl: '~/crmUi/wizard.html',
        transclude: true,
        controllerAs: 'crmUiWizardCtrl',
        controller: function($scope, $parse) {
          var steps = $scope.steps = []; // array<$scope>
          var crmUiWizardCtrl = this;
          var maxVisited = 0;
          var selectedIndex = null;

          var findIndex = function() {
            var found = null;
            angular.forEach(steps, function(step, stepKey) {
              if (step.selected) found = stepKey;
            });
            return found;
          };

          /// @return int the index of the current step
          this.$index = function() { return selectedIndex; };
          /// @return bool whether the currentstep is first
          this.$first = function() { return this.$index() === 0; };
          /// @return bool whether the current step is last
          this.$last = function() { return this.$index() === steps.length -1; };
          this.$maxVisit = function() { return maxVisited; };
          this.$validStep = function() {
            return steps[selectedIndex].isStepValid();
          };
          this.iconFor = function(index) {
            if (index < this.$index()) return '√';
            if (index === this.$index()) return '»';
            return ' ';
          };
          this.isSelectable = function(step) {
            if (step.selected) return false;
            var result = false;
            angular.forEach(steps, function(otherStep, otherKey) {
              if (step === otherStep && otherKey <= maxVisited) result = true;
            });
            return result;
          };

          /*** @param Object step the $scope of the step */
          this.select = function(step) {
            angular.forEach(steps, function(otherStep, otherKey) {
              otherStep.selected = (otherStep === step);
              if (otherStep === step && maxVisited < otherKey) maxVisited = otherKey;
            });
            selectedIndex = findIndex();
          };
          /*** @param Object step the $scope of the step */
          this.add = function(step) {
            if (steps.length === 0) {
              step.selected = true;
              selectedIndex = 0;
            }
            steps.push(step);
            steps.sort(function(a,b){
              return a.crmUiWizardStep - b.crmUiWizardStep;
            });
            selectedIndex = findIndex();
          };
          this.remove = function(step) {
            var key = null;
            angular.forEach(steps, function(otherStep, otherKey) {
              if (otherStep === step) key = otherKey;
            });
            if (key !== null) {
              steps.splice(key, 1);
            }
          };
          this.goto = function(index) {
            if (index < 0) index = 0;
            if (index >= steps.length) index = steps.length-1;
            this.select(steps[index]);
          };
          this.previous = function() { this.goto(this.$index()-1); };
          this.next = function() { this.goto(this.$index()+1); };
          if ($scope.crmUiWizard) {
            $parse($scope.crmUiWizard).assign($scope.$parent, this);
          }
        },
        link: function (scope, element, attrs) {}
      };
    })

    // Use this to add extra markup to wizard
    .directive('crmUiWizardButtons', function() {
      return {
        require: '^crmUiWizard',
        restrict: 'EA',
        scope: {},
        template: '<span ng-transclude></span>',
        transclude: true,
        link: function (scope, element, attrs, crmUiWizardCtrl) {
          var realButtonsEl = $(element).closest('.crm-wizard').find('.crm-wizard-buttons');
          $(element).appendTo(realButtonsEl);
        }
      };
    })

    // example: <div crm-ui-wizard-step crm-title="ts('My Title')" ng-form="mySubForm">...content...</div>
    // If there are any conditional steps, then be sure to set a weight explicitly on *all* steps to maintain ordering.
    // example: <div crm-ui-wizard-step="100" crm-title="..." ng-if="...">...content...</div>
    .directive('crmUiWizardStep', function() {
      var nextWeight = 1;
      return {
        require: ['^crmUiWizard', 'form'],
        restrict: 'EA',
        scope: {
          crmTitle: '@', // expression, evaluates to a printable string
          crmUiWizardStep: '@' // int, a weight which determines the ordering of the steps
        },
        template: '<div class="crm-wizard-step" ng-show="selected" ng-transclude/></div>',
        transclude: true,
        link: function (scope, element, attrs, ctrls) {
          var crmUiWizardCtrl = ctrls[0], form = ctrls[1];
          if (scope.crmUiWizardStep) {
            scope.crmUiWizardStep = parseInt(scope.crmUiWizardStep);
          } else {
            scope.crmUiWizardStep = nextWeight++;
          }
          scope.isStepValid = function() {
            return form.$valid;
          };
          crmUiWizardCtrl.add(scope);
          element.on('$destroy', function(){
            crmUiWizardCtrl.remove(scope);
          });
        }
      };
    })

    // Example: <button crm-confirm="{message: ts('Are you sure you want to continue?')}" on-yes="frobnicate(123)">Frobincate</button>
    // Example: <button crm-confirm="{type: 'disable', obj: myObject}" on-yes="myObject.is_active=0; myObject.save()">Disable</button>
    .directive('crmConfirm', function () {
      // Helpers to calculate default options for CRM.confirm()
      var defaultFuncs = {
        'disable': function (options) {
          return {
            message: ts('Are you sure you want to disable this?'),
            options: {no: ts('Cancel'), yes: ts('Disable')},
            width: 300,
            title: ts('Disable %1?', {
              1: options.obj.title || options.obj.label || options.obj.name || ts('the record')
            })
          };
        },
        'revert': function (options) {
          return {
            message: ts('Are you sure you want to revert this?'),
            options: {no: ts('Cancel'), yes: ts('Revert')},
            width: 300,
            title: ts('Revert %1?', {
              1: options.obj.title || options.obj.label || options.obj.name || ts('the record')
            })
          };
        },
        'delete': function (options) {
          return {
            message: ts('Are you sure you want to delete this?'),
            options: {no: ts('Cancel'), yes: ts('Delete')},
            width: 300,
            title: ts('Delete %1?', {
              1: options.obj.title || options.obj.label || options.obj.name || ts('the record')
            })
          };
        }
      };
      return {
        template: '',
        link: function (scope, element, attrs) {
          $(element).click(function () {
            var options = scope.$eval(attrs.crmConfirm);
            var defaults = (options.type) ? defaultFuncs[options.type](options) : {};
            CRM.confirm(_.extend(defaults, options))
              .on('crmConfirm:yes', function () { scope.$apply(attrs.onYes); })
              .on('crmConfirm:no', function () { scope.$apply(attrs.onNo); });
          });
        }
      };
    })
    .run(function($rootScope, $location) {
      /// Example: <button ng-click="goto('home')">Go home!</button>
      $rootScope.goto = function(path) {
        $location.path(path);
      };
      // useful for debugging: $rootScope.log = console.log || function() {};
    })
  ;

})(angular, CRM.$, CRM._);
