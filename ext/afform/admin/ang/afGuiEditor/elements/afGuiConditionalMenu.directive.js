(function(angular, $, _) {
  angular.module('afGuiEditor').directive('afGuiConditionalMenu', function() {
    return {
      restrict: 'A',
      templateUrl: '~/afGuiEditor/elements/afGuiConditionalMenu.html',
      require: {
        editor: '^^afGuiEditor',
        field: '?^^afGuiField'
      },
      bindToController: {
        node: '<afGuiConditionalMenu'
      },
      controller: function($scope) {

        $scope.hasRules = () => {
          return !!(
            (this.node['af-if'] && this.node['af-if'].length) ||
            (this.node['af-required'] && this.node['af-required'].length) ||
            (this.node['af-disabled'] && this.node['af-disabled'].length)
          );
        };
      }
    };
  });
})(angular, CRM.$, CRM._);
