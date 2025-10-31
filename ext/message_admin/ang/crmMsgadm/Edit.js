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

  angular.module('crmMsgadm').config(function($routeProvider) {
      $routeProvider.when('/edit', {
        controller: 'MsgtpluiEdit',
        controllerAs: '$ctrl',
        templateUrl: '~/crmMsgadm/Edit.html',

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
          tokenList: function (crmApi) {
            // FIXME: Use an API that provides tokens more attuned to the particular template.
            return crmApi('Mailing', 'gettokens', {
              entity: ['contact', 'mailing'],
              sequential: 1
            }).then((r) => {
              return r.values;
            });
          }
        }
      });
    }
  );

  angular.module('crmMsgadm').controller('MsgtpluiEdit', function($q, $scope, crmApi4, crmBlocker, crmStatus, crmUiAlert, crmUiHelp, $location, prefetch, tokenList, $rootScope, dialogService) {
    var block = $scope.block = crmBlocker();
    var ts = $scope.ts = CRM.ts('crmMsgadm');
    var hs = $scope.hs = crmUiHelp({file: 'CRM/MessageAdmin/Edit'}); // See: templates/CRM/MessageAdmin/Edit.hlp
    var $ctrl = this;
    var args = $location.search();

    $ctrl.locales = CRM.crmMsgadm.allLanguages;
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

    function doSave() {
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
      return crmApi4(requests);
    }

    $ctrl.switchTab = function switchTab(tgt) {
      $ctrl.tab = tgt;
      // Experimenting with action buttons in the tab-bar. This makes the scroll unnecessary.
      // $('html, body').animate({scrollTop: $("a[name=crm-msgadm-tabs]").offset().top - $('#civicrm-menu').height()}, 200);
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
      crmStatus({start: ts('Saving...'), success: ts('Created draft.')}, doSave());
    };
    $ctrl.deleteDraft = function deleteDraft() {
      copyTranslations({}, $ctrl.records.txDraft);
      $ctrl.switchTab('txActive');
      crmStatus({start: ts('Saving...'), success: ts('Abandoned draft.')}, doSave());
    };
    $ctrl.activateDraft = function activateDraft() {
      copyTranslations($ctrl.records.txDraft, $ctrl.records.txActive);
      copyTranslations({}, $ctrl.records.txDraft);
      $ctrl.switchTab('txActive');
      crmStatus({start: ts('Saving...'), success: ts('Activated draft.')}, doSave());
    };
    $ctrl.save = function save() {
      return block(crmStatus({start: ts('Saving...'), success: ts('Saved')}, doSave()));
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
        formatName: 'msg_html',
        formats: [
          {id: 0, name: 'msg_html', label: ts('HTML')},
          {id: 1, name: 'msg_html_raw', label: ts('HTML (Raw)')},
          {id: 2, name: 'msg_text', label: ts('Text')}
        ],
        revisionName: $ctrl.tab,
        revisions: _.reduce(revisionTypes, function(acc, revType){
          if ($ctrl.hasRevType(revType.name)) {
            acc.push(angular.extend({id: acc.length, rec: $ctrl.records[revType.name]}, revType));
          }
          return acc;
        }, []),
        filterData: function(data) {
          data.modelProps.locale = $ctrl.lang;
          return data;
        },
        title: $ctrl.lang ? ts('Preview - %1', {1: $ctrl.locales[$ctrl.lang] || $ctrl.lang}) : ts('Preview')
      };

      crmApi4({
        examples: ['ExampleData', 'get', {
          // FIXME: workflow name
          language: $ctrl.lang,
          where: [["tags", "CONTAINS", "preview"], ["name", "LIKE", "workflow/" + $ctrl.records.main.workflow_name + "/%"]],
          select: ['name', 'title', 'data']
        }],
        adhoc: ['WorkflowMessage', 'getTemplateFields', {
          workflow: $ctrl.records.main.workflow_name,
          format: 'example'
        }]
      }).then(function(resp) {
        if ((!resp.examples || resp.examples.length === 0) && resp.adhoc) {
          // In the future, if Preview dialog allows editing adhoc examples, then we can show the dialog. But for now, it won't work without explicit examples.
          crmUiAlert({
            title: ts('Preview unavailable'),
            text: ts('Generating a preview for this message requires example data. Please talk to a developer about adding example data for "%1".', {1: $ctrl.records.main.workflow_name}),
            type: 'error'
          });
          return;
        }
        defaults.exampleName = resp.examples.length > 0  ? (resp.examples)[0].name : null;
        var i = 0;
        angular.forEach(resp.examples, function(ex) {
          ex.id = i++;
        });
        defaults.examples = resp.examples;

        var model = angular.extend({}, defaults, args);
        var options = CRM.utils.adjustDialogDefaults({
          dialogClass: 'crm-msgadm-dialog',
          autoOpen: false,
          height: '90%',
          width: '90%'
        });
        return dialogService.open('previewMsgDlg', '~/crmMsgadm/Preview.html', model, options)
          // Nothing to do but hide warnings. The field was edited live.
          .then(function(){}, function(){});
      }, function(failure) {
        // handle failure
      });

    }
    var unreg = $rootScope.$on('previewMsgTpl', onPreview);
    $scope.$on('$destroy', function (){
      unreg();
    });
  });

})(angular, CRM.$, CRM._);
