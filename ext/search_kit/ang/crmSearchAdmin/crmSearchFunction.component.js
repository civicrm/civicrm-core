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
        ctrl.fieldArg = _.findWhere(info.args, {type: 'field'});
        ctrl.args = info.args;
        ctrl.fn = info.fn;
        ctrl.fnName = !info.fn ? '' : info.fn.name;
        initFunction();
      };

      this.addArg = function(exprType) {
        var param = ctrl.getParam(ctrl.args.length);
        ctrl.args.push({
          type: ctrl.exprTypes[exprType].type,
          flag_before: _.keys(param.flag_before)[0],
          value: exprType === 'SqlNumber' ? 0 : ''
        });
      };

      function initFunction() {
        if (!ctrl.fn) {
          return;
        }
        // Push args to reach the minimum
        _.each(ctrl.fn.params, function(param, index) {
          while (
            (ctrl.args.length - index < param.min_expr) &&
            // TODO: Handle named params like "ORDER BY"
            !param.name &&
            (!param.optional || param.must_be.length === 1)
          ) {
            ctrl.addArg(param.must_be[0]);
          }
        });
      }

      this.getParam = function(index) {
        return ctrl.fn.params[index] || _.last(ctrl.fn.params);
      };

      this.canAddArg = function() {
        if (!ctrl.fn) {
          return false;
        }
        var param = ctrl.getParam(ctrl.args.length),
          index = ctrl.fn.params.indexOf(param);
        // TODO: Handle named params like "ORDER BY"
        if (param.name) {
          return false;
        }
        return ctrl.args.length - index < param.max_expr;
      };

      // On-demand options for dropdown function selector
      this.getFunctions = function() {
        var allowedTypes = [], functions = [];
        if (ctrl.expr && ctrl.fieldArg) {
          if (ctrl.crmSearchAdmin.canAggregate(ctrl.expr)) {
            allowedTypes.push('aggregate');
          } else {
            allowedTypes.push('comparison', 'string');
            if (_.includes(['Integer', 'Float', 'Date', 'Timestamp', 'Money'], ctrl.fieldArg.field.data_type)) {
              allowedTypes.push('math');
            }
            if (_.includes(['Date', 'Timestamp'], ctrl.fieldArg.field.data_type)) {
              allowedTypes.push('date');
            }
          }
          _.each(allowedTypes, function(type) {
            var allowedFunctions = _.filter(CRM.crmSearchAdmin.functions, function(fn) {
              return fn.category === type && fn.params.length;
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
        delete ctrl.fieldArg.flag_before;
        ctrl.args = [ctrl.fieldArg];
        if (ctrl.fn) {
          var exprType,
            pos = 0;
          // Add non-field args to the beginning if needed
          while (!_.includes(ctrl.fn.params[pos].must_be, 'SqlField')) {
            exprType = ctrl.fn.params[pos].must_be[0];
            ctrl.args.splice(pos, 0, {
              type: ctrl.exprTypes[exprType].type,
              value: exprType === 'SqlNumber' ? 0 : ''
            });
            ++pos;
          }
          initFunction();
        }
        ctrl.writeExpr();
      };

      this.changeArg = function(index) {
        var val = ctrl.args[index].value;
        // Delete empty value
        if (index && !val && ctrl.args.length > ctrl.fn.params[0].min_expr) {
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
          var args = _.transform(ctrl.args, function(args, arg, index) {
            if (arg.value) {
              var prefix = arg.flag_before ? (index ? ' ' : '') + arg.flag_before + ' ' : (index ? ', ' : '');
              args.push(prefix + (arg.type === 'string' ? JSON.stringify(arg.value) : arg.value));
            }
          });
          // Replace fake function "e"
          ctrl.expr = (ctrl.fnName === 'e' ? '' : ctrl.fnName) + '(';
          ctrl.expr += args.join('');
          ctrl.expr += ') AS ' + makeAlias();
        } else {
          ctrl.expr = ctrl.args[0].value;
        }
      };
    }
  });

})(angular, CRM.$, CRM._);
