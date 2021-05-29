{*suppress license if within a file that already has the license*}{if !isset($no_license) or !$no_license}-- +--------------------------------------------------------------------+
-- | Copyright CiviCRM LLC. All rights reserved.                        |
-- |                                                                    |
-- | This work is published under the GNU AGPLv3 license with some      |
-- | permitted exceptions and without any warranty. For full license    |
-- | and copyright information, see https://civicrm.org/licensing       |
-- +--------------------------------------------------------------------+
--
-- Generated from {$smarty.template}
-- {$generated}
--{/if}
-- /*******************************************************
-- *
-- * Clean up the existing tables{if isset($no_license) and $no_license} - this section generated from {$smarty.template}
{/if}
-- *
-- *******************************************************/

SET FOREIGN_KEY_CHECKS=0;

{foreach from=$dropOrder item=name}
DROP TABLE IF EXISTS `{$name}`;
{/foreach}

SET FOREIGN_KEY_CHECKS=1;
