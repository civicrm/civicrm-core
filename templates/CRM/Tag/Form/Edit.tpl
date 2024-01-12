{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding/editing a tag (admin)  *}
<div class="crm-block crm-form-block crm-tag-form-block">
  {if $action eq 1 or $action eq 2}
    <table class="form-layout-compressed">
       <tr class="crm-tag-form-block-label">
          <td class="label">{$form.label.label}</td>
          <td>{$form.label.html}</td>
       </tr>
       <tr class="crm-tag-form-block-description">
          <td class="label">{$form.description.label}</td>
          <td>{$form.description.html}</td>
       </tr>
         {if !empty($form.parent_id.html)}
       <tr class="crm-tag-form-block-parent_id">
         <td class="label">{$form.parent_id.label}</td>
         <td>{$form.parent_id.html}</td>
       </tr>
   {/if}
      {if !empty($form.used_for)}
       <tr class="crm-tag-form-block-used_for">
          <td class="label">{$form.used_for.label}</td>
          <td>{$form.used_for.html} <br />
            <span class="description">
              {ts}What types of record(s) can this tag be used for?{/ts}
            </span>
          </td>
        </tr>
      {/if}
      {if !empty($form.color.html)}
        <tr class="crm-tag-form-block-color">
          <td class="label">{$form.color.label}</td>
          <td>{$form.color.html}</td>
        </tr>
      {/if}
        <tr class="crm-tag-form-block-is_reserved">
           <td class="label">{$form.is_reserved.label}</td>
           <td>{$form.is_reserved.html} <br /><span class="description">{ts}Reserved tags can not be deleted. Users with 'administer reserved tags' permission can set or unset the reserved flag. You must uncheck 'Reserved' (and delete any child tags) before you can delete a tag.{/ts}
           </td>
        </tr>
        {if ! $isTagSet} {* Tagsets are not selectable by definition, so exclude this field for tagsets *}
          <tr class="crm-tag-form-block-is_slectable">
             <td class="label">{$form.is_selectable.label}</td>
             <td>{$form.is_selectable.html}<br /><span class="description">{ts}Defines if you can select this tag.{/ts}
             </td>
          </tr>
        {/if}
    </table>
    {else}
        <div class="status">{ts 1=$delName}Are you sure you want to delete <b>%1</b>?{/ts}<br />{ts}This tag will be removed from any currently tagged contacts, and users will no longer be able to assign contacts to this tag.{/ts}</div>
    {/if}
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{literal}
<script type="text/javascript">
  CRM.$(function($) {
    var $form = $('form.{/literal}{$form.formClass}{literal}');
    function toggleUsedFor() {
      var value = $(this).val() && $(this).val() !== '0';
      $('.crm-tag-form-block-used_for', $form).toggle(!value);
      if (value) {
        $('select#used_for', $form).val('').change();
      }
    }
    $('input[name=parent_id]', $form).change(toggleUsedFor).each(toggleUsedFor);
  });
</script>
{/literal}
