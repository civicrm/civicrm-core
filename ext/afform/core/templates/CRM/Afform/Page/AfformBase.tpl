<crm-angular-js modules="afformStandalone">
  <form id="bootstrap-theme" ng-controller="AfformStandalonePageCtrl">
    <h1 style="display: none" crm-page-title ng-if="afformTitle" initial-document-title="{$afformTitle}" initial-page-title="{$afformTitle}">
      {literal}{{ afformTitle }}{/literal}
    </h1>
    <{$directive}></{$directive}>
  </form>
</crm-angular-js>
