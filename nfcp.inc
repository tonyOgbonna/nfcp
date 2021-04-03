<?php

/**
 * @file
 * nfcp.inc
 *
 * Main complete percentage calculator function for nfcp.
 */

/**
 * Main complete percentage calculator function for nfcp.
 */
function nfcp_get_complete_percentage_data($node) {

  // Get a nodes fields.
  // $fields = [];.
  $fields_label = [];
  $all_fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $node->bundle());
  foreach ($all_fields as $field_name => $field_definition) {
    if (!empty($field_definition->getTargetBundle())) {
      // $fields[$field_name] = $field_definition;
      $fields_label[$field_name] = $field_definition->getLabel();
    }
  }

  $nfcp_data = [];
  $config = \Drupal::config('nfcp.configuration');

  $field_order = $config->get('field_order');
  $node_fields = $config->get('node_fields');
  $hide_nfcp_block = $config->get('hide_block_on_complete');
  $open_field_link = $config->get('open_link');

  // Initialize all variable.
  $nfcp_data['nid']             = "";
  $nfcp_data['uid']             = "";
  $nfcp_data['total']           = "";
  $nfcp_data['open_link']       = "";
  $nfcp_data['completed']       = "";
  $nfcp_data['incomplete']      = "";
  $nfcp_data['hide_nfcp_block'] = "";
  $nfcp_data['nextfield_name']  = "";
  $nfcp_data['nextfield_title'] = "";
  $nfcp_data['current_percent'] = "";
  $nfcp_data['next_percent']    = "";
  $nfcp_data['nextfield_name']  = "";

  if (!isset($node_fields)) {
    $nfcp_data['current_percent'] = 100;
    $nfcp_data['next_percent']    = 0;
    return $nfcp_data;
  }

  // Check which node field is filled.
  $empty_fields = $nfcp_fields = [];
  foreach ($node_fields as $field_name_key => $field_name) {
    if (strcmp($field_name_key, $field_name) == 0) {
      $enable = (!empty($node->{$field_name}->value) || !empty($node->{$field_name}->target_id)) ? 1 : 0;
      $nfcp_fields[$field_name_key] = $enable;
      if ($enable == 0) {
        $empty_fields[$field_name_key] = $fields_label[$field_name];
      }
    }
  }

  // Count filled, empty and total field.
  $fields_count       = is_array($nfcp_fields) ? count($nfcp_fields) : 0;
  $empty_fields_count = is_array($empty_fields) ? count($empty_fields) : 0;
  $completed_fields   = $fields_count - $empty_fields_count;

  $current_percent = 0;
  $next_percent    = 0;
  if ($fields_count != 0) {
    $current_percent = number_format(($completed_fields / $fields_count), 2) * 100;
    $next_percent    = number_format((($completed_fields + 1) / $fields_count), 2) * 100;
  }

  // Open node field in new or same browser window.
  $open_field_link = $open_field_link == 0 ? '_self' : '_target';

  // Hide or Show when "node complete percentage" show 100%.
  $hide_nfcp_block = $hide_nfcp_block == 0 ? FALSE : TRUE;

  // Show random or same field, after refresh a browser window.
  reset($empty_fields);
  $next_field = $field_order == 0 ? array_rand($empty_fields) : key($empty_fields);
  $nextfield_title = isset($empty_fields[$next_field]) ? $empty_fields[$next_field] : "";

  $nfcp_data['nid']             = $node->id();
  $nfcp_data['uid']             = $node->getOwnerId();
  $nfcp_data['title']           = $node->getTitle();
  $nfcp_data['total']           = $fields_count;
  $nfcp_data['open_link']       = $open_field_link;
  $nfcp_data['completed']       = $completed_fields;
  $nfcp_data['incomplete']      = $empty_fields_count;
  $nfcp_data['hide_nfcp_block'] = $hide_nfcp_block;
  $nfcp_data['nextfield_name']  = $next_field;
  $nfcp_data['nextfield_title'] = $nextfield_title;
  $nfcp_data['current_percent'] = $current_percent;
  $nfcp_data['next_percent']    = $next_percent;
  $nfcp_data['nextfield_name']  = str_replace('_', '-', $next_field);

  return $nfcp_data;
}
