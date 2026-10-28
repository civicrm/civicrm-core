{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding/editing/deleting financial type  *}
<div class="crm-block crm-form-block crm-financial_type-form-block">
  {if $action eq 8}
    <div class="messages status">
      {icon icon="fa-info-circle"}{/icon}
      {ts}WARNING: You cannot delete a financial type if it is currently used by any Contributions, Contribution Pages or Membership Types. Consider disabling this option instead.{/ts} {ts}Deleting a financial type cannot be undone. Unbalanced transactions may be created if you delete this account.{/ts} {ts}Do you want to continue?{/ts}
      </div>
  {else}
    <table class="form-layout">
      <tr class="crm-contribution-form-block-account_relationship">
      <td class="label">{$form.account_relationship.label}</td>
  <td class="html-adjust">{$form.account_relationship.html}</td>
      </tr>
      <tr class="crm-contribution-form-block-financial_account_id">
      <td class="label">{$form.financial_account_id.label}</td>
  <td class="html-adjust">{$form.financial_account_id.html}</td>
      </tr>
    </table>
   {/if}
   <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="botttom"}</div>
</div>

{if $action ne 8}
<script type="text/javascript">
{literal}
CRM.$(function($) {
  const relations = {/literal}{$financialAccountRelations|json}{literal};
  const accounts = {/literal}{$financialAccounts|json}{literal};
  const relationships = {/literal}{$accountRelationships|json}{literal};

  const $rel = $('#account_relationship');
  const $acc = $('#financial_account_id');

  const resetAccounts = (matchingAccounts, defaultId) => {
    $acc.empty().append(new Option(ts('- select -'), 'select'));
    matchingAccounts.forEach((acc) => {
      $acc.append(new Option(acc.label, acc.id));
    });
    $acc.val(defaultId || 'select');
  };

  const resetRelationships = (matchingRelIds, selectedId) => {
    $rel.empty().append(new Option(ts('- Select Financial Account Relationship -'), 'select'));
    matchingRelIds.forEach((id) => {
      if (relationships[id]) {
        $rel.append(new Option(relationships[id], id));
      }
    });
    $rel.val(selectedId || 'select');
  };

  $acc.on('change', () => {
    const financialId = $acc.val();
    const currentRel = $rel.val();

    if (currentRel === 'select' || financialId === 'select') {
      if (financialId === 'select') {
        resetRelationships(Object.keys(relationships));
        resetAccounts(accounts);
      }
      else {
        const selectedAccount = accounts.find((acc) => acc.id == financialId);
        const typeId = selectedAccount ? selectedAccount.financial_account_type_id : null;
        const matchingRelIds = Object.keys(relations).filter((id) => relations[id] == typeId);
        const autoSelect = matchingRelIds.length === 1 ? matchingRelIds[0] : null;
        resetRelationships(matchingRelIds, autoSelect);
      }
    }
  });

  $rel.on('change', () => {
    const relVal = $rel.val();

    if (relVal === 'select') {
      resetAccounts(accounts);
      resetRelationships(Object.keys(relationships));
    }
    else {
      const typeId = relations[relVal];
      const matchingAccounts = typeId
        ? accounts.filter((acc) => acc.financial_account_type_id == typeId)
        : accounts;
      const defaultAcc = matchingAccounts.find((acc) => acc.is_default);
      resetAccounts(matchingAccounts, defaultAcc ? defaultAcc.id : null);
    }
  });
});
{/literal}
</script>
{/if}
