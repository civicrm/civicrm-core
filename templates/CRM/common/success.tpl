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
  <div class="crm-container crm-upgrade-box-outer crm-upgrade-warning">
    <div class="crm-upgrade-box-inner">
      <form method="post">
        {if $preUpgradeMessage}
          <div class="crm-success-flex">
            <div><i class="crm-i fa-warning"></i></span></div>
            <div class="crm-upgrade-large-text"><strong>{ts}Warning{/ts}</strong></div>
          </div>
          {$preUpgradeMessage}
        {/if}
        <p><strong>{ts}Back up your database before continuing.{/ts}</strong>
            {ts}This process may change your database structure and values. In case of emergency you may need to revert to a backup.{/ts} {docURL page="sysadmin/upgrade"}</p>
        <p>{ts 1=$currentVersion 2=$newVersion}The database will be upgraded from %1 to %2.{/ts}</p>
        <input type="hidden" name="action" value="begin" />
        <div class="crm-submit-buttons">
          <button type="submit" class="crm-button crm-form-submit"  name="upgrade" onclick="return confirm('{ts escape="js"}Are you sure you are ready to upgrade now?{/ts}');" >
            <i class="crm-i fa-rocket" aria-hidden="true"></i>
            {ts}Upgrade Now{/ts}
          </button>
        </div>
      </form>
    </div>
  </div>
{else}
    <div class="crm-container crm-upgrade-box-outer crm-upgrade-success">
      <div class="crm-upgrade-box-inner">
         <p class="crm-upgrade-large-text">{ts 1=$newVersion}Thank you for upgrading to %1.{/ts}</p>
         <p class="crm-upgrade-large-text">{ts 1="href='https://civicrm.org/core-team' target='_blank'" 2="href='https://civicrm.org/contributors' target='_blank'" 3="href='https://civicrm.org/civicrm/contribute/transact?reset=1&id=47&src=ug' target='_blank'"}This release was made possible by the <a %1>CiviCRM Core Team</a> and an <a %2>incredible group of CiviCRM Contributors</a>. We are committed to keeping CiviCRM free and open, forever. We depend on your support to help make that happen. <a %3>Support us by making a small donation today</a>.{/ts}</p>
        <div class="crm-success-flex">
          <div style="margin: 0 10px;"><i class="crm-i fa-check" aria-hidden="true"></i></div>
          <div>{$message}</div>
        </div>
        <div>
          <p><a href="{crmURL p='civicrm/a/#/status'}" title="{ts escape='htmlattribute'}CiviCRM Status Check{/ts}" style="text-decoration: underline;">{ts}View the CiviCRM System Status{/ts}</a></p>
          <p><a href="{crmURL p='civicrm/dashboard' q='reset=1'}" title="{ts escape='htmlattribute'}CiviCRM home page{/ts}" style="text-decoration: underline;">{ts}Return to CiviCRM home page.{/ts}</a></p>
        </div>
      </div>
    </div>
{/if}
{literal}
<style>
  /* Temporary inline CSS to be replaced by standard classes if they exist one day */
  .crm-upgrade-box-outer {
    margin-top: 2rem;
    border-radius: 5px;
    color: black;
    font-family: sans-serif;
    font-size: 16px;
  }
  .crm-upgrade-box-outer.crm-upgrade-warning {
    background-color: #FFFFE3;
    border: 1px #F8FF00 solid;
  }
  .crm-upgrade-box-outer.crm-upgrade-success {
    background-color: #EEFFEE;
    border: 1px #44cb7e solid;
  }
  .crm-upgrade-box-inner {
    padding: 1rem;
    background-color: rgba(255, 255, 255, 0.5);
  }
  .crm-success-flex {
    display: flex;
    padding: 1rem;
  }
  .crm-success-flex > div:first-child {
    width: 50px;
  }
  .crm-upgrade-large-text {
    font-size: 120%;
  }
  /* Force link colors because of the success background */
  .crm-container .crm-upgrade-box-inner a,
  .crm-container .crm-upgrade-box-inner a:hover,
  .crm-container .crm-upgrade-box-inner a:active,
  .crm-container .crm-upgrade-box-inner a:visited {
    color: #0071bd;
    text-decoration: underline;
  }
  /* Fixes weird visual on Drupal9 */
  .crm-container summary {
    color: black;
  }
</style>
{/literal}
