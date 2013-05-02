{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
{* success.tpl: Display page for Upgrades. Provides complete HTML doc.*}
{if $config->userSystem->is_drupal neq '1'}
    <h2>{$pageTitle}</h2>
{/if}
{if !$upgraded}
  <div class="crm-container" style="margin-top: 2em; padding: 1em; background-color: #FFFFE3; border: 1px #F8FF00 solid; color: black;">
    <form method="post">
        <p>
          <span class="crm-status-icon info"> </span>
          {ts 1=$currentVersion 2=$newVersion}Use this utility to upgrade your CiviCRM database from %1 to %2.{/ts}
        </p>
        {if $preUpgradeMessage}
            <div style="background-color: #E43D2B; padding: 10px;"><strong>{ts}Warning:{/ts}&nbsp;</strong>{$preUpgradeMessage}</div>
        {/if}
        <p><strong>{ts}Back up your database before continuing.{/ts}</strong>
            {capture assign=docLink}{docURL page="Installation and Upgrades" text="Upgrade Documentation" style="text-decoration: underline;" resource="wiki"}{/capture}
            {ts 1=$docLink}This process may change your database structure and values. In case of emergency you may need to revert to a backup. For more detailed information, refer to the %1.{/ts}</p>
        <p>{ts}Click 'Upgrade Now' if you are ready to proceed. Otherwise click 'Cancel' to return to the CiviCRM home page.{/ts}</p>
        <input type="hidden" name="action" value="begin" />
        <input type="submit" value="{ts}Upgrade Now{/ts}" name="upgrade" onclick="return confirm('{ts}Are you sure you are ready to upgrade now?{/ts}');" /> &nbsp;&nbsp;
        <input type="button" value="{ts}Cancel{/ts}" onclick="window.location='{$cancelURL}';" />
    </form>
  </div>

{else}
    <div class="crm-container" style="margin-top: 2em; padding: 1em; background-color: #EEFFEE; border: 1px #070 solid; color: black;">
      <p><span class="crm-status-icon success"> </span><strong>{$message}</strong></p>
      <div style="padding: 1em; background-color: rgba(255, 255, 255, 0.76);">
        <p>
          <img style="display:block; float:left; width:40px; margin-right:10px;" src="{$config->resourceBase}i/logo_lg.png">
          {ts 1='http://civicrm.org/civicrm/profile/create?reset=1&gid=15'}
          This release was made possible by contributions from people like <strong>you</strong>. <a href="%1" target="_blank">Register your site</a> to join the community.
          {/ts}
        </p>
        <p>
          {ts 1='http://civicrm.org/contribute' 2='http://civicrm.org/make-it-happen'}
          If CiviCRM is useful to your organization, consider making a <a href="%1" target="_blank">monthly contribution</a>, or helping to <a href="%2" target="_blank">fund a proposed improvement</a>.
          {/ts}
        </p>
      </div>
      {if $afterUpgradeMessage}
        <h3>Important Notes</h3>
        <p>{$afterUpgradeMessage}</p>
      {/if}
      <p><a href="{$menuRebuildURL}" title="{ts}CiviCRM home page{/ts}" style="text-decoration: underline;">{ts}Return to CiviCRM home page.{/ts}</a></p>
    </div>
{/if}
