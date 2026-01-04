<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright U.S. PIRG Education Fund 2007
 *
 */
class CRM_Core_QuickForm_GroupMultiSelect extends CRM_Core_QuickForm_NestedAdvMultiSelect {

  /**
   * Returns the HTML generated for the advanced multiple select component.
   *
   * @return string
   * @since      version 0.4.0 (2005-06-25)
   */
  public function toHtml() {
    if ($this->_flagFrozen) {
      return $this->getFrozenHtml();
    }

    $tabs = $this->_getTabs();
    $tab = $this->_getTab();
    $strHtml = '';

    if ($this->getComment() != '') {
      $strHtml .= $tabs . '<!-- ' . $this->getComment() . " //-->" . PHP_EOL;
    }

    $selectName = $this->getName() . '[]';

    // placeholder {unselected} existence determines if we will render
    if (!str_contains($this->_elementTemplate, '{unselected}')) {
      // ... a single multi-select with checkboxes

      $id = $this->getAttribute('id');

      $strHtmlSelected = $tab . '<div id="' . $id . 'amsSelected">' . PHP_EOL;

      foreach ($this->_options as $option) {

        $_labelAttributes = ['style', 'class', 'onmouseover', 'onmouseout'];
        $labelAttributes = [];
        foreach ($_labelAttributes as $attr) {
          if (isset($option['attr'][$attr])) {
            $labelAttributes[$attr] = $option['attr'][$attr];
            unset($option['attr'][$attr]);
          }
        }

        if (is_array($this->_values) && in_array((string) $option['attr']['value'], $this->_values)) {
          // The items is *selected*
          $checked = ' checked="checked"';
        }
        else {
          // The item is *unselected* so we want to put it
          $checked = '';
        }
        $strHtmlSelected .= $tab . '<label' . $this->_getAttrString($labelAttributes) . '>' . '<input type="checkbox"' . ' id="' . $this->getName() . '"' . ' name="' . $selectName . '"' . $checked . $this->_getAttrString($option['attr']) . ' />' . $option['text'] . '</label>' . PHP_EOL;
      }
      $strHtmlSelected .= $tab . '</div>' . PHP_EOL;

      $strHtmlHidden = '';
      $strHtmlUnselected = '';
      $strHtmlAdd = '';
      $strHtmlRemove = '';

      // build the select all button with all its attributes
      $attributes = ['onclick' => "{$this->_jsPrefix}{$this->_jsPostfix}('" . $this->getName() . "', 1);"];
      $this->_allButtonAttributes = array_merge($this->_allButtonAttributes, $attributes);
      $attrStrAll = $this->_getAttrString($this->_allButtonAttributes);
      $strHtmlAll = "<input$attrStrAll />" . PHP_EOL;

      // build the select none button with all its attributes
      $attributes = ['onclick' => "{$this->_jsPrefix}{$this->_jsPostfix}('" . $this->getName() . "', 0);"];
      $this->_noneButtonAttributes = array_merge($this->_noneButtonAttributes, $attributes);
      $attrStrNone = $this->_getAttrString($this->_noneButtonAttributes);
      $strHtmlNone = "<input$attrStrNone />" . PHP_EOL;

      // build the toggle selection button with all its attributes
      $attributes = ['onclick' => "{$this->_jsPrefix}{$this->_jsPostfix}('" . $this->getName() . "', 2);"];
      $this->_toggleButtonAttributes = array_merge($this->_toggleButtonAttributes, $attributes);
      $attrStrToggle = $this->_getAttrString($this->_toggleButtonAttributes);
      $strHtmlToggle = "<input$attrStrToggle />" . PHP_EOL;

      $strHtmlMoveUp = '';
      $strHtmlMoveDown = '';
    }
    else {
      // ... or a dual multi-select

      // set name of Select From Box
      $this->_attributesUnselected = [
        'name' => '__' . $selectName,
        'ondblclick' => "{$this->_jsPrefix}{$this->_jsPostfix}(this.form.elements['__" . $selectName . "'], this.form.elements['_" . $selectName . "'], this.form.elements['" . $selectName . "'], 'add')",
      ];
      $this->_attributesUnselected = array_merge($this->_attributes, $this->_attributesUnselected);
      $attrUnselected = $this->_getAttrString($this->_attributesUnselected);

      // set name of Select To Box
      $this->_attributesSelected = [
        'name' => '_' . $selectName,
        'ondblclick' => "{$this->_jsPrefix}{$this->_jsPostfix}(this.form.elements['__" . $selectName . "'], this.form.elements['_" . $selectName . "'], this.form.elements['" . $selectName . "'], 'remove')",
      ];
      $this->_attributesSelected = array_merge($this->_attributes, $this->_attributesSelected);
      $attrSelected = $this->_getAttrString($this->_attributesSelected);

      // set name of Select hidden Box
      $this->_attributesHidden = [
        'name' => $selectName,
        'style' => 'overflow: hidden; visibility: hidden; width: 1px; height: 0;',
      ];
      $this->_attributesHidden = array_merge($this->_attributes, $this->_attributesHidden);
      $attrHidden = $this->_getAttrString($this->_attributesHidden);

      // prepare option tables to be displayed as in POST order
      $append = count($this->_values);
      if ($append > 0) {
        $arrHtmlSelected = array_fill(0, $append, ' ');
      }
      else {
        $arrHtmlSelected = [];
      }

      $options = count($this->_options);
      $arrHtmlUnselected = [];
      if ($options > 0) {
        $arrHtmlHidden = array_fill(0, $options, ' ');

        foreach ($this->_options as $option) {
          if (is_array($this->_values) &&
            in_array((string) $option['attr']['value'], $this->_values)
          ) {
            // Get the post order
            $key = array_search($option['attr']['value'], $this->_values);

            // The items is *selected* so we want to put it in the 'selected' multi-select
            $arrHtmlSelected[$key] = $option;
            // Add it to the 'hidden' multi-select and set it as 'selected'
            $option['attr']['selected'] = 'selected';
            $arrHtmlHidden[$key] = $option;
          }
          else {
            // The item is *unselected* so we want to put it in the 'unselected' multi-select
            $arrHtmlUnselected[] = $option;
            // Add it to the hidden multi-select as 'unselected'
            $arrHtmlHidden[$append] = $option;
            $append++;
          }
        }
      }
      else {
        $arrHtmlHidden = [];
      }

      // The 'unselected' multi-select which appears on the left
      $strHtmlUnselected = "<select$attrUnselected>" . PHP_EOL;
      if (count($arrHtmlUnselected) > 0) {
        foreach ($arrHtmlUnselected as $data) {
          $strHtmlUnselected .= $tabs . $tab . '<option' . $this->_getAttrString($data['attr']) . '>' . $data['text'] . '</option>' . PHP_EOL;
        }
      }
      $strHtmlUnselected .= '</select>';

      // The 'selected' multi-select which appears on the right
      $strHtmlSelected = "<select$attrSelected>" . PHP_EOL;
      if (count($arrHtmlSelected) > 0) {
        foreach ($arrHtmlSelected as $data) {
          $strHtmlSelected .= $tabs . $tab . '<option' . $this->_getAttrString($data['attr']) . '>' . $data['text'] . '</option>' . PHP_EOL;
        }
      }
      $strHtmlSelected .= '</select>';

      // The 'hidden' multi-select
      $strHtmlHidden = "<select$attrHidden>" . PHP_EOL;
      if (count($arrHtmlHidden) > 0) {
        foreach ($arrHtmlHidden as $data) {
          $strHtmlHidden .= $tabs . $tab . '<option' . $this->_getAttrString($data['attr']) . '>' . $data['text'] . '</option>' . PHP_EOL;
        }
      }
      $strHtmlHidden .= '</select>';

      // build the remove button with all its attributes
      $attributes = ['onclick' => "{$this->_jsPrefix}{$this->_jsPostfix}(this.form.elements['__" . $selectName . "'], this.form.elements['_" . $selectName . "'], this.form.elements['" . $selectName . "'], 'remove'); return false;"];
      $this->_removeButtonAttributes = array_merge($this->_removeButtonAttributes, $attributes);
      $attrStrRemove = $this->_getAttrString($this->_removeButtonAttributes);
      $strHtmlRemove = "<input$attrStrRemove />" . PHP_EOL;

      // build the add button with all its attributes
      $attributes = ['onclick' => "{$this->_jsPrefix}{$this->_jsPostfix}(this.form.elements['__" . $selectName . "'], this.form.elements['_" . $selectName . "'], this.form.elements['" . $selectName . "'], 'add'); return false;"];
      $this->_addButtonAttributes = array_merge($this->_addButtonAttributes, $attributes);
      $attrStrAdd = $this->_getAttrString($this->_addButtonAttributes);
      $strHtmlAdd = "<input$attrStrAdd />" . PHP_EOL;

      // build the select all button with all its attributes
      $attributes = ['onclick' => "{$this->_jsPrefix}{$this->_jsPostfix}(this.form.elements['__" . $selectName . "'], this.form.elements['_" . $selectName . "'], this.form.elements['" . $selectName . "'], 'all'); return false;"];
      $this->_allButtonAttributes = array_merge($this->_allButtonAttributes, $attributes);
      $attrStrAll = $this->_getAttrString($this->_allButtonAttributes);
      $strHtmlAll = "<input$attrStrAll />" . PHP_EOL;

      // build the select none button with all its attributes
      $attributes = ['onclick' => "{$this->_jsPrefix}{$this->_jsPostfix}(this.form.elements['__" . $selectName . "'], this.form.elements['_" . $selectName . "'], this.form.elements['" . $selectName . "'], 'none'); return false;"];
      $this->_noneButtonAttributes = array_merge($this->_noneButtonAttributes, $attributes);
      $attrStrNone = $this->_getAttrString($this->_noneButtonAttributes);
      $strHtmlNone = "<input$attrStrNone />" . PHP_EOL;

      // build the toggle button with all its attributes
      $attributes = ['onclick' => "{$this->_jsPrefix}{$this->_jsPostfix}(this.form.elements['__" . $selectName . "'], this.form.elements['_" . $selectName . "'], this.form.elements['" . $selectName . "'], 'toggle'); return false;"];
      $this->_toggleButtonAttributes = array_merge($this->_toggleButtonAttributes, $attributes);
      $attrStrToggle = $this->_getAttrString($this->_toggleButtonAttributes);
      $strHtmlToggle = "<input$attrStrToggle />" . PHP_EOL;

      // build the move up button with all its attributes
      $attributes = ['onclick' => "{$this->_jsPrefix}moveUp(this.form.elements['_" . $selectName . "'], this.form.elements['" . $selectName . "']); return false;"];
      $this->_upButtonAttributes = array_merge($this->_upButtonAttributes, $attributes);
      $attrStrUp = $this->_getAttrString($this->_upButtonAttributes);
      $strHtmlMoveUp = "<input$attrStrUp />" . PHP_EOL;

      // build the move down button with all its attributes
      $attributes = ['onclick' => "{$this->_jsPrefix}moveDown(this.form.elements['_" . $selectName . "'], this.form.elements['" . $selectName . "']); return false;"];
      $this->_downButtonAttributes = array_merge($this->_downButtonAttributes, $attributes);
      $attrStrDown = $this->_getAttrString($this->_downButtonAttributes);
      $strHtmlMoveDown = "<input$attrStrDown />" . PHP_EOL;
    }

    // render all part of the multi select component with the template
    $strHtml = $this->_elementTemplate;

    // Prepare multiple labels
    $labels = $this->getLabel();
    if (is_array($labels)) {
      array_shift($labels);
    }
    // render extra labels, if any
    if (is_array($labels)) {
      foreach ($labels as $key => $text) {
        $key = is_int($key) ? $key + 2 : $key;
        $strHtml = str_replace("{label_{$key}}", $text, $strHtml);
        $strHtml = str_replace("<!-- BEGIN label_{$key} -->", '', $strHtml);
        $strHtml = str_replace("<!-- END label_{$key} -->", '', $strHtml);
      }
    }
    // clean up useless label tags
    if (strpos($strHtml, '{label_')) {
      $strHtml = preg_replace('/\s*<!-- BEGIN label_(\S+) -->.*<!-- END label_\1 -->\s*/i', '', $strHtml);
    }

    $placeHolders = [
      '{stylesheet}',
      '{javascript}',
      '{class}',
      '{unselected}',
      '{selected}',
      '{add}',
      '{remove}',
      '{all}',
      '{none}',
      '{toggle}',
      '{moveup}',
      '{movedown}',
    ];
    $htmlElements = [
      $this->getElementCss(FALSE),
      $this->getElementJs(FALSE),
      $this->_tableAttributes,
      $strHtmlUnselected,
      $strHtmlSelected . $strHtmlHidden,
      $strHtmlAdd,
      $strHtmlRemove,
      $strHtmlAll,
      $strHtmlNone,
      $strHtmlToggle,
      $strHtmlMoveUp,
      $strHtmlMoveDown,
    ];

    $strHtml = str_replace($placeHolders, $htmlElements, $strHtml);

    return $strHtml;
  }

}
