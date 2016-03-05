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
{* template for custom data *}
{assign var="customDataGroupName" value=$customDataGroup.name}
{strip}
  {if $displayStyle neq 'tableOriented' and ($action eq 16 or $action eq 4)} {* Browse or View actions *}
    <div class="form-item">
      {include file="CRM/Custom/Page/CustomDataView.tpl"}
    </div>
  {/if}
{/strip}
{foreach from=$viewCustomData item=customGroupWrapper}
  {foreach from=$customGroupWrapper item=customGroup key=customGroupId}
    {assign var="customRegion" value='contact-custom-data-'|cat:$customGroup.name}
    {crmRegion name=$customRegion}
      {if $customGroup.help_pre}
        <div class="messages help">{$customGroup.help_pre}</div>
      {/if}
      {if $action eq 0 or $action eq 1 or $action eq 2 or $recordActivity}
        {include file="CRM/Contact/Form/CustomData.tpl" mainEdit=$mainEditForm}
      {/if}
      {if $displayStyle eq 'tableOriented'}
        {include file='CRM/Profile/Page/MultipleRecordFieldsListing.tpl' showListing=1 dontShowTitle=1 pageViewType='customDataView'}
        {literal}
          <script type="text/javascript">
            CRM.$(function($) {
              var $table = $("#{/literal}custom-{$customGroupId}-table-wrapper{literal}");
              $('a.delete-custom-row', $table).on('click', function(e) {
                deleteRow($(this));
                e.preventDefault();
              });
              $(".crm-multifield-selector").on('click', '.delete-custom-row', function (e) {
                deleteRow($(this));
                e.preventDefault();
              });

              function deleteRow($el) {
                CRM.confirm({
                  message: '{/literal}{ts escape='js'}Are you sure you want to delete this record?{/ts}{literal}'
                }).on('crmConfirm:yes', function() {
                  var postUrl = {/literal}"{crmURL p='civicrm/ajax/customvalue' h=0 }"{literal};
                  var request = $.post(postUrl, $el.data('delete_params'));
                  CRM.status({/literal}"{ts escape='js'}Record Deleted{/ts}"{literal}, request);
                  request.done(function() {
                    CRM.refreshParent($el);
                  });
                })
              }
            });
          </script>
        {/literal}
      {else}
        {if $mainEditForm}
          <script type="text/javascript">
            var showBlocks1 = new Array({$showBlocks1});
            var hideBlocks1 = new Array({$hideBlocks1});

            on_load_init_blocks(showBlocks1, hideBlocks1);
          </script>
        {else}
          <script type="text/javascript">
            var showBlocks = new Array({$showBlocks});
            var hideBlocks = new Array({$hideBlocks});

            {* hide and display the appropriate blocks as directed by the php code *}
            on_load_init_blocks(showBlocks, hideBlocks);
          </script>
        {/if}
      {/if}
      {if $customGroup.help_post}
        <div class="messages help">{$customGroup.help_post}</div>
      {/if}
    {/crmRegion}
  {/foreach}
{/foreach}
