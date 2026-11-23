(function(angular, $) {
  angular.module('afGuiEditor').directive('afGuiConditionalMenu', function() {
    return {
      restrict: 'A',
      templateUrl: '~/afGuiEditor/elements/afGuiConditionalMenu.html',
      // Own $ctrl, so the template reads the bound node rather than the host component's.
      scope: true,
      controllerAs: '$ctrl',
      require: {
        editor: '^^afGuiEditor',
        field: '?^^afGuiField'
      },
      bindToController: {
        node: '<afGuiConditionalMenu'
      },
      controller: function() {

        this.hasRules = () => {
          return !!(
            (this.node['af-if'] && this.node['af-if'].length) ||
            (this.node['af-required'] && this.node['af-required'].length) ||
            (this.node['af-disabled'] && this.node['af-disabled'].length)
          );
        };
      }
    };
  });
})(angular, CRM.$);
