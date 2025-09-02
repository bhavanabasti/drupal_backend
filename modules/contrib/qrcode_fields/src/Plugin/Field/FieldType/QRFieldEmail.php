<?php

namespace Drupal\qrcode_fields\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

#[FieldType(
  id: "qrcode_email",
  label: new TranslatableMarkup("Email Message"),
  description: new TranslatableMarkup("Field for generating QR codes for email message."),
  default_widget: "qrcode_email_field_widget",
  default_formatter: "qrcode_fields_formatter",
)]
class QRFieldEmail extends FieldItemBase {

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
      '#title' => $this->t('QR code email message service plugin'),
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
    $properties['email'] = DataDefinition::create('string')
      ->setLabel(t("Recipient's email address"));
    $properties['subject'] = DataDefinition::create('string')
      ->setLabel(t('Subject Line'));
    $properties['message'] = DataDefinition::create('string')
      ->setLabel(t('Body of the Email'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'email' => [
          'type' => 'varchar',
          'length' => '255',
          'not null' => FALSE,
        ],
        'subject' => [
          'type' => 'varchar',
          'length' => '255',
          'not null' => FALSE,
        ],
        'message' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
      ],
    ];
  }

}
