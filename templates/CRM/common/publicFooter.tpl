{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{if $config->empoweredBy}
  {capture assign=civilogo}<a href="https://civicrm.org/" title="{ts escape='htmlattribute'}CiviCRM.org - Growing and Sustaining Relationships{/ts}" target="_blank" class="empowered-by-link"><div class="empowered-by-logo"><span>CiviCRM</span></div></a>{/capture}
  <div class="crm-public-footer" id="civicrm-footer">
    {ts 1=$civilogo}empowered by %1{/ts}
  </div>
{/if}
