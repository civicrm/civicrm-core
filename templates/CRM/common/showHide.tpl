{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* This included tpl hides and displays the appropriate blocks as directed by the php code which assigns showBlocks and hideBlocks arrays. *}
 <script type="text/javascript">
    var showBlocks = new Array({$showBlocks nofilter});
    var hideBlocks = new Array({$hideBlocks nofilter});

    on_load_init_blocks( showBlocks, hideBlocks{if $elemType and $elemType EQ 'table-row'}, 'table-row'{/if} );
 </script>
