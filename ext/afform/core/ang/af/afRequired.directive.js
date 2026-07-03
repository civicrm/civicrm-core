(function(angular, $, _) {
  angular.module('af').directive('afRequired', function($parse) {
    return {
      restrict: 'A',
      require: ['^^afForm', '?afField'],
      link: function($scope, $element, $attr, ctrls) {
        const afForm = ctrls[0];
        const afField = ctrls[1];
        if (!afField) {
          return;
        }

        const watcher = () => {
          const conditions = $parse($attr.afRequired)();
          return afForm.checkConditions(conditions);
        };

        $scope.$watch(watcher, (value) => {
          afField.defn.required = value;
        });
      }
    };
  });
})(angular, CRM.$, CRM._);
