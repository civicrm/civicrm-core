(function(angular, $, _) {

  function chainTranslations(lang, status) {
    return ["Translation", "get", {
      "select": ['id', 'entity_field', 'string'],
      "where": [
        ['entity_table', '=', 'civicrm_msg_template'],
        ['entity_id', '=', '$id'],
        ['language', '=', lang],
        ['status_id:name', '=', status]
      ]
    }];
  }

  function mergeTranslations(prefetch) {
    angular.forEach(prefetch, function(results) {
      angular.forEach(results, function(result) {
        if (result.translations) {
          angular.forEach(result.translations, function(tx) {
            result[tx.entity_field] = tx.string;
          });
        }
      });
    });
    return prefetch;
  }

  function pickFirsts(prefetch) {
    return _.reduce(prefetch, function(all, record, key){
      all[key] = record[0] || undefined;
      return all;
    }, {});
  }

  angular.module('msgtplui').config(function($routeProvider) {
      $routeProvider.when('/edit', {
        controller: 'MsgtpluiEdit',
        controllerAs: '$ctrl',
        templateUrl: '~/msgtplui/Edit.html',

        // If you need to look up data when opening the page, list it out
        // under "resolve".
        resolve: {
          prefetch: function(crmApi4, crmStatus, $location) {
            var args = $location.search();
            var requests = {};

            requests.main = ['MessageTemplate', 'get', {
              where: [['id', '=', args.id]],
            }];

            requests.original = ['MessageTemplate', 'get', {
              join: [["MessageTemplate AS other", "INNER", null, ["workflow_name", "=", "other.workflow_name"]]],
              where: [["other.id", "=", args.id], ["is_reserved", "=", true]],
              limit: 25
            }];

            if (args.lang) {
              requests.txActive = ['MessageTemplate', 'get', {
                where: [['id', '=', args.id]],
                chain: {translations: chainTranslations(args.lang, 'active')}
              }];

              requests.txDraft = ['MessageTemplate', 'get', {
                where: [['id', '=', args.id]],
                chain: {translations: chainTranslations(args.lang, 'draft')}
              }];
            }

            return crmStatus({start: ts('Loading...'), success: ''}, crmApi4(requests).then(mergeTranslations).then(pickFirsts));
          }
        }
      });
    }
  );

  angular.module('msgtplui').controller('MsgtpluiEdit', function($scope, crmApi4, crmBlocker, crmStatus, crmUiAlert, crmUiHelp, $location, prefetch) {
    var block = $scope.block = crmBlocker();
    var ts = $scope.ts = CRM.ts('msgtplui');
    var hs = $scope.hs = crmUiHelp({file: 'CRM/msgtplui/Edit'}); // See: templates/CRM/msgtplui/Edit.hlp
    var ctrl = this;
    var args = $location.search();

    ctrl.records = prefetch;
    if (args.lang) {
      ctrl.lang = args.lang;
      ctrl.tab = args.status === 'draft' ? 'txDraft' : 'txActive';
    }
    else {
      ctrl.lang = null;
      ctrl.tab = 'main';
    }

    ctrl.allowDelete = function() {
      return !!ctrl.lang;
    };

    ctrl.save = function save() {
      var p = crmApi4('Contact', 'get', {limit: 1}); // TODO
      return block(crmStatus({start: ts('TODO-ing...'), success: ts('TODO-ed')}, p));
    };
    ctrl.cancel = function() {
      window.location = '#/workflow';
    };
    ctrl.delete = function() {
      var p = crmApi4('Contact', 'get', {limit: 1}); // TODO
      return block(crmStatus({start: ts('TODO-ing...'), success: ts('TODO-ed')}, p));
    };
  });

})(angular, CRM.$, CRM._);
