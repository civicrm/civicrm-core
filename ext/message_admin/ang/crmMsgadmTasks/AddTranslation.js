(function(angular, $, _) {
  "use strict";

  angular.module('crmMsgadmTasks').controller('crmMsgadmAddTranslation', function($scope, crmApi4, searchTaskBaseTrait) {
    const ts = $scope.ts = CRM.ts('message_admin');
    // Combine this controller with model properties (ids, entity, entityInfo) and searchTaskBaseTrait
    angular.extend(this, $scope.model, searchTaskBaseTrait);

    // The task declares number '=== 1', so there is exactly one.
    const templateId = this.ids[0];

    this.langs = [];
    this.selected = null;
    this.selectedOther = '';

    crmApi4({
      template: ['MessageTemplate', 'get', {select: ['msg_title'], where: [['id', '=', templateId]]}],
      existing: ['Translation', 'get', {
        select: ['language'],
        where: [['entity_table', '=', 'civicrm_msg_template'], ['entity_id', '=', templateId]],
        groupBy: ['language']
      }],
      // Languages already translated anywhere on this site, which are the ones worth offering first.
      inUse: ['Translation', 'get', {
        select: ['language'],
        where: [['entity_table', '=', 'civicrm_msg_template']],
        groupBy: ['language']
      }]
    }).then((result) => {
      const taken = result.existing.map((row) => row.language),
        inUse = result.inUse.map((row) => row.language);
      this.msgTitle = (result.template[0] || {}).msg_title;
      this.langs = Object.keys(CRM.crmMsgadmTasks.allLanguages).map((name) => ({
        name: name,
        label: CRM.crmMsgadmTasks.allLanguages[name],
        is_allowed: !taken.includes(name),
        is_encouraged: inUse.includes(name) || (name in CRM.crmMsgadmTasks.uiLanguages)
      }));
      this.selected = (this.langs.filter((lang) => lang.is_allowed && lang.is_encouraged)[0] || {}).name;
    });

    // The editor creates the translation; this dialog only chooses which language to open it for.
    this.add = () => {
      const language = (this.selected === 'other') ? this.selectedOther : this.selected;
      window.location = CRM.url('civicrm/admin/messageTemplates/edit', {reset: 1}) +
        '#/edit?id=' + encodeURIComponent(templateId) + '&lang=' + encodeURIComponent(language);
    };

  });

})(angular, CRM.$, CRM._);
