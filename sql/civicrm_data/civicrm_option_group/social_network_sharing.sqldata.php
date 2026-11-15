<?php
return CRM_Core_CodeGen_OptionGroup::create('social_network_sharing', 'a/0085')
  ->addMetadata([
    'title' => ts('Social Network Sharing'),
    'description' => implode(
      ' ',
      [
        ts('The following social network sharing options are offered to visitors, when and where enabled. You can reorder them and disable/enable them here.'),
      ]
    ),
  ])
  ->addValueTable(['name', 'value', 'desc', 'is_active'], [
    ['BlueSky', 'bsky', 'Share on Bluesky', 1],
    ['Facebook', 'facebook', 'Share on Facebook', 1],
    ['LinkedIn', 'linkedin', 'Share on LinkedIn', 1],
    ['Mastodon', 'mastodon', 'Share on Mastodon', 1],
    ['Twitter', 'twitter', 'Tweet', 0],
    ['Email', 'email', 'Email', 1],
  ])
  ->syncColumns('fill', ['name' => 'label']);
