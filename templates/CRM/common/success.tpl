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
    <div style="margin-top: 2em; padding: 1em; background-color: #0C0; border: 1px #070 solid; color: white; font-weight: normal">
    <form method="post">
        <p>{ts 1=$currentVersion 2=$newVersion}Use this utility to upgrade your CiviCRM database from %1 to %2.{/ts}</p>
        {if $preUpgradeMessage}
            <div style="background-color: #E43D2B; padding: 10px;"><strong>{ts}Warning:{/ts}&nbsp;</strong>{$preUpgradeMessage}</div>
        {/if}
        <p><strong>{ts}Back up your database before continuing.{/ts}</strong>
            {capture assign=docLink}{docURL page="Installation and Upgrades" text="Upgrade Documentation" style="color: white; text-decoration: underline;" resource="wiki"}{/capture}
            {ts 1=$docLink}This process may change your database structure and values. In case of emergency you may need to revert to a backup. For more detailed information, refer to the %1.{/ts}</p>
        <p>{ts}Click 'Upgrade Now' if you are ready to proceed. Otherwise click 'Cancel' to return to the CiviCRM home page.{/ts}</p>
        <input type="hidden" name="action" value="begin" />
        <input type="submit" value="{ts}Upgrade Now{/ts}" name="upgrade" onclick="return confirm('{ts}Are you sure you are ready to upgrade now?{/ts}');" /> &nbsp;&nbsp;
        <input type="button" value="{ts}Cancel{/ts}" onclick="window.location='{$cancelURL}';" />
    </form>
    </div>

{else}
    <div style="margin-top: 3em; padding: 1em; background-color: #0C0; border: 1px #070 solid; color: white; font-weight: bold">
        <p>{$message}</p>
        <p>{$afterUpgradeMessage}</p>
        <p><a href="{$menuRebuildURL}" title="{ts}CiviCRM home page{/ts}" style="color: white; text-decoration: underline;">{ts}Return to CiviCRM home page.{/ts}</a></p>
      <div class="upgrade-success">
        <table>
          <tr><td class="tasklist">
            {ts 1='http://civicrm.org/civicrm/profile/create?reset=1&gid=15'}Have you registered this site at CiviCRM.org? If not, please help strengthen the CiviCRM ecosystem by taking a few minutes to <a href="%1" target="_blank">fill out the site registration form</a>. The information collected will help us prioritize improvements, target our communications and build the community. If you have a technical role for this site, be sure to check "Keep in Touch" to receive technical updates (a low volume mailing list).{/ts}</td>
          </tr>
        </table>
      </div>
    </div>
{/if}
