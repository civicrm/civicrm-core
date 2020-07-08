(function(angular, $, _) {
  "use strict";

  angular.module('search').component('crmSearchFunction', {
    bindings: {
      expr: '=',
      cat: '<'
    },
    templateUrl: '~/search/crmSearchFunction.html',
    controller: function($scope, formatForSelect2, searchMeta) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this;
      this.functions = formatForSelect2(_.where(CRM.vars.search.functions, {category: this.cat}), 'name', 'title');

      this.$onInit = function() {
        var fieldInfo = searchMeta.parseExpr(ctrl.expr);
        ctrl.path = fieldInfo.path;
        ctrl.field = fieldInfo.field;
        ctrl.fn = !fieldInfo.fn ? '' : fieldInfo.fn.name;
      };

      this.selectFunction = function() {
        ctrl.expr = ctrl.fn ? (ctrl.fn + '(' + ctrl.path + ')') : ctrl.path;
      };
    }
  });

})(angular, CRM.$, CRM._);
