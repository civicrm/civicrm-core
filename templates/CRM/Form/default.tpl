{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if !$suppressForm}
<form {$form.attributes|smarty:nodefaults}>
  {crmRegion name='form-top'}{/crmRegion}
{/if}

  {crmRegion name='form-body'}
    {include file="CRM/Form/body.tpl"}

    {include file=$tplFile}
  {/crmRegion}

{if !$suppressForm}
  {crmRegion name='form-bottom'}{/crmRegion}
</form>
{/if}
