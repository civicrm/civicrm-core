<crm-angular-js modules="afformStandalone">
  <form id="bootstrap-theme" ng-controller="AfformStandalonePageCtrl">
    {literal}
      <h1 style="display: none" crm-page-title ng-if="afformTitle">{{ afformTitle }}</h1>
      <div ng-if="!afformDisplayForm">
        {{ afformConfirmationMessage }}
      </div>
    {/literal}
    <{$directive} {literal} ng-if="afformDisplayForm" {/literal}></{$directive}>
 </form>
</crm-angular-js>
