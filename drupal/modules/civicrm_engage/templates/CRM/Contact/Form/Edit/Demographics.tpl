{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-accordion-wrapper crm-demographics-accordion collapsed">
 <div class="crm-accordion-header">
    {$title}
  </div><!-- /.crm-accordion-header -->
  <div id="demographics" class="crm-accordion-body">
  <div class="form-item">
        <span class="label">{$form.gender_id.label}</span>

  <span class="value">
        {$form.gender_id.html}
        </span>
  </div>
  <div class="form-item">
        <span class="label">{$form.birth_date.label}</span>
        <span class="fields">{$form.birth_date.html}</span>
  </div>
  <div class="form-item">
       {$form.is_deceased.html}
       {$form.is_deceased.label}
  </div>
  <div id="showDeceasedDate" class="form-item">
       <span class="label">{$form.deceased_date.label}</span>
       <span class="fields">{$form.deceased_date.html}</span>
  </div>
  {if isset($demographics_groupTree)}{foreach from=$demographics_groupTree item=cd_edit key=group_id}
     {foreach from=$cd_edit.fields item=element key=field_id}
        <table class="form-layout-compressed">
        {include file="CRM/Custom/Form/CustomField.tpl"}
        </table>
     {/foreach}
  {/foreach}{/if}
 </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->

{literal}
<script type="text/javascript">
    showDeceasedDate( );
    function showDeceasedDate( )
    {
        if ( cj("#is_deceased").is(':checked') ) {
            cj("#showDeceasedDate").show( );
        } else {
    cj("#showDeceasedDate").hide( );
         cj("#deceased_date").val('');
        }
    }
</script>
{/literal}
