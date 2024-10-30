<div class="messages help">
    {capture assign='adminURL'}{crmURL p='civicrm/admin/setting/path' q="reset=1&civicrmDestination=$destination"}{/capture}
    <p>{ts 1=$adminURL 2="https://civicrm.org/extensions"}CiviCRM extensions allow you to install additional features for your site. This page lists the stable and reviewed extensions from the <a href="%2" target="_blank">CiviCRM.org extensions directory</a> which are compatible with this version of CiviCRM.{/ts} {ts}Reviewed extensions have gone through a manual review and met certain criteria in the hope that they are actively maintained and keep working in the future.{/ts} {docURL page="dev/extensions/lifecycle"}</p>
    {if $config->userFramework != "Standalone"}
      {ts 1=$config->userFramework|replace:'6':'' 2="https://civicrm.org/extensions"}<p>You may also want to check the directory for <a href="%2/%1" target="_blank">native %1 modules</a> that may be useful for you (CMS-specific modules are not listed here).{/ts}</p>
    {/if}
</div>
