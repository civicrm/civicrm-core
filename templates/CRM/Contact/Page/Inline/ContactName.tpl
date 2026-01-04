{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div id="crm-contactname-content" {if $permission EQ 'edit'}class="crm-inline-edit" data-edit-params='{ldelim}"cid": "{$contactId}", "class_name": "CRM_Contact_Form_Inline_ContactName"{rdelim}' data-dependent-fields='["#crm-communication-pref-content"]'{/if}>
  {crmRegion name="contact-page-contactname"}
    <div class="crm-inline-block-content"{if $permission EQ 'edit'} title="{ts escape='htmlattribute'}Edit Contact Name{/ts}"{/if}>
      {if $permission EQ 'edit'}
        <div class="crm-edit-help">
          <span class="crm-i fa-pencil" role="img" aria-hidden="true"></span> {ts}Edit name{/ts}
        </div>
      {/if}

      <div class="crm-summary-display_name">
        {$title}{if $domainContact} ({ts}default organization{/ts}){/if}
      </div>
    </div>
  {/crmRegion}
</div>
