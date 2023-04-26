<?php

namespace Drupal\custom_text_format_action\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Change text format of fields with CKEditor.
 *
 * @Action(
 *   id = "entity:change_text_format",
 *   action_label = @Translation("Change text format of fields with CKEditor"),
 *   type = "node",
 *   deriver = "Drupal\Core\Action\Plugin\Action\Derivative\EntityChangedActionDeriver"
 * )
 */
class ChangeTextFormat extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity === NULL) {
      return;
    }

    // Get all fields.
    $fields = $entity->getFieldDefinitions();

    foreach ($fields as $field_name => $field_definition) {

      // Check if the field is a text field with CKEditor.
      if ($field_definition->getType() === 'text_with_summary' || $field_definition->getType() === 'text_long') {

        // Current text format.
        $old_text_format = $entity->get($field_name)->format;

        // Change format.
        if ($entity->get($field_name)->format !== $this->configuration['format']) {
          $entity->get($field_name)->format = $this->configuration['format'];
        } else {
          $this->messenger()->addMessage("Nothing happened");
          return;
        }

        // Save
        $entity->save();

        // Log the changes.
        $logger = \Drupal::logger('custom_text_format_action');
        $logger->info('Node ID: @nid, Old text format: @old_format, New text format: @new_format', [
          '@nid' => $entity->id(),
          '@old_format' => $old_text_format,
          '@new_format' => $this->configuration['format'],
        ]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('update', $account, $return_as_object);
  }

  public function defaultConfiguration() {
    return ['format' => 'basic_html'];
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $formats = \Drupal::database()->query("select name from {config} where name like 'filter.format%'")->fetchAll();
    $values = [];

    foreach ($formats as $item) {
      $values[] = mb_substr($item->name, 14);
    }
    $form['format'] = [
      '#title' => $this->t('Choose your primary format'),
      '#options' => array_combine($values, $values),
      '#type' => 'select',
      '#required' => TRUE,
      '#default_value' => $this->configuration['format'],
    ];

    return $form;
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration["format"] = $form_state->getValue('format');
  }

}
