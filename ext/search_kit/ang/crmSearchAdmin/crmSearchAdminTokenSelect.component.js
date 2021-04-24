(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminTokenSelect', {
    bindings: {
      apiEntity: '<',
      apiParams: '<',
      model: '<',
      field: '@'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdminTokenSelect.html',
    controller: function ($scope, $element, searchMeta) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.initTokens = function() {
        ctrl.tokens = ctrl.tokens || getTokens();
      };

      this.insertToken = function(key) {
        ctrl.model[ctrl.field] = (ctrl.model[ctrl.field] || '') + ctrl.tokens[key].token;
      };

      function getTokens() {
        var tokens = {
          id: {
            token: '[id]',
            label: searchMeta.getField('id', ctrl.apiEntity).label
          }
        };
        _.each(ctrl.apiParams.join, function(joinParams) {
          var info = searchMeta.parseExpr(joinParams[0].split(' AS ')[1] + '.id');
          tokens[info.alias] = {
            token: '[' + info.alias + ']',
            label: info.field ? info.field.label : info.alias
          };
        });
        _.each(ctrl.apiParams.select, function(expr) {
          var info = searchMeta.parseExpr(expr);
          tokens[info.alias] = {
            token: '[' + info.alias + ']',
            label: info.field ? info.field.label : info.alias
          };
        });
        return tokens;
      }

    }
  });

})(angular, CRM.$, CRM._);
