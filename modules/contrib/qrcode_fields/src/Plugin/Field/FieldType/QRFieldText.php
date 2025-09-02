<?php

namespace Drupal\qrcode_fields\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

#[FieldType(
  id: "qrcode_text",
  label: new TranslatableMarkup("Text"),
  description: new TranslatableMarkup("Field for generating QR codes from content entity."),
  default_widget: "qrcode_fields_widget",
  default_formatter: "qrcode_fields_formatter",
)]
class QRFieldText extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'qrcode_plugin' => 'goqr',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];
    $pluginDefinitions = \Drupal::service('plugin.manager.qrcode_fields')->getDefinitionsList();
    $elements['qrcode_plugin'] = [
      '#title' => $this->t('QR code service plugin'),
      '#type' => 'select',
      '#options' => $pluginDefinitions,
      '#default_value' => $this->getSetting('qrcode_plugin'),
      '#description' => $this->t('Service to use for QR code generation.'),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['text'] = DataDefinition::create('string')
      ->setLabel(t('QR text'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'text' => [
          'type' => 'varchar',
          'length' => '255',
        ],
      ],
    ];
  }

}
