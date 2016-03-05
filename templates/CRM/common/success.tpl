{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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
            {capture assign=docLink}{docURL page="Installation and Upgrades" text="Upgrade Documentation" style="text-decoration: underline;" resource="wiki"}{/capture}
            {ts 1=$docLink}This process may change your database structure and values. In case of emergency you may need to revert to a backup. For more detailed information, refer to the %1.{/ts}</p>
        <input type="hidden" name="action" value="begin" />
        <button type="submit" class="crm-button" name="upgrade" onclick="return confirm('{ts escape="js"}Are you sure you are ready to upgrade now?{/ts}');" >
          <i class="crm-i fa-rocket"></i>
          {ts}Upgrade Now{/ts}
        </button>&nbsp;&nbsp;
        <a class="button cancel crm-form-submit" href="{$cancelURL}">
          <i class="crm-i fa-times"></i>
          {ts}Cancel{/ts}
        </a>
    </form>
  </div>

{else}
    <div class="crm-container" style="margin-top: 2em; padding: 1em; background-color: #EEFFEE; border: 1px #070 solid; color: black;">
      <div class="bold" style="padding: 1em; background-color: rgba(255, 255, 255, 0.76);">
        <p>
          <img style="display:block; float:left; width:40px; margin-right:10px;" src="{$config->resourceBase}i/logo_lg.png">
          {ts 1="https://civicrm.org/core-team" 2="https://civicrm.org/providers/contributors" 3="https://civicrm.org/become-a-member?src=ug&sid=$sid"}Thank you for upgrading to 4.7, the latest version of CiviCRM. Packed with new features and improvements, this release was made possible by both the <a href="%1">CiviCRM Core Team</a> and an incredible group of <a href="%2">contributors</a>, combined with the financial support of CiviCRM Members and Partners, without whom the project could not exist. We invite you to join their ranks by <a href="%3">becoming a member of CiviCRM today</a>. There is no better way to say thanks than to support those that have made CiviCRM 4.7 possible. <a href="%3">Join today</a>.{/ts}
        </p>
      </div>
      <p><span class="crm-status-icon success"> </span>{$message}</p>
      {if $afterUpgradeMessage}
        <h3>{ts}Important Notes{/ts}</h3>
        <p>{$afterUpgradeMessage}</p>
      {/if}
      <p><a href="{crmURL p='civicrm/dashboard' q='reset=1'}" title="{ts}CiviCRM home page{/ts}" style="text-decoration: underline;">{ts}Return to CiviCRM home page.{/ts}</a></p>
    </div>
{/if}
