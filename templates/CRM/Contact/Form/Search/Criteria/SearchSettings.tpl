<div id="search-settings" class="form-item">
  <table>
    <tr>
      <td>{$form.operator.label} {help id="id-search-operator"}<br />{$form.operator.html}</td>
      <td>
        {if !empty($form.deleted_contacts)}{$form.deleted_contacts.html} {$form.deleted_contacts.label}{/if}
      </td>
      <td class="adv-search-top-submit" colspan="2">
          {include file="CRM/common/formButtons.tpl" location="top"}
        <div class="crm-submit-buttons reset-advanced-search">
          <a href="{crmURL p='civicrm/contact/search/advanced' q='reset=1'}" id="resetAdvancedSearch" class="crm-hover-button" title="{ts}Clear all search criteria{/ts}">
            <i class="crm-i fa-undo" aria-hidden="true"></i>
            &nbsp;{ts}Reset Form{/ts}
          </a>
        </div>
      </td>
    </tr>
  </table>
</div>
