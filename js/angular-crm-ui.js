/// crmUi: Sundry UI helpers
(function (angular, $, _) {

  angular.module('crmUi', [])

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
    .run(function($rootScope, $location) {
      /// Example: <button ng-click="goto('home')">Go home!</button>
      $rootScope.goto = function(path) {
        $location.path(path);
      };
    })
  ;

})(angular, CRM.$, CRM._);