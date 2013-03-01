<div class="messages help">
    {capture assign='adminURL'}{crmURL p='civicrm/admin/setting/path' q="reset=1&civicrmDestination=$destination"}{/capture}
    <p>{ts 1=$adminURL 2="http://civicrm.org/extensions"}CiviCRM extensions allow you to install additional features for your site. This page will automatically list the available "native" extensions from the <a href="%2" target="_blank">CiviCRM.org extensions directory</a> which are compatible with this version of CiviCRM. If you install Custom Searches, Reports or Payment Processor extensions - these will automatically be available on the corresponding menus and screens.{/ts}</p>
    {ts 1=$config->userFramework|replace:'6':'' 2="http://civicrm.org/extensions"}<p>You may also want to check the directory for <a href="%2/%1" target="_blank">native %1 modules</a> that may be useful for you (CMS-specific modules are not listed here).{/ts}</p>
</div>
