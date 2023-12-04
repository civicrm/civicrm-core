<!DOCTYPE html >
<html lang="{$config->lcMessages|substr:0:2}" class="crm-standalone" >
 <head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="Shortcut Icon" type="image/x-icon" href="{$config->resourceBase}i/widget/favicon.png" />

  {* @todo crmRegion below should replace this, but not working? *}
  {if isset($pageHTMLHead)}
    {foreach from=$pageHTMLHead item=i}
      {$i}
    {/foreach}
  {/if}

  {crmRegion name='html-header'}
  {/crmRegion}

{* @todo This is probably not required? *}
{if isset($buildNavigation) and !$urlIsPublic}
    {include file="CRM/common/Navigation.tpl"}
{/if}

  <title>{if isset($docTitle)}{$docTitle}{else}CiviCRM{/if}</title>
</head>
<body>
  {if $config->debug}
  {include file="CRM/common/debug.tpl"}
  {/if}

  <div id="crm-container" class="crm-container" lang="{$config->lcMessages|substr:0:2}" xml:lang="{$config->lcMessages|substr:0:2}">
    {if $breadcrumb}
      <nav aria-label="{ts}Breadcrumb{/ts}" class="breadcrumb"><ol>
        <li><a href="/civicrm/dashboard?reset=1" >{ts}Home{/ts}</a></li>
        {foreach from=$breadcrumb item=crumb key=key}
          <li><a href="{$crumb.url}">{$crumb.title}</a></li>
        {/foreach}
      </ol></nav>
    {/if}

    {if $standaloneErrors}
      <div class="standalone-errors">
        <ul>{$standaloneErrors}</ul>
      </div>
    {/if}

    {if $pageTitle}
      <div class="crm-title">
        <h1 class="title">{if $isDeleted}<del>{/if}{$pageTitle}{if $isDeleted}</del>{/if}</h1>
      </div>
    {/if}

    {crmRegion name='page-header'}
    {/crmRegion}

    <div class="clear"></div>

    <div id="crm-main-content-wrapper">
      {* include file="CRM/common/status.tpl" @todo FIXME *}
      {crmRegion name='page-body'}
        {if isset($isForm) and $isForm and isset($formTpl)}
          {include file="CRM/Form/$formTpl.tpl"}
        {else}
          {include file=$tplFile}
        {/if}
      {/crmRegion}
    </div>

    {crmRegion name='page-footer'}
      {if !empty($urlIsPublic)}
        {include file="CRM/common/publicFooter.tpl"}
      {else}
        {include file="CRM/common/footer.tpl"}
      {/if}
    {/crmRegion}
  </div>
</body>
</html>
