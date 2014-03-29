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
{* this template is used for building tabbed custom data *}
{if $cdType || $postedInfo}
   {include file="CRM/Custom/Form/CustomData.tpl"}
   {if $multiRecordDisplay eq 'single'}
     <div class="html-adjust">{$form.buttons.html}</div>
   {/if}
{else}
    <div id="customData"></div>
    <div class="html-adjust">{$form.buttons.html}</div>

    {*include custom data js file*}
    {include file="CRM/common/customData.tpl"}

  {if $customValueCount}
    {literal}
    <script type="text/javascript">
      var customValueCount = {/literal}"{$customValueCount}"{literal};
      var groupID = {/literal}"{$groupID}"{literal};
      var contact_type = {/literal}"{$contact_type}"{literal};
      var contact_subtype = {/literal}"{$contact_subtype}"{literal};
      CRM.buildCustomData( contact_type, contact_subtype );
      for ( var i = 1; i < customValueCount; i++ ) {
        CRM.buildCustomData( contact_type, contact_subtype, null, i, groupID, true );
      }
    </script>
    {/literal}
  {/if}
{/if}

{include file="CRM/Form/attachmentjs.tpl"}
