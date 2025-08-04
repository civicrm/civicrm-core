<?php

namespace Civi\Api4\Action\SearchDisplay;

/**
 * Download the results of a SearchDisplay as a spreadsheet.
 *
 * Note: unlike other APIs this action will directly output a file
 * if 'format' is set to anything other than 'array'.
 *
 * @method $this setFormat(string $format)
 * @method string getFormat()
 * @package Civi\Api4\Action\SearchDisplay
 */
class Download extends AbstractRunAction {
  use ResultDataTrait;

  /**
   * @param \Civi\Api4\Result\SearchDisplayRunResult $result
   * @throws \CRM_Core_Exception
   */
  protected function processResult(\Civi\Api4\Result\SearchDisplayRunResult $result) {
    $entityName = $this->savedSearch['api_entity'];
    $apiParams =& $this->_apiParams;
    $settings =& $this->display['settings'];
    $fileName = '';

    // Checking permissions for menu, link or button columns is costly, so remove them early
    foreach ($settings['columns'] as $index => $col) {
      // Remove buttons/menus and other column types that cannot be rendered in a spreadsheet
      if (empty($col['key'])) {
        unset($settings['columns'][$index]);
      }
      // Avoid wasting time processing links, editable and other non-printable items from spreadsheet
      else {
        \CRM_Utils_Array::remove($settings['columns'][$index], 'link', 'editable', 'icons', 'cssClass');
      }
    }
    // Reset indexes as some items may have been removed
    $settings['columns'] = array_values($settings['columns']);

    // Displays are only exportable if they have actions enabled
    if (empty($settings['actions'])) {
      \CRM_Utils_System::permissionDenied();
    }

    // Force limit if the display has no pager
    if (!isset($settings['pager']) && !empty($settings['limit'])) {
      $apiParams['limit'] = $settings['limit'];
    }
    $apiParams['orderBy'] = $this->getOrderByFromSort();
    $this->augmentSelectClause($apiParams, $settings);

    $this->applyFilters();

    $apiResult = civicrm_api4($entityName, 'get', $apiParams);

    $rows = $this->formatResult($apiResult);

    if ('array' === $this->format) {
      $result->exchangeArray($this->processData($this->display['label'], $this->display['settings']['columns'], $rows, $fileName));
      return;
    }
    else {
      $this->processData($this->display['label'], $this->display['settings']['columns'], $rows, $fileName);
    }
    $bypass_headers = ['array', 'csv'];
    if (!in_array($this->format, $bypass_headers)) {
      $this->sendHeaders($fileName);
    }
    \CRM_Utils_System::civiExit();
  }

  /**
   * Return raw value if it is a single date, otherwise return parent
   * {@inheritDoc}
   */
  protected function formatViewValue($key, $rawValue, $data, $dataType, $format = NULL) {
    if (is_array($rawValue)) {
      return parent::formatViewValue($key, $rawValue, $data, $dataType, $format);
    }

    if (($dataType === 'Date' || $dataType === 'Timestamp') && in_array($this->format, ['csv', 'xlsx', 'ods'])) {
      return $rawValue;
    }
    else {
      return parent::formatViewValue($key, $rawValue, $data, $dataType, $format);
    }
  }

  /**
   * Sets headers based on content type and file name
   *
   * @param string $fileName
   */
  protected function sendHeaders(string $fileName) {
    header('Content-Type: ' . $this->formats[$this->format]['mime']);
    header('Content-Transfer-Encoding: binary');
    header('Content-Description: File Transfer');
    header('Content-Disposition: ' . $this->getContentDisposition($fileName));
  }

  /**
   * Copied from \League\Csv\AbstractCsv::sendHeaders()
   * @param string $fileName
   * @return string
   */
  protected function getContentDisposition(string $fileName) {
    $flag = FILTER_FLAG_STRIP_LOW;
    if (strlen($fileName) !== mb_strlen($fileName)) {
      $flag |= FILTER_FLAG_STRIP_HIGH;
    }

    /** @var string $filtered_name */
    $filtered_name = filter_var($fileName, FILTER_UNSAFE_RAW, $flag);
    $filenameFallback = str_replace('%', '', $filtered_name);

    $disposition = sprintf('attachment; filename="%s"', str_replace('"', '\\"', $filenameFallback));
    if ($fileName !== $filenameFallback) {
      $disposition .= sprintf("; filename*=utf-8''%s", rawurlencode($fileName));
    }
    return $disposition;
  }

}
