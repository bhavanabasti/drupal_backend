<?php

namespace Drupal\qrcode_fields\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

#[FieldType(
  id: "qrcode_event",
  label: new TranslatableMarkup("Calendar event"),
  description: new TranslatableMarkup("Field for generating QR codes from content entity."),
  default_widget: "qrcode_event_field_widget",
  default_formatter: "qrcode_fields_formatter",
)]
class QRFieldEvent extends FieldItemBase {

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
      '#title' => $this->t('QR code Calendar event service plugin'),
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
    $properties['summary'] = DataDefinition::create('string')
      ->setLabel(t('QR code summary'));
    $properties['description'] = DataDefinition::create('string')
      ->setLabel(t('QR code description'));
    $properties['location'] = DataDefinition::create('string')
      ->setLabel(t('QR code location'));
    $properties['dstart'] = DataDefinition::create('string')
      ->setLabel(t('QR code dstart'));
    $properties['dend'] = DataDefinition::create('string')
      ->setLabel(t('QR code dend'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'summary' => [
          'type' => 'varchar',
          'length' => '255',
          'not null' => FALSE,
        ],
        'description' => [
          'type' => 'varchar',
          'length' => '255',
          'not null' => FALSE,
        ],
        'location' => [
          'type' => 'varchar',
          'length' => '255',
          'not null' => FALSE,
        ],
        'dstart' => [
          'type' => 'varchar',
          'mysql_type' => 'datetime',
          'not null' => FALSE,
          'default' => NULL,
        ],
        'dend' => [
          'type' => 'varchar',
          'mysql_type' => 'datetime',
          'not null' => FALSE,
          'default' => NULL,
        ],
      ],
    ];
  }

}
