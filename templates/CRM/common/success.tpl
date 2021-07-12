{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Display page for Upgrades. *}
{if $config->userSystem->is_drupal neq '1'}
    <h2>{$pageTitle}</h2>
{/if}
{if !$upgraded}
  <div class="crm-container" style="margin-top: 2em; padding: 1em; background-color: #FFFFE3; border: 1px #F8FF00 solid; color: black;">
    <form method="post">
        <p>
          <span class="crm-status-icon info"> </span>
          {ts 1=$currentVersion 2=$newVersion}The database will be upgraded from %1 to %2.{/ts}
        </p>
        {if $preUpgradeMessage}
            <div style="border: 2px solid #E43D2B; background-color: rgba(228, 61, 43, 0.08); padding: 10px; margin-bottom: 15px;">
              <span class="crm-status-icon"></span>
              <strong style="vertical-align: middle; font-size: 1.2em;">{ts}Warning:{/ts}</strong>
              {$preUpgradeMessage}
            </div>
        {/if}
        <p><strong>{ts}Back up your database before continuing.{/ts}</strong>
            {ts}This process may change your database structure and values. In case of emergency you may need to revert to a backup.{/ts} {docURL page="sysadmin/upgrade"}</p>
        <input type="hidden" name="action" value="begin" />
        <button type="submit" class="crm-button" name="upgrade" onclick="return confirm('{ts escape="js"}Are you sure you are ready to upgrade now?{/ts}');" >
          <i class="crm-i fa-rocket" aria-hidden="true"></i>
          {ts}Upgrade Now{/ts}
        </button>&nbsp;&nbsp;
        <a class="button cancel crm-form-submit" href="{$cancelURL}">
          <i class="crm-i fa-times" aria-hidden="true"></i>
          {ts}Cancel{/ts}
        </a>
    </form>
  </div>

{else}
    <div class="crm-container" style="margin-top: 2em; padding: 1em; background-color: #EEFFEE; border: 1px #070 solid; color: black;">
      <div class="bold" style="padding: 1em; background-color: rgba(255, 255, 255, 0.76);">
        <p>
          <img style="display:block; float:left; width:40px; margin-right:10px;" src="{$config->resourceBase}i/logo_lg.png">
          {ts 1="https://civicrm.org/core-team" 2="https://civicrm.org/contributors" 3="https://civicrm.org/members" 4="https://civicrm.org/partners" 5="https://civicrm.org/become-a-member?src=ug&sid=$sid" 6=$newVersion 7="https://civicrm.org/become-a-partner" 8="https://civicrm.org"}Thank you for upgrading to %6. This release was made possible by the <a href="%1" target="_blank">CiviCRM Core Team</a> and an <a href="%2" target="_blank">incredible group of CiviCRM Contributors</a>. The CiviCRM project could not exist without the continued financial support of <a href="%3" target="_blank">CiviCRM Members</a> and <a href="%4" target="_blank">CiviCRM Partners</a>. We invite you to support CiviCRM by becoming a <a href="%5" target="_blank">CiviCRM Member</a> or <a href="%7" target="_blank">CiviCRM Partner</a> today. Providing financial support ensures that the <a href="%8" target="_blank">CiviCRM project</a> can continue to provide the essential resources and services to support the <a href="%8" target="_blank">CiviCRM community</a>.{/ts}
        </p>
      </div>
      <p><span class="crm-status-icon success"> </span>{$message}</p>
      {if !empty($afterUpgradeMessage)}
        <h3>{ts}Important Notes{/ts}</h3>
        <p>{$afterUpgradeMessage}</p>
      {/if}
      <p><a href="{crmURL p='civicrm/dashboard' q='reset=1'}" title="{ts}CiviCRM home page{/ts}" style="text-decoration: underline;">{ts}Return to CiviCRM home page.{/ts}</a></p>
    </div>
{/if}
