<?php

/**
 * @file
 * Node related functions.
 */

use Drupal\Core\Template\Attribute;

/**
 * Generic pre-process for all view mode of nodes.
 */
function _civictheme_preprocess_node(&$variables) {
  $node = $variables['node'];

  if (!$node) {
    return;
  }

  $variables['attributes']['class'][] = 'node';
  if (!$node->isPublished()) {
    $variables['attributes']['class'][] = 'node--unpublished';
  }

  $view_mode = $variables['elements']['#view_mode'];
  // Do not display node title on revision pages.
  if ($view_mode === 'full') {
    unset($variables['label']);
  }

  // Add Table of Contents if the value is set on node.
  if ($node->hasField('field_c_n_show_toc') && $node->field_c_n_show_toc->getString()) {
    $toc_attributes = [];
    $toc_attributes['data-table-of-contents-position'] = 'prepend';
    $toc_attributes['data-table-of-contents-theme'] = 'light';
    $toc_attributes['data-table-of-contents-anchor-selector'] = 'h2';
    $toc_attributes['data-table-of-contents-title'] = t('On this page');
    $toc_attributes['data-table-of-contents-anchor-scope-selector'] = '.civictheme-content__main';
    $toc_attributes = new Attribute($toc_attributes);

    array_unshift($variables['content'], [
      '#markup' => '<div class="container"><div class="row"><div class="table-of-contents-container col-m-12" ' . $toc_attributes . '></div></div></div>',
    ]);
  }
}

/**
 * Pre-process Full view mode of nodes.
 */
function _civictheme_preprocess_node__full(&$variables) {
  /** @var \Drupal\node\NodeInterface $node */
  $node = $variables['node'];
  $type = $node->getType();
  $type_callback = '_civictheme_preprocess_node__' . $type . '__full';
  if (function_exists($type_callback)) {
    $type_callback($variables);
  }
  civictheme_attributes_to_modifier_class($variables);
}

/**
 * Default view mode fallback preprocessor for nodes.
 */
function _civictheme_preprocess_node__default(&$variables) {
  /** @var \Drupal\node\NodeInterface $node */
  $node = $variables['node'];
  $type = $node->getType();
  $type_callback = '_civictheme_preprocess_node__' . $type . '__full';
  if (function_exists($type_callback)) {
    $type_callback($variables);
  }
  civictheme_attributes_to_modifier_class($variables);
}

/**
 * Pre-process CivicTheme alert nodes.
 */
function _civictheme_preprocess_node__civictheme_alert__full(&$variables) {
  $node = $variables['node'];
  $variables['id'] = $node->id();
  $variables['title'] = $node->label();
  $variables['description'] = $node->get('field_c_n_body')->value;
  $variables['type'] = $node->get('field_c_n_alert_type')->getString();
}

/**
 * Generic page pre-process for all view mode of nodes.
 */
function _civictheme_preprocess_page__node(&$variables) {
  /** @var \Drupal\node\Entity\Node $node */
  $node = civictheme_load_node_or_revision($variables);

  if (!$node) {
    return;
  }

  // Layout determines whether a node is contained and what it's layout is.
  $variables['page']['content_contained'] = FALSE;

  // Add vertical space.
  if ($node->hasField('field_c_n_space')) {
    $variables['vertical_space'] = $node->get('field_c_n_space')->getString();
  }

  // Hide sidebar check.
  if ($node->hasField('field_c_n_hide_sidebar') && !$node->get('field_c_n_hide_sidebar')->isEmpty() && $node->get('field_c_n_hide_sidebar')->getString()) {
    $variables['hide_sidebar'] = TRUE;
  }
}

/**
 * Pre-process CivicTheme nodes Slider slide view mode.
 */
function _civictheme_preprocess_node__civictheme_slider_slide(&$variables) {
  /** @var \Drupal\node\NodeInterface $node */
  $node = $variables['node'];

  $variables['title'] = $node->label();

  // Image.
  if ($node->hasField('field_c_n_thumbnail') && !$node->get('field_c_n_thumbnail')->isEmpty()) {
    $target = $node->get('field_c_n_thumbnail')->first()->get('entity')->getTarget();
    if ($target) {
      $media = $target->getValue();
      $variables['image'] = civictheme_media_image_process_variables($media);
    }
  }

  $variables['attributes']['data-component-civictheme-slider-slide'] = '';

  // Topic.
  if ($node->hasField('field_c_n_topics') && !$node->get('field_c_n_topics')->isEmpty()) {
    $topics = [];
    foreach ($node->get('field_c_n_topics')->referencedEntities() as $topic) {
      $topics[] = [
        'theme' => $variables['theme'],
        'text' => $topic->label(),
      ];
    }
    $variables['content_top']['slide_topics'] = [
      '#theme' => 'civictheme_slider_slide_tags',
      '#tags' => $topics,
    ];
  }

  // Summary.
  // Page.
  if ($node->hasField('field_c_n_summary') && !$node->get('field_c_n_summary')->isEmpty()) {
    $variables['summary'] = $node->get('field_c_n_summary')->getValue()[0]['value'];
  }
  // Event.
  if ($node->hasField('field_c_n_tagline') && !$node->get('field_c_n_tagline')->isEmpty()) {
    $variables['summary'] = $node->get('field_c_n_tagline')->getValue()[0]['value'];
  }

  // Links.
  $inverse_theme = $variables['theme'] === 'dark' ? 'light' : 'dark';
  $links = [];
  $links[] = [
    'type' => 'primary',
    'size' => 'regular',
    'text' => $node->label(),
    'url' => $variables['url'],
  ];
  $variables['links']['slide_links'] = [
    '#theme' => 'civictheme_slider_slide_links',
    '#component_theme' => $inverse_theme,
    '#links' => $links,
  ];
}
