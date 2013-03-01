{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{if $config->userSystem->is_drupal eq '1'}
  {if $config->customCSSURL}
    <link rel="stylesheet" href="{$config->customCSSURL}" type="text/css" />
  {else}
    <link rel="stylesheet" href="{$config->resourceBase}css/deprecate.css" type="text/css" />
    <link rel="stylesheet" href="{$config->resourceBase}css/civicrm.css" type="text/css" />
    <link rel="stylesheet" href="{$config->resourceBase}css/extras.css" type="text/css" />
  {/if} 
{elseif $config->userFramework eq 'Joomla'}
  <link rel="stylesheet" href="{$config->resourceBase}css/deprecate.css" type="text/css" />
  <link rel="stylesheet" href="{$config->resourceBase}css/civicrm.css" type="text/css" />
  {if !$config->userFrameworkFrontend}
    <link rel="stylesheet" href="{$config->resourceBase}css/joomla.css" type="text/css" />
  {else}
    <link rel="stylesheet" href="{$config->resourceBase}css/joomla_frontend.css" type="text/css" />
  {/if}
  {if $config->customCSSURL}
    <link rel="stylesheet" href="{$config->customCSSURL}" type="text/css" />
  {/if}
  <link rel="stylesheet" href="{$config->resourceBase}css/extras.css" type="text/css" />
{/if}