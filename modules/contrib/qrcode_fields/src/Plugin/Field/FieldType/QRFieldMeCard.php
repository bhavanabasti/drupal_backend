<?php

namespace Drupal\qrcode_fields\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

#[FieldType(
  id: "qrcode_mecard",
  label: new TranslatableMarkup("meCard"),
  description: new TranslatableMarkup("Field for generating QR codes from content entity."),
  default_widget: "qrcode_mecard_field_widget",
  default_formatter: "qrcode_fields_formatter",
)]
class QRFieldMeCard extends FieldItemBase {

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
      '#title' => $this->t('QR code meCard service plugin'),
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
    $properties['fname'] = DataDefinition::create('string')
      ->setLabel(t('QR code fname'));
    $properties['lname'] = DataDefinition::create('string')
      ->setLabel(t('QR code lname'));
    $properties['email'] = DataDefinition::create('string')
      ->setLabel(t('QR code email'));
    $properties['phone'] = DataDefinition::create('string')
      ->setLabel(t('QR code phone'));
    $properties['address'] = DataDefinition::create('string')
      ->setLabel(t('QR code address'));
    $properties['url'] = DataDefinition::create('string')
      ->setLabel(t('QR code url'));
    $properties['note'] = DataDefinition::create('string')
      ->setLabel(t('QR code note'));
    $properties['organization'] = DataDefinition::create('string')
      ->setLabel(t('QR code organization'));
    $properties['birthday'] = DataDefinition::create('string')
      ->setLabel(t('QR code birthday'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'fname' => [
          'type' => 'varchar',
          'length' => '255',
          'not null' => FALSE,
        ],
        'lname' => [
          'type' => 'varchar',
          'length' => '255',
          'not null' => FALSE,
        ],
        'email' => [
          'type' => 'varchar',
          'length' => '255',
          'not null' => FALSE,
        ],
        'phone' => [
          'type' => 'varchar',
          'length' => '255',
          'not null' => FALSE,
        ],
        'address' => [
          'type' => 'varchar',
          'length' => '255',
          'not null' => FALSE,
        ],
        'url' => [
          'type' => 'varchar',
          'length' => '255',
          'not null' => FALSE,
        ],
        'note' => [
          'type' => 'varchar',
          'length' => '255',
          'not null' => FALSE,
        ],
        'organization' => [
          'type' => 'varchar',
          'length' => '255',
          'not null' => FALSE,
        ],
        'title' => [
          'type' => 'varchar',
          'length' => '255',
          'not null' => FALSE,
        ],
        'birthday' => [
          'type' => 'varchar',
          'length' => '255',
          'not null' => FALSE,
        ],
      ],
    ];
  }

}
