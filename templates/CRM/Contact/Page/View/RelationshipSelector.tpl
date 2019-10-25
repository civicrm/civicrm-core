{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
{* entity selector *}
{crmRegion name="crm-contact-relationshipselector-pre"}
{/crmRegion}
<div class="crm-contact-{$entityInClassFormat}-{$context}">
  <table
    class="crm-contact-{$entityInClassFormat}-selector-{$context} crm-ajax-table"
    data-ajax="{crmURL p="civicrm/ajax/contactrelationships" q="context=$context&cid=$contactId"}"
    data-order='[[0,"asc"],[1,"asc"]]'
    style="width: 100%;">
    <thead>
    <tr>
      {foreach from=$columnHeaders key=headerkey item=header}
        {if $header.sort}
          <th data-data="{$header.sort}" class="crm-contact-{$entityInClassFormat}-{$header.sort}">{$header.name}</th>
        {else}
          <th data-data="{$headerkey}" data-orderable="false" class="crm-contact-{$entityInClassFormat}-{$headerkey}">{$header.name}</th>
        {/if}

      {/foreach}
    </tr>
    </thead>
  </table>
</div>
{crmRegion name="crm-contact-relationshipselector-post"}
{/crmRegion}
