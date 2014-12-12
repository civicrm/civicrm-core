/// crmUi: Sundry UI helpers
(function (angular, $, _) {

  var partialUrl = function (relPath) {
    return CRM.resourceUrls['civicrm'] + '/partials/crmUi/' + relPath;
  };

  angular.module('crmUi', [])

    .factory('crmUiId', function() {
      var idCount = 0;
      // Get the HTML ID of an element. If none available, assign one.
      return function crmUiId(el){
        var id = el.attr('id');
        if (!id) {
          id = 'crmUi_' + (++idCount);
          el.attr('id', id);
        }
        return id;
      };
    })

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

    // example: <input crm-ui-date="myobj.datefield" />
    // example: <input crm-ui-date="myobj.datefield" crm-ui-date-format="yy-mm-dd" />
    .directive('crmUiDate', function ($parse, $timeout) {
      return {
        restrict: 'AE',
        scope: {
          crmUiDate: '@', // expression, model binding
          crmUiDateFormat: '@' // expression, date format (default: "yy-mm-dd")
        },
        link: function (scope, element, attrs) {
          var fmt = attrs.crmUiDateFormat ? $parse(attrs.crmUiDateFormat)() : "yy-mm-dd";
          var model = $parse(attrs.crmUiDate);

          element.addClass('dateplugin');
          $(element).datepicker({
            dateFormat: fmt
          });

          var updateChildren = (function() {
            element.off('change', updateParent);
            $(element).datepicker('setDate', model(scope.$parent));
            element.on('change', updateParent);
          });
          var updateParent = (function() {
            $timeout(function () {
              model.assign(scope.$parent, $(element).val());
            });
          });

          updateChildren();
          scope.$parent.$watch(attrs.crmUiDate, updateChildren);
          element.on('change', updateParent);
        }
      };
    })

    // example: <div crm-ui-date-time="myobj.mydatetimefield"></div>
    .directive('crmUiDateTime', function ($parse) {
      return {
        restrict: 'AE',
        scope: {
          crmUiDateTime: '@'
        },
        template: '<input crm-ui-date="dtparts.date" placeholder="{{dateLabel}}"/> <input crm-ui-time="dtparts.time" placeholder="{{timeLabel}}"/>',
        link: function (scope, element, attrs) {
          var model = $parse(attrs.crmUiDateTime);
          scope.dateLabel = ts('Date');
          scope.timeLabel = ts('Time');

          var updateChildren = (function () {
            var value = model(scope.$parent);
            if (value) {
              var dtparts = value.split(/ /);
              scope.dtparts = {date: dtparts[0], time: dtparts[1]};
            }
            else {
              scope.dtparts = {date: '', time: ''};
            }
          });
          var updateParent = (function () {
            model.assign(scope.$parent, scope.dtparts.date + " " + scope.dtparts.time);
          });

          updateChildren();
          scope.$parent.$watch(attrs.crmUiDateTime, updateChildren);
          scope.$watch('dtparts.date', updateParent),
          scope.$watch('dtparts.time', updateParent)
        }
      };
    })

    // Display a field/row in a field list
    // example: <div crm-ui-field crm-title="My Field"> {{mydata}} </div>
    // example: <div crm-ui-field="myfield" crm-title="My Field"> <input name="myfield" /> </div>
    // example: <div crm-ui-field="myfield" crm-title="My Field"> <input name="myfield" required /> </div>
    .directive('crmUiField', function(crmUiId) {
      function createReqStyle(req) {
        return {visibility: req ? 'inherit' : 'hidden'};
      }
      // Note: When writing new templates, the "label" position is particular. See/patch "var label" below.
      var templateUrls = {
        default: partialUrl('field.html'),
        checkbox: partialUrl('field-cb.html')
      };

      return {
        scope: {
          crmUiField: '@', // string, name of an HTML form element
          crmLayout: '@', // string, "default" or "checkbox"
          crmTitle: '@' // expression, printable title for the field
        },
        templateUrl: function(tElement, tAttrs){
          var layout = tAttrs.crmLayout ? tAttrs.crmLayout : 'default';
          return templateUrls[layout];
        },
        transclude: true,
        link: function (scope, element, attrs) {
          $(element).addClass('crm-section');
          scope.crmTitle = attrs.crmTitle;
          scope.crmUiField = attrs.crmUiField;
          scope.cssClasses = {};
          scope.crmRequiredStyle = createReqStyle(false);

          // 0. Ensure that a target field has been specified

          if (!attrs.crmUiField) return;
          if (attrs.crmUiField == 'name') {
            throw new Error('Validation monitoring does not work for field name "name"');
          }

          // 1. Figure out form and input elements

          var form = $(element).closest('form');
          var formCtrl = scope.$parent.$eval(form.attr('name'));
          var input = $('input[name="' + attrs.crmUiField + '"],select[name="' + attrs.crmUiField + '"],textarea[name="' + attrs.crmUiField + '"]', form);
          var label = $('>div.label >label, >label', element);
          if (form.length != 1 || input.length != 1 || label.length != 1) {
            if (console.log) console.log('Label cannot be matched to input element. Expected to find one form and one input[name='+attrs.crmUiField+'].', form.length, input.length, label.length);
            return;
          }

          // 2. Make sure that inputs are well-defined (with name+id).

          crmUiId(input);
          $(label).attr('for', input.attr('id'));

          // 3. Monitor is the "required" and "$valid" properties

          if (input.attr('ng-required')) {
            scope.crmRequiredStyle = createReqStyle(scope.$parent.$eval(input.attr('ng-required')));
            scope.$parent.$watch(input.attr('ng-required'), function(isRequired) {
              scope.crmRequiredStyle = createReqStyle(isRequired);
            });
          } else {
            scope.crmRequiredStyle = createReqStyle(input.prop('required'));
          }

          var inputCtrl = form.attr('name') + '.' + input.attr('name');
          scope.$parent.$watch(inputCtrl + '.$valid', function(newValue) {
            scope.cssClasses['crm-error'] = !scope.$parent.$eval(inputCtrl + '.$valid') && !scope.$parent.$eval(inputCtrl + '.$pristine');
          });
          scope.$parent.$watch(inputCtrl + '.$pristine', function(newValue) {
            scope.cssClasses['crm-error'] = !scope.$parent.$eval(inputCtrl + '.$valid') && !scope.$parent.$eval(inputCtrl + '.$pristine');
          });
        }
      };
    })

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
          }

          scope.$parent.$watch(attrs.crmUiIframe, refresh);
          //setTimeout(function () { refresh(); }, 50);
        }
      };
    })

    // example: <textarea crm-ui-richtext name="body_html" ng-model="mailing.body_html"></textarea>
    .directive('crmUiRichtext', function (crmUiId, $timeout) {
      return {
        require: '?ngModel',
        link: function (scope, elm, attr, ngModel) {
          crmUiId(elm);
          var ck = CKEDITOR.replace(elm[0]);

          if (!ngModel) {
            return;
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

    // example: <a crm-ui-lock binding="mymodel.boolfield"></a>
    // example: <a crm-ui-lock
    //            binding="mymodel.boolfield"
    //            title-locked="ts('Boolfield is locked')"
    //            title-unlocked="ts('Boolfield is unlocked')"></a>
    .directive('crmUiLock', function ($parse, $rootScope) {
      var defaultVal = function (defaultValue) {
        var f = function (scope) {
          return defaultValue;
        }
        f.assign = function (scope, value) {
          // ignore changes
        }
        return f;
      };

      // like $parse, but accepts a defaultValue in case expr is undefined
      var parse = function (expr, defaultValue) {
        return expr ? $parse(expr) : defaultVal(defaultValue);
      };

      return {
        template: '',
        link: function (scope, element, attrs) {
          var binding = parse(attrs['binding'], true);
          var titleLocked = parse(attrs['titleLocked'], ts('Locked'));
          var titleUnlocked = parse(attrs['titleUnlocked'], ts('Unlocked'));

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
              $(element).select2('val', ngModel.$viewValue);
            });
          };
          function refreshModel() {
            var oldValue = ngModel.$viewValue, newValue = $(element).select2('val');
            if (oldValue != newValue) {
              scope.$parent.$apply(function () {
                ngModel.$setViewValue(newValue);
              });
            }
          }

          function init() {
            // TODO watch select2-options
            var options = attrs.crmUiSelect ? scope.$parent.$eval(attrs.crmUiSelect) : {};
            $(element).select2(options);
            $(element).on('change', refreshModel);
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
        templateUrl: partialUrl('tabset.html'),
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

    // example: <input crm-ui-time="myobj.mytimefield" />
    .directive('crmUiTime', function ($parse, $timeout) {
      return {
        restrict: 'AE',
        scope: {
          crmUiTime: '@'
        },
        link: function (scope, element, attrs) {
          var model = $parse(attrs.crmUiTime);

          element.addClass('crm-form-text six');
          $(element).timeEntry({show24Hours: true});

          var updateChildren = (function() {
            element.off('change', updateParent);
            $(element).timeEntry('setTime', model(scope.$parent));
            element.on('change', updateParent);
          });
          var updateParent = (function () {
            $timeout(function () {
              model.assign(scope.$parent, element.val());
            });
          });

          updateChildren();
          scope.$parent.$watch(attrs.crmUiTime, updateChildren);
          element.on('change', updateParent);
        }
      }
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
        templateUrl: partialUrl('wizard.html'),
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
          this.$maxVisit = function() { return maxVisited; }
          this.iconFor = function(index) {
            if (index < this.$index()) return '√';
            if (index === this.$index()) return '»';
            return ' ';
          }
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
          };
          this.goto = function(index) {
            if (index < 0) index = 0;
            if (index >= steps.length) index = steps.length-1;
            this.select(steps[index]);
          };
          this.previous = function() { this.goto(this.$index()-1); };
          this.next = function() { this.goto(this.$index()+1); };
          if ($scope.crmUiWizard) {
            $parse($scope.crmUiWizard).assign($scope.$parent, this)
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

    // example <div crm-ui-wizard-step crm-title="ts('My Title')">...content...</div>
    .directive('crmUiWizardStep', function() {
      return {
        require: '^crmUiWizard',
        restrict: 'EA',
        scope: {
          crmTitle: '@'
        },
        template: '<div class="crm-wizard-step" ng-show="selected" ng-transclude/></div>',
        transclude: true,
        link: function (scope, element, attrs, crmUiWizardCtrl) {
          crmUiWizardCtrl.add(scope);
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
            var options = scope.$eval(attrs['crmConfirm']);
            var defaults = (options.type) ? defaultFuncs[options.type](options) : {};
            CRM.confirm(_.extend(defaults, options))
              .on('crmConfirm:yes', function () { scope.$apply(attrs['onYes']); })
              .on('crmConfirm:no', function () { scope.$apply(attrs['onNo']); });
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
