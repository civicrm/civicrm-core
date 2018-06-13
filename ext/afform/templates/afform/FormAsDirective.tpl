{* This takes an $afform and generates an AngularJS directive.

 @param string $afform.camel The full camel-case name of the AngularJS module being created
 @param string $afform.meta  The full metadata record of the form
 @param string $afform.layout  The template content
 *}
{literal}
(function(angular, $, _) {
  angular.module('{/literal}{$afform.camel}{literal}', CRM.angRequires('{/literal}{$afform.camel}{literal}'));
  angular.module('{/literal}{$afform.camel}{literal}').directive('{/literal}{$afform.camel}{literal}', function() {
    return {
      restrict: 'AE',
      template: {/literal}{$afform.layout|json}{literal},
      scope: {
        {/literal}{$afform.camel}{literal}: '='
      },
      link: function($scope, $el, $attr) {
        var ts = $scope.ts = CRM.ts('{/literal}{$afform.camel}{literal}');
        $scope.$watch('{/literal}{$afform.camel}{literal}', function(newValue){
          $scope.myOptions = newValue;
        });
      }
    };
  });
})(angular, CRM.$, CRM._);
{/literal}