{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
{strip}
<fieldset>
<div id="help" class="messages help">
    <div class="icon info-icon"></div>&nbsp;
    {ts}Custom searches are developed and contributed by members of the CiviCRM community.{/ts} {help id="id-custom-searches"}
</div>
{if $rows}
    {foreach from=$rows item=customTitle key=csid}
        <div class="action-link">
            <a href="{crmURL p="civicrm/contact/search/custom" q="csid=`$csid`&reset=1"}" title="{ts}Use this search{/ts}">&raquo; {$customTitle}</a>
        </div>
    {/foreach}
{else}
    {capture assign=infoTitle}{ts}There are currently no Custom Searches{/ts}{/capture}
    {include file="CRM/common/info.tpl" infoType="info" infoMessage=""}
{/if}
</fieldset>
{/strip}
