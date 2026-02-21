{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Included in Custom/Form/Field.tpl - used for fields with multiple choice options. *}
<tr>
  <td class="label">{$form.option_type.label} {help id="option_type" file="CRM/Custom/Form/Field"}</td>
  <td class="html-adjust">{$form.option_type.html}</td>
</tr>

<tr id="option_group" {if empty($form.option_group_id)}class="hiddenElement"{/if}>
  <td class="label">{$form.option_group_id.label}</td>
  <td class="html-adjust">{$form.option_group_id.html}</td>
</tr>

<tr id="multiple">
<td colspan="2" class="html-adjust">
  <fieldset>
    <legend>{ts}Multiple Choice Options{/ts}</legend>
    <crm-options-repeat>
      {$form.option_values.html|crmReplace:'type':'hidden'}
      <table>
        <thead>
          <tr>
            <th></th>
            <th>{ts}Default{/ts}</th>
            <th>
              {ts}Label{/ts}
              <a class="crm-hover-button crm-options-repeat-sort" title="{ts escape='html'}Sort by label{/ts}">
                <i class="crm-i fa-sort-alpha-down" aria-hidden="true" role="img"></i>
                <span class="sr-only">{ts}Sort by label{/ts}</span>
              </a>
            </th>

            <th>
              {ts}Value{/ts}
              <a class="crm-hover-button crm-options-repeat-sort" title="{ts escape='html'}Sort by value{/ts}">
                <i class="crm-i fa-sort-numeric-down" aria-hidden="true" role="img"></i>
                <span class="sr-only">{ts}Sort by value{/ts}</span>
              </a>
            </th>
            <th>{ts}Enabled{/ts}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <a class="crm-draggable">
                <i class="crm-i fa-arrows-up-down" role="img" aria-hidden="true"></i>
                <span class="sr-only">{ts}Change order{/ts}</span>
              </a>
            </td>
            <td><input type="radio" name="is_default" class="crm-form-radio"></td>
            <td><input type="text" name="label" class="crm-form-text required" required></td>
            <td><input type="text" name="value" class="crm-form-text required" required value="1"></td>
            <td><input type="checkbox" name="is_active" class="crm-form-checkbox" checked></td>
            <td>
              <a class="crm-hover-button crm-options-repeat-remove" title="{ts escape='html'}Delete{/ts}">
                <i class="crm-i fa-trash" role="img" aria-hidden="true"></i>
                <span class="sr-only">{ts}Delete{/ts}</span>
              </a>
            </td>
          </tr>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="6">
              <button type="button" class="crm-options-repeat-add">
                <i class="crm-i fa-plus" role="img" aria-hidden="true"></i>
                {ts}Add Option{/ts}
              </button>
            </td>
          </tr>
        </tfoot>
      </table>
    </crm-options-repeat>
  </fieldset>
</td>
</tr>
<script type="text/javascript">

{if !empty($form.option_group_id)}
{literal}
  CRM.$(function($) {
    const $form = $('form.{/literal}{$form.formClass}{literal}');

    function showOptionSelect() {
      const createNewOptions = $('[name="option_type"]:checked', $form).val() === '1';
      $('#multiple').toggle(createNewOptions);
      $('#option_group').toggle(!createNewOptions);
    }

    $('[name="option_type"]', $form).on('change', showOptionSelect);
    showOptionSelect();
  });
{/literal}
{/if}
</script>


