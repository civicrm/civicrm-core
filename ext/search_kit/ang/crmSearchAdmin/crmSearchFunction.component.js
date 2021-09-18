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

      this.exprTypes = {
        SqlField: {label: ts('Field'), type: 'field'},
        SqlString: {label: ts('Text'), type: 'string'},
        SqlNumber: {label: ts('Number'), type: 'number'},
      };

      this.$onInit = function() {
        var info = searchMeta.parseExpr(ctrl.expr);
        ctrl.args = info.args;
        ctrl.fn = info.fn;
        ctrl.fnName = !info.fn ? '' : info.fn.name;
        initFunction();
      };

      this.addArg = function(exprType) {
        exprType = exprType || ctrl.fn.params[0].must_be[0];
        ctrl.args.push({
          type: ctrl.exprTypes[exprType].type,
          value: exprType === 'SqlNumber' ? 0 : ''
        });
      };

      function initFunction() {
        if (!ctrl.fn) {
          return;
        }
        if (ctrl.fn && ctrl.fn.params[0] && !_.isEmpty(ctrl.fn.params[0].flag_before)) {
          ctrl.modifierName = _.keys(ctrl.fn.params[0].flag_before)[0];
          ctrl.modifierLabel = ctrl.fn.params[0].flag_before[ctrl.modifierName];
        }
        else {
          ctrl.modifierName = null;
          ctrl.modifier = null;
        }
        while (ctrl.args.length < ctrl.fn.params[0].min_expr) {
          ctrl.addArg();
        }
      }

      // On-demand options for dropdown function selector
      this.getFunctions = function() {
        var allowedTypes = [], functions = [];
        if (ctrl.expr && ctrl.args[0] && ctrl.args[0].field) {
          if (ctrl.crmSearchAdmin.canAggregate(ctrl.expr)) {
            allowedTypes.push('aggregate');
          } else {
            allowedTypes.push('comparison', 'string');
            if (_.includes(['Integer', 'Float', 'Date', 'Timestamp'], ctrl.args[0].field.data_type)) {
              allowedTypes.push('math');
            }
            if (_.includes(['Date', 'Timestamp'], ctrl.args[0].field.data_type)) {
              allowedTypes.push('date');
            }
          }
          _.each(allowedTypes, function(type) {
            var allowedFunctions = _.filter(CRM.crmSearchAdmin.functions, function(fn) {
              return fn.category === type &&
                fn.params.length &&
                fn.params[0].min_expr > 0 &&
                _.includes(fn.params[0].must_be, 'SqlField');
            });
            functions.push({
              text: allTypes[type],
              children: formatForSelect2(allowedFunctions, 'name', 'title', ['description'])
            });
          });
        }
        return {results: functions};
      };

      this.getFields = function() {
        return {
          results: ctrl.crmSearchAdmin.getAllFields(':label', ['Field', 'Custom', 'Extra'])
        };
      };

      this.selectFunction = function() {
        ctrl.fn = _.find(CRM.crmSearchAdmin.functions, {name: ctrl.fnName});
        ctrl.args.length = 1;
        initFunction();
        ctrl.writeExpr();
      };

      this.toggleModifier = function() {
        ctrl.modifier = ctrl.modifier ? null : ctrl.modifierName;
        ctrl.writeExpr();
      };

      this.changeArg = function(index) {
        var val = ctrl.args[index].value;
        // Delete empty value
        if (!val && ctrl.args.length > ctrl.fn.params[0].min_expr) {
          ctrl.args.splice(index, 1);
        }
        ctrl.writeExpr();
      };

      // Make a sql-friendly alias for this expression
      function makeAlias() {
        var args = _.pluck(_.filter(_.filter(ctrl.args, 'value'), {type: 'field'}), 'value');
        return (ctrl.fnName + '_' + args.join('_')).replace(/[.:]/g, '_');
      }

      this.writeExpr = function() {
        if (ctrl.fnName) {
          var args = _.transform(ctrl.args, function(args, arg) {
            if (arg.value) {
              args.push(arg.type === 'string' ? JSON.stringify(arg.value) : arg.value);
            }
          });
          ctrl.expr = ctrl.fnName + '(' + (ctrl.modifier ? ctrl.modifier + ' ' : '') + args.join(', ') + ') AS ' + makeAlias();
        } else {
          ctrl.expr = ctrl.args[0].value;
        }
      };
    }
  });

})(angular, CRM.$, CRM._);
