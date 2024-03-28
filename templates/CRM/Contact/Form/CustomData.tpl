{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for building tabbed custom data *}
{if $cdType || $postedInfo}
   {include file="CRM/Custom/Form/CustomData.tpl"}
   {if $multiRecordDisplay eq 'single'}
     <div class="crm-submit-buttons">{$form.buttons.html}</div>
   {/if}
{else}
    <div id="customData_{$contact_type}" class="crm-customData-block"></div>
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
