(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchFunction', {
    bindings: {
      mode: '@',
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

      // Watch if field is switched
      $scope.$watch('$ctrl.expr', function(newExpr, oldExpr) {
        if (oldExpr && newExpr && newExpr.indexOf('(') < 0) {
          ctrl.$onInit();
        }
      });

      this.addArg = function(exprType, optional) {
        var param = ctrl.getParam(ctrl.args.length),
          val = '';
        if (exprType === 'SqlNumber') {
          // Number: default to 0
          val = 0;
        } else if (exprType === 'SqlField' && !optional) {
          // Field: Default to first available field, making it easier to delete the value
          val = ctrl.getFields().results[0].children[0].id;
        }
        ctrl.args.push({
          type: ctrl.exprTypes[exprType].type,
          flag_before: _.filter(_.keys(param.flag_before))[0],
          flag_after: _.filter(_.keys(param.flag_after))[0],
          name: param.name,
          value: val
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
            // Exclude 'api_default' params (should not be changed by the user)
            !param.api_default &&
            (!param.optional || param.must_be.length === 1)
          ) {
            ctrl.addArg(param.must_be[0], param.optional);
          }
        });
      }

      this.getParam = function(index) {
        if (ctrl.fn) {
          return ctrl.fn.params[index] || _.last(ctrl.fn.params);
        }
      };

      this.canAddArg = function() {
        if (!ctrl.fn) {
          return false;
        }
        var param = ctrl.getParam(ctrl.args.length),
          index = ctrl.fn.params.indexOf(param);
        // TODO: Handle optional named params like "ORDER BY"
        if (param.name && param.optional) {
          return false;
        }
        return ctrl.args.length - index < param.max_expr;
      };

      // On-demand options for dropdown function selector
      this.getFunctions = function() {
        var allowedTypes = [], functions = [];
        if (ctrl.expr && ctrl.fieldArg) {
          // Field in select clause that can be aggregated
          if (ctrl.mode !== 'groupBy' && ctrl.crmSearchAdmin.canAggregate(ctrl.expr)) {
            allowedTypes.push('aggregate');
            // In addition to aggregate functions, also permit a function used in the groupBy clause
            ctrl.crmSearchAdmin.savedSearch.api_params.groupBy.forEach(function(fieldStr) {
              if (fieldStr.includes(ctrl.fieldArg.field.name) && fieldStr.includes('(')) {
                let fieldExpr = searchMeta.parseExpr(fieldStr);
                let field = _.findWhere(fieldExpr.args, {type: 'field'});
                if (fieldExpr.fn && fieldExpr.fn.name !== 'e' && field && field.field.name === ctrl.fieldArg.field.name) {
                  functions.push({
                    text: allTypes[fieldExpr.fn.category],
                    children: formatForSelect2([fieldExpr.fn], 'name', 'title', ['description'])
                  });
                }
              }
            });
          }
          // Field in groupBy clause or field in select clause that isn't required to be aggregated
          if (ctrl.mode === 'groupBy' || !ctrl.crmSearchAdmin.mustAggregate(ctrl.expr)) {
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
        ctrl.args = [ctrl.fieldArg];
        if (ctrl.fn) {
          var exprType,
            pos = 0;
          // Add non-field args to the beginning if needed
          while (!_.includes(ctrl.fn.params[pos].must_be, 'SqlField')) {
            exprType = _.first(ctrl.fn.params[pos].must_be);
            ctrl.args.splice(pos, 0, {
              type: exprType ? ctrl.exprTypes[exprType].type : null,
              flag_before: _.filter(_.keys(ctrl.fn.params[pos].flag_before))[0],
              flag_after: _.filter(_.keys(ctrl.fn.params[pos].flag_after))[0],
              name: ctrl.fn.params[pos].name,
              value: exprType === 'SqlNumber' ? 0 : ''
            });
            ++pos;
          }
          // Update fieldArg
          var fieldParam = ctrl.fn.params[pos];
          ctrl.fieldArg.flag_before = _.keys(fieldParam.flag_before)[0];
          ctrl.fieldArg.flag_after = _.keys(fieldParam.flag_after)[0];
          ctrl.fieldArg.name = fieldParam.name;
          initFunction();
        }
        ctrl.writeExpr();
      };

      this.changeArg = function(index) {
        var val = ctrl.args[index].value,
          param = ctrl.getParam(index);
        // Delete empty value if allowed
        if (index && !val && val !== 0 && !param.optional && ctrl.args.length > param.min_expr) {
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
            if (arg.value || arg.value === 0 || arg.flag_before) {
              var prefix = arg.flag_before || arg.name ? (index ? ' ' : '') + (arg.flag_before || arg.name) + (arg.value ? ' ' : '') : (index ? ', ' : '');
              var suffix = arg.flag_after ? ' ' + arg.flag_after : '';
              args.push(prefix + (arg.type === 'string' ? JSON.stringify(arg.value) : arg.value) + suffix);
            }
          });
          // Replace fake function "e"
          ctrl.expr = (ctrl.fnName === 'e' ? '' : ctrl.fnName) + '(';
          ctrl.expr += args.join('');
          ctrl.expr += ')';
          if (ctrl.mode === 'select') {
            // Add pseudoconstant suffix if function has an option list
            if (ctrl.fn.options) {
              ctrl.expr += ':label';
            }
            ctrl.expr += ' AS ' + makeAlias();
          }
        } else {
          ctrl.expr = ctrl.args[0].value;
        }
      };
    }
  });

})(angular, CRM.$, CRM._);
