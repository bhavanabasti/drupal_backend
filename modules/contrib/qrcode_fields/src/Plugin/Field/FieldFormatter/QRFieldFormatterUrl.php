<?php

namespace Drupal\qrcode_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of 'qrcode_fields_formatter_url'.
 *
 * This formatter is used for displaying URLs pointing to
 * generated QR code.
 *
 * @FieldFormatter(
 *   id = "qrcode_fields_formatter_url",
 *   label = @Translation("QR code image URL"),
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
class QRFieldFormatterUrl extends QRFieldFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'display_link' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['display_link'] = [
      '#title' => $this->t('Display QR code as link'),
      '#type' => 'checkbox',
      '#description' => $this->t('Show link pointing to QR code image. If disabled URL will be shown as plain text.'),
      '#default_value' => $this->getSetting('display_link'),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [
      'display_link' => $this->t('Display QR code as link: @value', [
        '@value' => $this->getSetting('display_link') ? $this->t('Yes') : $this->t('No'),
      ]),
    ] + parent::settingsSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $parentElements = parent::viewElements($items, $langcode);
    $elements = [];

    foreach ($parentElements as $element) {
      if ($this->getSetting('display_link')) {
        $url = Url::fromUri($element['image']['#uri']);
        $elements[] = [
          '#type' => 'link',
          '#title' => $element['image']['#alt'],
          '#url' => $url,
        ];
      }
      else {
        $elements[] = [
          '#markup' => $element['image']['#uri'],
        ];
      }
    }

    return $elements;
  }

}
