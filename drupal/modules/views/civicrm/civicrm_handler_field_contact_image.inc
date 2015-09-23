<?php

/**
 * @file civicrm_handler_field_contact_image.inc
 *
 * @author Shawn P. Duncan <shawn@c3design.org>
 * @date   4/19/14,  9:37 AM
 *
 * @brief This class provides rendering for contact images displayed via the Views module in Drupal.
 *
 * Copyright 2014 by Shawn P. Duncan.  This code is
 * released under under the Academic Free License version 3.0
 * http://opensource.org/licenses/academic.php
 * Licensed to CiviCRM under the Academic Free License version 3.0.
 */
class civicrm_handler_field_contact_image extends views_handler_field {

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $image_styles = image_style_options(FALSE);
    if (module_exists('picture')) {
      $picture_group_options = array();
      $picture_group_options[] = "- Select a Picture Group -";
      $picture_group_options = $picture_group_options + picture_get_mapping_options();
      $form['picture_options'] = array(
        '#type' => 'fieldset',
        '#title' => t('Set Picture Module Options'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#weight' => 80,
      );
      $form['picture_group'] = array(
        '#title' => t('Picture group'),
        '#type' => 'select',
        '#default_value' => $this->options['picture_group'],
        '#options' => $picture_group_options,
        '#fieldset' => 'picture_options',
      );
      $form['fallback_image_style'] = array(
        '#title' => t('Fallback Image Style'),
        '#type' => 'select',
        '#default_value' => $this->options['fallback_image_style'],
        '#empty_option' => t('Automatic'),
        '#options' => $image_styles,
        '#fieldset' => 'picture_options',
      );
      $form['override_picture'] = array(
        '#type' => 'checkbox',
        '#title' => t('Override Picture Module styling'),
        '#description' => t('If checked, the image will always be rendered by Drupal Core using the Fallback Image Style set above.'),
        '#default_value' => $this->options['override_picture'],
        '#fieldset' => 'picture_options',
      );
      $form['alt'] = array(
        '#title' => t('Alt attribute'),
        '#description' => t('The text to use as value for the <em>img</em> tag <em>alt</em> attribute.'),
        '#type' => 'textfield',
        '#default_value' => $this->options['alt'],
        '#fieldset' => 'picture_options',
      );
      $form['title'] = array(
        '#title' => t('Title attribute'),
        '#description' => t('The text to use as value for the <em>img</em> tag <em>title</em> attribute.'),
        '#type' => 'textfield',
        '#default_value' => $this->options['title'],
        '#fieldset' => 'picture_options',
      );
    }
    else {
      $form['imagestyle_options'] = array(
        '#type' => 'fieldset',
        '#title' => t('Set Image Style Options'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#weight' => 80,
      );
      $form['image_style'] = array(
        '#title' => t('Image style'),
        '#type' => 'select',
        '#default_value' => $this->options['image_style'],
        '#empty_option' => t('None (original image)'),
        '#options' => $image_styles,
        '#fieldset' => 'imagestyle_options',
      );
      $form['alt'] = array(
        '#title' => t('Alt attribute'),
        '#description' => t('The text to use as value for the <em>img</em> tag <em>alt</em> attribute.'),
        '#type' => 'textfield',
        '#default_value' => $this->options['alt'],
        '#fieldset' => 'imagestyle_options',
      );
      $form['title'] = array(
        '#title' => t('Title attribute'),
        '#description' => t('The text to use as value for the <em>img</em> tag <em>title</em> attribute.'),
        '#type' => 'textfield',
        '#default_value' => $this->options['title'],
        '#fieldset' => 'imagestyle_options',
      );
    }
  }

  /* add our options to the options definition */
  function option_definition() {
    $options = parent::option_definition();
    if (module_exists('picture')) {
      $options['picture_group'] = array('default' => 0);
      $options['fallback_image_style'] = array('default' => '');
      $options['override_picture'] = array('default' => FALSE, 'bool' => TRUE);
      $options['alt'] = array(
        'default' => '',
        'translatable' => TRUE,
      );
      $options['title'] = array(
        'default' => '',
        'translatable' => TRUE,
      );
    }
    else {
      $options['image_style'] = array('default' => '');
      $options['alt'] = array(
        'default' => '',
        'translatable' => TRUE,
      );
      $options['title'] = array(
        'default' => '',
        'translatable' => TRUE,
      );
    }
    return $options;
  }

  /* Override views_handler_field pre_render.  The parent method probably doesn't need to be called
   * as it is an empty placeholder, but that could change...
   * */
  function pre_render(&$values) {
    parent::pre_render($values);
    $config = CRM_Core_Config::singleton();
    foreach ($values as $key => $row) {
      $file = array();
      // This is the public url (which gets routed through civi)
      $img_url = $this->get_value($row);
      if (!$img_url) {
        continue;
      }
      // This is the internal file path
      $file_path = $config->customFileUploadDir . substr(strstr($img_url, 'photo='), 6);
      $image_info = image_get_info($file_path);
      if ($image_info) {
        $file['width'] = $image_info['width'];
        $file['height'] = $image_info['height'];
      }
      $file['alt'] = $this->options['alt'];
      /* If $this->options['override_picture'] isn't TRUE then load breakpoints otherwise
       * set the fallback image style into a variable for
       * use in setting up the file array for image style themeing instead
       */
      if (module_exists('picture')) {
        if ($this->options['override_picture']) {
          /* User has chosen to fallback to core image_style */
          $image_style = $this->options['fallback_image_style'];
        }
        else {
          if (!empty($this->options['picture_group'])) {
            $file['uri'] = $img_url;
            $mappings = picture_mapping_load($this->options['picture_group']);
            $file['breakpoints'] = picture_get_mapping_breakpoints($mappings, $this->options['fallback_image_style']);
          }
          else {
            watchdog('CiviCRM Views',
              'Picture Module styling used in @view for Contact Image but Picture Group not selected',
              array('@view' => $this->view->get_title()),
              WATCHDOG_ERROR);
          }
          if (!empty($this->options['title'])) {
            $file['attributes'] = array(
              '#title' => $this->options['title'],
            );
          }
        }
      }
      if (isset($this->options['image_style']) || $this->options['override_picture']) {
        if (!empty($image_style)) {
          $file['style_name'] = $image_style;
        }
        elseif (!empty($this->options['image_style'])) {
          $file['style_name'] = $this->options['image_style'];
        }
        $file['path'] = $img_url;
        $file['title'] = $this->options['title'];
      }
      /* if file['uri'] was not set in the Picture module conditional, and $file['path] is not set
       * in the image style conditional just completed, then set the path and so we have something to theme
       * */
      if ((!isset($file['uri'])) && (!isset($file['path']))) {
        $file['path'] = $img_url;
        $file['title'] = $this->options['title'];
      }
      $values[$key]->{$this->field_alias} = $file;
    }
  }

  function render($values) {
    /* get the file array created in $this::prerender */
    $file = $this->get_value($values);
    /* If $file['uri'] contains the file path then we are using Picture module,
     * otherwise use core D7 image_style or fall through to raw image.
     * */
    if (!empty($file['uri'])) {
      return theme('picture', $file);
    }
    elseif (!empty($file['style_name'])) {
      return theme('image_style', $file);
    }
    elseif ($file) {
      return theme('image', $file);
    }
  }
}