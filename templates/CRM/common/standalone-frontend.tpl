<!DOCTYPE html >
<html lang="{$config->lcMessages|substr:0:2}" class="crm-standalone crm-public" >
 <head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="image/png" href="{$config->resourceBase}i/logo_lg.png" >

  {* @todo crmRegion below should replace this, but not working? *}
  {if isset($pageHTMLHead)}
    {foreach from=$pageHTMLHead item=i}
      {$i}
    {/foreach}
  {/if}

  {crmRegion name='html-header'}
  {/crmRegion}

  <title>{if isset($docTitle)}{$docTitle}{else}CiviCRM{/if}</title>
</head>
<body>
  {if $config->debug}
  {include file="CRM/common/debug.tpl"}
  {/if}

  <div id="crm-container" class="crm-container standalone-page-padding" lang="{$config->lcMessages|substr:0:2}" xml:lang="{$config->lcMessages|substr:0:2}">
    {if $breadcrumb}
      <nav aria-label="{ts escape='htmlattribute'}Breadcrumb{/ts}" class="breadcrumb"><ol>
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
      <div class="crm-page-title-wrapper">
        <h1 class="crm-page-title">{$pageTitle}</h1>
      </div>
    {/if}

    {crmRegion name='page-header'}
    {/crmRegion}

    <div class="clear"></div>

    <div id="crm-main-content-wrapper">
      {include file="CRM/common/status.tpl"}
      {crmRegion name='page-body'}
        {if isset($isForm) and $isForm and isset($formTpl)}
          {include file="CRM/Form/$formTpl.tpl"}
        {else}
          {include file=$tplFile}
        {/if}
      {/crmRegion}
    </div>

    {crmRegion name='page-footer'}
      {include file="CRM/common/publicFooter.tpl"}
    {/crmRegion}
  </div>
</body>
</html>
