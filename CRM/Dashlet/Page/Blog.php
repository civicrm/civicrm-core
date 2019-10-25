<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */

/**
 * Main page for blog dashlet
 */
class CRM_Dashlet_Page_Blog extends CRM_Core_Page {

  const CHECK_TIMEOUT = 5;
  const CACHE_DAYS = 1;
  const NEWS_URL = 'https://civicrm.org/news-feed.rss';

  /**
   * Gets url for blog feed.
   *
   * @return string
   */
  public function getNewsUrl() {
    // Note: We use "*default*" as the default (rather than self::NEWS_URL) so that future
    // developers can change NEWS_URL without needing to update {civicrm_setting}.
    $url = Civi::settings()->get('blogUrl');
    if ($url === '*default*') {
      $url = self::NEWS_URL;
    }
    return CRM_Utils_System::evalUrl($url);
  }

  /**
   * Output data to template.
   */
  public function run() {
    $this->assign('feeds', $this->getData());
    return parent::run();
  }

  /**
   * Load feeds from cache.
   *
   * Refresh cache if expired.
   *
   * @return array
   */
  protected function getData() {
    $value = Civi::cache('community_messages')->get('dashboard_newsfeed');

    if (!$value) {
      $value = $this->getFeeds();

      if ($value) {
        Civi::cache('community_messages')->set('dashboard_newsfeed', $value, (60 * 60 * 24 * self::CACHE_DAYS));
      }
    }

    return $value;
  }

  /**
   * Fetch all feeds.
   *
   * @return array
   */
  protected function getFeeds() {
    $newsFeed = $this->getFeed($this->getNewsUrl());
    // If unable to fetch the feed, return empty results.
    if (!$newsFeed) {
      return [];
    }
    $feeds = $this->formatItems($newsFeed);
    return $feeds;
  }

  /**
   * Parse a single rss feed.
   *
   * @param $url
   *
   * @return array|NULL
   *   array of blog items; or NULL if not available
   */
  protected function getFeed($url) {
    $httpClient = new CRM_Utils_HttpClient(self::CHECK_TIMEOUT);
    list ($status, $rawFeed) = $httpClient->get($url);
    if ($status !== CRM_Utils_HttpClient::STATUS_OK) {
      return NULL;
    }
    return @simplexml_load_string($rawFeed);
  }

  /**
   * @param string $feed
   * @return array
   */
  protected function formatItems($feed) {
    $result = [];
    if ($feed && !empty($feed->channel)) {
      foreach ($feed->channel as $channel) {
        $content = [
          'title' => (string) $channel->title,
          'description' => (string) $channel->description,
          'name' => strtolower(CRM_Utils_String::munge($channel->title, '-')),
          'items' => [],
        ];
        foreach ($channel->item as $item) {
          $item = (array) $item;
          $item['title'] = strip_tags($item['title']);
          // Clean up description - remove tags & styles that would break dashboard layout
          $description = preg_replace('#<h[1-3][^>]*>(.+?)</h[1-3][^>]*>#s', '<h4>$1</h4>', $item['description']);
          $description = strip_tags($description, "<a><p><h4><h5><h6><b><i><em><strong><ol><ul><li><dd><dt><code><pre><br><hr>");
          $description = preg_replace('/(<[^>]+) style=["\'].*?["\']/i', '$1', $description);
          // Add paragraph markup if it's missing.
          if (strpos($description, '<p') === FALSE) {
            $description = '<p>' . $description . '</p>';
          }
          $item['description'] = $description;
          $content['items'][] = $item;
        }
        if ($content['items']) {
          $result[] = $content;
        }
      }
    }
    return $result;
  }

}
