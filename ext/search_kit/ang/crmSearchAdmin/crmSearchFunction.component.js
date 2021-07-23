(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchFunction', {
    bindings: {
      expr: '=',
      cat: '<'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchFunction.html',
    controller: function($scope, formatForSelect2, searchMeta) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.$onInit = function() {
        ctrl.functions = formatForSelect2(_.where(CRM.crmSearchAdmin.functions, {category: ctrl.cat}), 'name', 'title');
        var fieldInfo = searchMeta.parseExpr(ctrl.expr);
        ctrl.path = fieldInfo.path + fieldInfo.suffix;
        ctrl.field = fieldInfo.field;
        ctrl.fn = !fieldInfo.fn ? '' : fieldInfo.fn.name;
        ctrl.modifier = fieldInfo.modifier || null;
        initFunction();
      };

      function initFunction() {
        ctrl.fnInfo = _.find(CRM.crmSearchAdmin.functions, {name: ctrl.fn});
        if (ctrl.fnInfo && ctrl.fnInfo.params[0] && !_.isEmpty(ctrl.fnInfo.params[0].flag_before)) {
          ctrl.modifierName = _.keys(ctrl.fnInfo.params[0].flag_before)[0];
          ctrl.modifierLabel = ctrl.fnInfo.params[0].flag_before[ctrl.modifierName];
        }
        else {
          ctrl.modifierName = null;
          ctrl.modifier = null;
        }
      }

      this.selectFunction = function() {
        initFunction();
        ctrl.writeExpr();
      };

      this.toggleModifier = function() {
        ctrl.modifier = ctrl.modifier ? null : ctrl. modifierName;
        ctrl.writeExpr();
      };

      // Make a sql-friendly alias for this expression
      function makeAlias() {
        return (ctrl.fn + '_' + (ctrl.modifier ? ctrl.modifier + '_' : '') + ctrl.path).replace(/[.:]/g, '_');
      }

      this.writeExpr = function() {
        ctrl.expr = ctrl.fn ? (ctrl.fn + '(' + (ctrl.modifier ? ctrl.modifier + ' ' : '') + ctrl.path + ') AS ' + makeAlias()) : ctrl.path;
      };
    }
  });

})(angular, CRM.$, CRM._);
