(function(angular, $, _) {

  /**
   * Convert keys with literal dots to Javascript subtrees.
   *
   * @param rec
   *   Ex: {'foo.bar.whiz:bang': 123}
   * @returns {{}}
   *   Ex: {foo_bar_whiz_bang: 123}
   */
  function simpleKeys(rec) {
    var newRec = {};
    angular.forEach(rec, function(value, key) {
      newRec[key.replaceAll('.','_').replaceAll(':', '_')] = value;
    });
    return newRec;
  }

  angular.module('crmMsgadm').controller('MsgtpluiListCtrl', function($scope, $route, crmApi4, crmStatus, crmUiAlert, crmUiHelp, prefetch, $location, dialogService) {
    var ts = $scope.ts = CRM.ts('crmMsgadm');
    var hs = $scope.hs = crmUiHelp({file: 'CRM/MessageAdmin/User'}); // See: templates/CRM/MessageAdmin/User.hlp
    $scope.crmUrl = CRM.url;
    $scope.crmUiAlert = crmUiAlert;
    $scope.location = $location;
    $scope.checkPerm = CRM.checkPerm;
    $scope.help = CRM.help;

    $scope.$bindToRoute({
      param: 'f',
      expr: 'filters',
      default: {text: ''}
    });

    var $ctrl = this;
    $ctrl.records = _.map(
      [].concat(prefetch.records, _.map(prefetch.translations || [], simpleKeys)),
      function(r) {
        r._is_translation = (r.tx_language !== undefined);
        return r;
      }
    );

    function findTranslations(record) {
      return _.reduce($ctrl.records, function(existing, rec){
        if (rec._is_translation && record.id === rec.id) {
          existing[rec.tx_language] = record;
        }
        return existing;
      }, {});
    }

    function findActiveLangs() {
      return _.reduce($ctrl.records, function(langs, rec){
        if (rec._is_translation) {
          langs[rec.tx_language] = true;
        }
        return langs;
      }, {});
    }

    /**
     *
     * @param record
     * @param variant - One of null 'legacy', 'current', 'draft'. (If null, then 'current'.)
     * @returns {string}
     */
    $ctrl.editUrl = function(record, variant) {
      if (variant === 'legacy') {
        return CRM.url('civicrm/admin/messageTemplates/add', {action: 'update', id: record.id, reset: 1});
      }
      var url = '#/edit?id=' + encodeURIComponent(record.id);
      if (record.tx_language) {
        url = url + '&lang=' + encodeURIComponent(record.tx_language);
      }
      if (variant === 'draft') {
        url = url + '&status=draft';
      }
      return url;
    };

    $ctrl.addTranslation = function(record) {
      var existing = findTranslations(record), activeLangs = findActiveLangs();
      var langs = [];
      angular.forEach(CRM.crmMsgadm.allLanguages, function (label, value) {
        langs.push({
          name: value,
          label: label,
          is_allowed: !existing[value],
          is_encouraged: !!(activeLangs[value] || CRM.crmMsgadm.uiLanguages[value])
        });
      });
      var model = {
        msgtpl: record,
        selected: (_.head(_.filter(langs, {is_allowed: true, is_encouraged: true}))||{}).name,
        selectedOther: '',
        langs: langs
      };
      var options = CRM.utils.adjustDialogDefaults({
        autoOpen: false,
        height: '50%',
        width: '50%',
        title: ts('Add Translation')
      });
      return dialogService.open('addTranslationDlg', '~/crmMsgadm/AddTranslation.html', model, options)
        .then(function(){
          var selection = (model.selected === 'other') ? model.selectedOther : model.selected;
          window.location = $ctrl.editUrl({id: record.id, tx_language: selection});
        });
    };

    $ctrl.delete = function (record) {
      var q = crmApi4('MessageTemplate', 'delete', {where: [['id', '=', record.id]]}).then(function(){
        $route.reload();
      });
      return crmStatus({start: ts('Deleting...'), success: ts('Deleted')}, q);
    };

    $ctrl.toggle = function (record) {
      var wasActive = !!record.is_active;
      var q = crmApi4('MessageTemplate', 'update', {where: [['id', '=', record.id]], values: {is_active: !wasActive}})
        .then(function(resp){
          record.is_active = !wasActive;
        });
      return wasActive ? crmStatus({start: ts('Disabling...'), success: ts('Disabled')}, q)
        : crmStatus({start: ts('Enabling...'), success: ts('Enabled')}, q);
    };

  });

})(angular, CRM.$, CRM._);
