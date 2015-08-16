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
{* Custom Data view mode*}
{assign var="customGroupCount" value = 1}
{foreach from=$viewCustomData item=customValues key=customGroupId}
  {assign var="cgcount" value=1}
  {assign var="count" value=$customGroupCount%2}
  {if ($count eq $side) or $skipTitle }
    {foreach from=$customValues item=cd_edit key=cvID}
      <div class="customFieldGroup crm-collapsible{if $cd_edit.collapse_display} collapsed{/if} ui-corner-all {$cd_edit.name} crm-custom-set-block-{$customGroupId}">
        <div class="collapsible-title">
          {$cd_edit.title}
        </div>
        {if $cvID eq 0}
          {assign var='cvID' value='-1'}
        {/if}
        <div class="crm-summary-block" id="custom-set-block-{$customGroupId}-{$cvID}">
          {include file="CRM/Contact/Page/View/CustomDataFieldView.tpl" customGroupId=$customGroupId customRecId=$cvID cgcount=$cgcount}
        </div>
      </div>
      {assign var="cgcount" value=$cgcount+1}
    {/foreach}
  {/if}
  {assign var="customGroupCount" value = $customGroupCount+1}
{/foreach}

