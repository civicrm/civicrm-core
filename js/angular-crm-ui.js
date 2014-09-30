/// crmUi: Sundry UI helpers
(function (angular, $, _) {
  var idCount = 0;

  angular.module('crmUi', [])
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
