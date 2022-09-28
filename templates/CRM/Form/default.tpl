{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if empty($suppressForm)}
<form {$form.attributes} >
  {crmRegion name='form-top'}{/crmRegion}
{/if}

  {crmRegion name='form-body'}
    {include file="CRM/Form/body.tpl"}

    {include file=$tplFile}
  {/crmRegion}

{if empty($suppressForm)}
  {crmRegion name='form-bottom'}{/crmRegion}
</form>
{/if}
