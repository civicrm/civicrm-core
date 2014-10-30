/// crmUi: Sundry UI helpers
(function (angular, $, _) {
  var idCount = 0;

  var partialUrl = function (relPath) {
    return CRM.resourceUrls['civicrm'] + '/partials/crmUi/' + relPath;
  };


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

    // example: <form name="myForm">...<label crm-ui-label crm-for="myField">My Field</span>...<input name="myField"/>...</form>
    //
    // Label adapts based on <input required>, <input ng-required>, or any other validation.
    //
    // Note: This should work in the normal case where <label> and <input> are in roughly the same scope,
    // but if the scopes are materially different then problems could arise.
    .directive('crmUiLabel', function($parse) {
      return {
        scope: {
          name: '@'
        },
        transclude: true,
        template: '<span ng-class="cssClasses"><span ng-transclude></span> <span ng-show="crmRequired" class="crm-marker" title="This field is required.">*</span></span>',
        link: function(scope, element, attrs) {
          if (attrs.crmFor == 'name') {
            throw new Error('Validation monitoring does not work for field name "name"');
          }

          // 1. Figure out form and input elements

          var form = $(element).closest('form');
          var formCtrl = scope.$parent.$eval(form.attr('name'));
          var input = $('input[name="' + attrs.crmFor + '"],select[name="' + attrs.crmFor + '"],textarea[name="' + attrs.crmFor + '"]', form);
          if (form.length != 1 || input.length != 1) {
            if (console.log) console.log('Label cannot be matched to input element. Expected to find one form and one input.', form.length, input.length);
            return;
          }

          // 2. Make sure that inputs are well-defined (with name+id).

          if (!input.attr('id')) {
            input.attr('id', 'crmUi_' + (++idCount));
          }
          $(element).attr('for', input.attr('id'));

          // 3. Monitor is the "required" and "$valid" properties

          if (input.attr('ng-required')) {
            scope.crmRequired = scope.$parent.$eval(input.attr('ng-required'));
            scope.$parent.$watch(input.attr('ng-required'), function(isRequired) {
              scope.crmRequired = isRequired;
            });
          } else {
            scope.crmRequired = input.prop('required');
          }

          var inputCtrl = form.attr('name') + '.' + input.attr('name');
          scope.cssClasses = {};
          scope.$parent.$watch(inputCtrl + '.$valid', function(newValue) {
            //scope.cssClasses['ng-valid'] = newValue;
            //scope.cssClasses['ng-invalid'] = !newValue;
            scope.cssClasses['crm-error'] = !scope.$parent.$eval(inputCtrl + '.$valid') && !scope.$parent.$eval(inputCtrl + '.$pristine');
          });
          scope.$parent.$watch(inputCtrl + '.$pristine', function(newValue) {
            //scope.cssClasses['ng-pristine'] = newValue;
            //scope.cssClasses['ng-dirty'] = !newValue;
            scope.cssClasses['crm-error'] = !scope.$parent.$eval(inputCtrl + '.$valid') && !scope.$parent.$eval(inputCtrl + '.$pristine');
          });

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
