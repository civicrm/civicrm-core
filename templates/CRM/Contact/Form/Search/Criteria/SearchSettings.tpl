<div id="search-settings" class="form-item">
  <table>
    <tr>
      <td>{$form.operator.label} {help id="id-search-operator"}<br />{$form.operator.html}</td>
      <td>
        {if $form.deleted_contacts}{$form.deleted_contacts.html} {$form.deleted_contacts.label}{/if}
      </td>
      <td class="adv-search-top-submit" colspan="2">
        <div class="crm-submit-buttons">
          {include file="CRM/common/formButtons.tpl" location="top"}
        </div>
        <div class="crm-submit-buttons reset-advanced-search">
          <a href="{crmURL p='civicrm/contact/search/advanced' q='reset=1'}" id="resetAdvancedSearch" class="crm-hover-button css_right" title="{ts}Clear all search criteria{/ts}">
            <i class="crm-i fa-undo"></i>
            {ts}Reset Form{/ts}
          </a>
        </div>
      </td>
    </tr>
  </table>
</div>
