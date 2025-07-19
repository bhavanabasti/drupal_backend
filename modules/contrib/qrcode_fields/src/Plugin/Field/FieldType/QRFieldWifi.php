<?php

namespace Drupal\qrcode_fields\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

#[FieldType(
  id: "qrcode_wifi",
  label: new TranslatableMarkup("WiFi"),
  description: new TranslatableMarkup("Field for generating QR codes for wifi."),
  default_widget: "qrcode_wifi_field_widget",
  default_formatter: "qrcode_fields_formatter",
)]
class QRFieldWifi extends FieldItemBase {

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
      '#title' => $this->t('QR code wifi service plugin'),
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
    $properties['network_name'] = DataDefinition::create('string')
      ->setLabel(t('Network name'));
    $properties['password'] = DataDefinition::create('string')
      ->setLabel(t('Password'));
    $properties['hidden'] = DataDefinition::create('string')
      ->setLabel(t('Hidden'));
    $properties['encryption'] = DataDefinition::create('string')
      ->setLabel(t('Encryption'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'network_name' => [
          'type' => 'varchar',
          'length' => '255',
          'not null' => FALSE,
        ],
        'password' => [
          'type' => 'varchar',
          'length' => '255',
          'not null' => FALSE,
        ],
        'hidden' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'encryption' => [
          'type' => 'varchar',
          'length' => '255',
          'not null' => FALSE,
        ],
      ],
    ];
  }

}
