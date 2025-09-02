<?php

namespace Drupal\qrcode_fields\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

#[FieldType(
  id: "qrcode_phone",
  label: new TranslatableMarkup("Phone"),
  description: new TranslatableMarkup("Field for generating QR codes from content entity."),
  default_widget: "qrcode_phone_field_widget",
  default_formatter: "qrcode_fields_formatter",
)]
class QRFieldPhone extends FieldItemBase {

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
      '#title' => $this->t('QR code phone service plugin'),
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
    $properties['phone'] = DataDefinition::create('string')
      ->setLabel(t('QR code phone'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'phone' => [
          'type' => 'varchar',
          'length' => '255',
        ],
      ],
    ];
  }

}
