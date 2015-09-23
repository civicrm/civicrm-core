<?php

/**
 * A handler to run a field through check_markup, using the default format
 *
 */
class civicrm_handler_field_markup extends views_handler_field {
  function option_definition() {
    $options = parent::option_definition();

    $options['text_format'] = array('default' => filter_fallback_format());
    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['text_format'] = array(
      '#type' => 'select',
      '#title' => 'Text format',
      '#default_value' => $this->options['text_format'],
      '#options' => $this->get_formats(),
    );
  }

  function render($values) {
    $value = $values->{$this->field_alias};
    $retval = check_markup($value, $this->options['text_format'], FALSE);
    return ($retval == 'n/a') ? NULL : $retval;
  }

  function get_formats() {
    $formats = array();
    foreach (filter_formats() as $format) {
      $formats[$format->format] = $format->name;
}
    return $formats;
  }
}

