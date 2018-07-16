{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{* this template is used for building tabbed custom data *}
{if $cdType || $postedInfo}
   {include file="CRM/Custom/Form/CustomData.tpl"}
   {if $multiRecordDisplay eq 'single'}
     <div class="crm-submit-buttons">{$form.buttons.html}</div>
   {/if}
{else}
    <div id="customData"></div>
    <div class="crm-submit-buttons">{$form.buttons.html}</div>

    {*include custom data js file*}
    {include file="CRM/common/customData.tpl"}

  {if $customValueCount}
    {literal}
    <script type="text/javascript">
      CRM.$(function() {
        {/literal}
        var customValueCount = {$customValueCount|@json_encode},
          groupID = {$groupID|@json_encode},
          contact_type = {$contact_type|@json_encode},
          contact_subtype = {$contact_subtype|@json_encode},
          i = 1;
        {literal}
        // FIXME: This is pretty terrible. Loading each item at a time via ajax.
        // Building the complete form in php with no ajax would be way more efficient.
        function loadNextRecord() {
          if (i < customValueCount) {
            CRM.buildCustomData(contact_type, contact_subtype, null, i++, groupID, true).one('crmLoad', loadNextRecord);
          }
        }
        CRM.buildCustomData(contact_type, contact_subtype).one('crmLoad', loadNextRecord);
      });
    </script>
    {/literal}
  {/if}
  {include file="CRM/Form/attachmentjs.tpl"}
{/if}

