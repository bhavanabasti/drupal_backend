<?php

namespace Drupal\qrcode_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of 'qrcode_fields_formatter'.
 *
 * This is the default implementation used for displaying QR code image.
 *
 * @FieldFormatter(
 *   id = "qrcode_fields_formatter",
 *   label = @Translation("QR code"),
 *   field_types = {
 *      "qrcode_email",
 *      "qrcode_mecard",
 *      "qrcode_phone",
 *      "qrcode_sms",
 *      "qrcode_text",
 *      "qrcode_url",
 *      "qrcode_vcard",
 *      "qrcode_event",
 *      "qrcode_wifi"
 *   }
 * )
 */
class QRFieldFormatter extends QRFieldFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'display_text' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['display_text'] = [
      '#title' => $this->t('Display QR image text'),
      '#type' => 'checkbox',
      '#description' => $this->t('Shows text encoded in QR code.'),
      '#default_value' => $this->getSetting('display_text'),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [
      'display_text' => $this->t('Display text: @value', [
        '@value' => $this->getSetting('display_text') ? $this->t('Yes') : $this->t('No'),
      ]),
    ] + parent::settingsSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    if ($this->getSetting('display_text')) {
      foreach ($elements as $delta => $element) {
        $elements[$delta]['email'] = [
          '#markup' => $element['image']['#alt'] ?? $items[$delta]->get('email')->getValue(),
        ];
      }
    }
    return $elements;
  }

}
