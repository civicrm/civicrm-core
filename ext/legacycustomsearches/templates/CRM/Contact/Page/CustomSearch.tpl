{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{strip}
<fieldset>
<div id="help" class="messages help">
    <div class="icon info-icon"></div>&nbsp;
    {ts}Custom searches are developed and contributed by members of the CiviCRM community.{/ts} {help id="id-custom-searches"}
</div>
{if $rows}
    {foreach from=$rows item=customTitle key=csid}
        <div class="action-link">
            <a href="{crmURL p="civicrm/contact/search/custom" q="csid=`$csid`&reset=1"}" title="{ts escape='htmlattribute'}Use this search{/ts}"><i class="crm-i fa-chevron-right" aria-hidden="true"></i> {$customTitle}</a>
        </div>
    {/foreach}
{else}
    {capture assign=infoTitle}{ts}There are currently no Custom Searches{/ts}{/capture}
    {include file="CRM/common/info.tpl" infoType="info" infoMessage=""}
{/if}
</fieldset>
{/strip}
