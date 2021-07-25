(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchFunction', {
    bindings: {
      expr: '='
    },
    require: {
      crmSearchAdmin: '^crmSearchAdmin'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchFunction.html',
    controller: function($scope, formatForSelect2, searchMeta) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      var allTypes = {
        aggregate: ts('Aggregate'),
        comparison: ts('Comparison'),
        date: ts('Date'),
        math: ts('Math'),
        string: ts('Text')
      };

      $scope.$watch('$ctrl.expr', function(expr) {
        var fieldInfo = searchMeta.parseExpr(expr);
        ctrl.path = fieldInfo.path + fieldInfo.suffix;
        ctrl.field = fieldInfo.field;
        ctrl.fn = !fieldInfo.fn ? '' : fieldInfo.fn.name;
        ctrl.modifier = fieldInfo.modifier || null;
        initFunction();
      });

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

      this.getFunctions = function() {
        var allowedTypes = [], functions = [];
        if (ctrl.expr && ctrl.field) {
          if (ctrl.crmSearchAdmin.canAggregate(ctrl.expr)) {
            allowedTypes.push('aggregate');
          } else {
            allowedTypes.push('comparison', 'string');
            if (_.includes(['Integer', 'Float', 'Date', 'Timestamp'], ctrl.field.data_type)) {
              allowedTypes.push('math');
            }
            if (_.includes(['Date', 'Timestamp'], ctrl.field.data_type)) {
              allowedTypes.push('date');
            }
          }
          _.each(allowedTypes, function (type) {
            functions.push({
              text: allTypes[type],
              children: formatForSelect2(_.where(CRM.crmSearchAdmin.functions, {category: type}), 'name', 'title')
            });
          });
        }
        return {results: functions};
      };

      this.selectFunction = function() {
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
