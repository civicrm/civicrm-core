(function(angular, $, _) {

  var TRANSLATED = ['msg_subject', 'msg_html', 'msg_text'];

  /**
   * Create an APIv4 request to replace a series of translations.
   *
   * @param id
   *   The ID of the translated entity.
   * @param lang
   *   The language of the translation ('fr_CA', 'en_GB').
   * @param status
   *   The status of the translation ('active', 'draft').
   * @param values
   *   Key-value pairs. ({msg_title: 'Cheerio'})
   * @returns []
   *   An API call which replaces the translations ([entity,action,params]).
   */
  function reqReplaceTranslations(id, lang, status, values) {
    if (!values._exists) {
      return reqDeleteTranslations(id, lang, status);
    }
    var records = [];
    angular.forEach(TRANSLATED, function(key) {
      records.push({"entity_field":key, "string":values[key]});
    });
    return ['Translation', 'replace', {
      records: records,
      where: [
        ["entity_table", "=", "civicrm_msg_template"],
        ["entity_id", "=", id],
        ["language", "=", lang],
        ["status_id:name", "=", status]
      ]
    }];
  }

  function reqDeleteTranslations(id, lang, status) {
    return ['Translation', 'delete', {
      where: [
        ["entity_table", "=", "civicrm_msg_template"],
        ["entity_id", "=", id],
        ["language", "=", lang],
        ["status_id:name", "=", status]
      ]
    }];
  }

  /**
   * Create an APIv4 request to read a series of translations.
   *
   * @param lang
   *   The language of the translation ('fr_CA', 'en_GB').
   * @param status
   *   The status of the translation ('active', 'draft').
   * @returns []
   *   An API call which replaces the translations ([entity,action,params]).
   */
  function reqChainTranslations(lang, status) {
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

  function respMergeTranslations(prefetch) {
    angular.forEach(prefetch, function(results, queryName) {
      var forceExists = (queryName === 'txActive');
      angular.forEach(results, function(result) {
        if (result.translations) {
          angular.forEach(result.translations, function(tx) {
            result[tx.entity_field] = tx.string;
          });
          result._exists = forceExists || (result.translations.length > 0);
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

  function copyTranslations(src, dest) {
    dest._exists = false; // Starting assumption - prove otherwise in a moment.
    TRANSLATED.forEach(function(fld) {
      if (src[fld] !== undefined) {
        dest._exists = true;
        dest[fld] = src[fld];
      }
      else {
        delete dest[fld];
      }
    });
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
                select: TRANSLATED,
                where: [['id', '=', args.id]],
                chain: {translations: reqChainTranslations(args.lang, 'active')}
              }];

              requests.txDraft = ['MessageTemplate', 'get', {
                select: TRANSLATED,
                where: [['id', '=', args.id]],
                chain: {translations: reqChainTranslations(args.lang, 'draft')}
              }];
            }

            return crmStatus({start: ts('Loading...'), success: ''}, crmApi4(requests).then(respMergeTranslations).then(pickFirsts));
          },
          tokenList: function () {
            return CRM.crmMailing.mailTokens;
          }
        }
      });
    }
  );

  angular.module('msgtplui').controller('MsgtpluiEdit', function($q, $scope, crmApi4, crmBlocker, crmStatus, crmUiAlert, crmUiHelp, $location, prefetch, tokenList, $rootScope, dialogService) {
    var block = $scope.block = crmBlocker();
    var ts = $scope.ts = CRM.ts('msgtplui');
    var hs = $scope.hs = crmUiHelp({file: 'CRM/Msgtplui/Edit'}); // See: templates/CRM/Msgtplui/Edit.hlp
    var $ctrl = this;
    var args = $location.search();

    $ctrl.locales = CRM.msgtplui.uiLanguages;
    $ctrl.records = prefetch;
    $ctrl.tokenList = tokenList;
    if (args.lang) {
      $ctrl.lang = args.lang;
      $ctrl.tab = (args.status === 'draft' && $ctrl.records.txDraft && $ctrl.records.txDraft._exists) ? 'txDraft' : 'txActive';
    }
    else {
      $ctrl.lang = null;
      $ctrl.tab = 'main';
    }

    var revisionTypes = [
      {name: 'original', label: ts('Original')},
      {name: 'main', label: ts('Standard')},
      {name: 'txActive', label: ts('%1 - Current translation', {1: $ctrl.locales[$ctrl.lang] || $ctrl.lang})},
      {name: 'txDraft', label: ts('%1 - Draft translation', {1: $ctrl.locales[$ctrl.lang] || $ctrl.lang})}
    ];

    $ctrl.switchTab = function switchTab(tgt) {
      $ctrl.tab = tgt;
      // Experimenting with action buttons in the tab-bar. This makes the scroll unnecessary.
      // $('html, body').animate({scrollTop: $("a[name=msgtplui-tabs]").offset().top - $('#civicrm-menu').height()}, 200);
    };
    $ctrl.allowDraft = function allowDraft() {
      return !!$ctrl.lang;
    };
    $ctrl.hasDraft = function hasDraft() {
      return $ctrl.allowDraft() && $ctrl.records.txDraft && $ctrl.records.txDraft._exists;
    };
    $ctrl.hasRevType = function hasRevType(name) {
      switch (name) {
        case 'txDraft': return $ctrl.hasDraft();
        case 'txActive': return !!$ctrl.lang;
        case 'original': return !!$ctrl.records.original;
        case 'main': return !$ctrl.lang; // !!$ctrl.records.main;
      }
    };
    $ctrl.createDraft = function createDraft(src) {
      copyTranslations(src, $ctrl.records.txDraft);
      $ctrl.switchTab('txDraft');
      crmStatus({success: ts('Beginning draft...')}, $q.resolve());
    };
    $ctrl.deleteDraft = function deleteDraft() {
      copyTranslations({}, $ctrl.records.txDraft);
      $ctrl.switchTab('txActive');
      crmStatus({error: ts('Abandoned draft.')}, $q.reject()).then(function(){},function(){});
    };
    $ctrl.activateDraft = function activateDraft() {
      copyTranslations($ctrl.records.txDraft, $ctrl.records.txActive);
      copyTranslations({}, $ctrl.records.txDraft);
      $ctrl.switchTab('txActive');
      crmStatus({success: ts('Activated draft.')}, $q.resolve());
    };

    $ctrl.save = function save() {
      var requests = {};
      if ($ctrl.lang) {
        requests.txActive = reqReplaceTranslations($ctrl.records.main.id, $ctrl.lang, 'active', $ctrl.records.txActive);
        requests.txDraft = reqReplaceTranslations($ctrl.records.main.id, $ctrl.lang, 'draft', $ctrl.records.txDraft);
      }
      else {
        requests.main = ['MessageTemplate', 'update', {
          where: [['id', '=', $ctrl.records.main.id]],
          values: $ctrl.records.main
        }];
      }
      return block(crmStatus({start: ts('Saving...'), success: ts('Saved')}, crmApi4(requests)));
    };
    $ctrl.cancel = function() {
      window.location = '#/workflow';
    };
    $ctrl.delete = function() {
      var requests = {};
      if ($ctrl.lang) {
        requests.txActive = reqDeleteTranslations($ctrl.records.main.id, $ctrl.lang, 'active');
        requests.txDraft = reqDeleteTranslations($ctrl.records.main.id, $ctrl.lang, 'draft');
      }
      else {
        requests.main = ['MessageTemplate', 'delete', {where: [['id', '=', $ctrl.records.main.id]]}];
      }
      return block(crmStatus({start: ts('Deleting...'), success: ts('Deleted')}, crmApi4(requests)))
        .then($ctrl.cancel);
    };

    // Ex: $rootScope.$emit('previewMsgTpl', {revisionName: 'txDraft', formatName: 'msg_text'})
    function onPreview(event, args) {
      var defaults = {
        // exampleName: 'fix-this-example',
        // examples: [
        //   {id: 0, name: 'fix-this-example', title: ts('Fix this example')},
        //   {id: 1, name: 'another-example', title: ts('Another example')}
        // ],
        formatName: 'msg_html',
        formats: [
          {id: 0, name: 'msg_html', label: ts('HTML')},
          {id: 1, name: 'msg_text', label: ts('Text')}
        ],
        revisionName: $ctrl.tab,
        revisions: _.reduce(revisionTypes, function(acc, revType){
          if ($ctrl.hasRevType(revType.name)) {
            acc.push(angular.extend({id: acc.length, rec: $ctrl.records[revType.name]}, revType));
          }
          return acc;
        }, []),
        title: ts('Preview')
      };

      crmApi4({
        examples: ['ExampleData', 'get', {
          // FIXME: workflow name
          where: [["tags", "CONTAINS", "preview"], ["name", "LIKE", "workflow/" + $ctrl.records.main.workflow_name + "/%"]],
          select: ['name', 'title', 'data']
        }],
        adhoc: ['WorkflowMessage', 'getTemplateFields', {
          workflow: $ctrl.records.main.workflow_name,
          format: 'example'
        }]
      }).then(function(resp) {
        console.log('resp',resp);
        if ((!resp.examples || resp.examples.length === 0) && resp.adhoc) {
          resp.examples = [{
            name: 'auto',
            title: ts('Empty example'),
            workflow: $ctrl.records.main.workflow_name,
            data: {modelProps: resp.adhoc[0]}
          }];
        }
        defaults.exampleName = resp.examples.length > 0  ? (resp.examples)[0].name : null;
        var i = 0;
        angular.forEach(resp.examples, function(ex) {
          ex.id = i++;
        });
        defaults.examples = resp.examples;

        var model = angular.extend({}, defaults, args);
        var options = CRM.utils.adjustDialogDefaults({
          dialogClass: 'msgtplui-dialog',
          autoOpen: false,
          height: '90%',
          width: '90%'
        });
        return dialogService.open('previewMsgDlg', '~/msgtplui/Preview.html', model, options)
          // Nothing to do but hide warnings. The field was edited live.
          .then(function(){}, function(){});
      }, function(failure) {
        // handle failure
      });

    }
    $rootScope.$on('previewMsgTpl', onPreview);
    $rootScope.$on('$destroy', function (){
      $rootScope.$off('previewMsgTpl', onPreview);
    });
  });

})(angular, CRM.$, CRM._);
