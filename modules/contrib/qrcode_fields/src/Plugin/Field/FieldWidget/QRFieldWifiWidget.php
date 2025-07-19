<?php

namespace Drupal\qrcode_fields\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\qrcode_fields\Service\QRImageInterface;
use Drupal\token\Token;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'qrcode_fields_widget'.
 *
 * @FieldWidget(
 *   id = "qrcode_wifi_field_widget",
 *   label = @Translation("WiFi field widget"),
 *   field_types = {
 *      "qrcode_wifi"
 *   }
 * )
 */
class QRFieldWifiWidget extends WidgetBase {

  use StringTranslationTrait;

  /**
   * Token service.
   *
   * @var \Drupal\token\Token
   */
  protected $token;

  /**
   * QR image service.
   *
   * @var \Drupal\qrcode_fields\Service\QRImageInterface
   */
  protected $qrImage;

  /**
   * Current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a QRFieldWifiWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\token\Token $token_service
   *   Token service.
   * @param \Drupal\qrcode_fields\Service\QRImageInterface $qrImage
   *   QR image service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Current route match service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    Token $token_service,
    QRImageInterface $qrImage,
    RouteMatchInterface $route_match,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->fieldDefinition = $field_definition;
    $this->settings = $settings;
    $this->thirdPartySettings = $third_party_settings;
    $this->token = $token_service;
    $this->qrImage = $qrImage;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
    $plugin_id,
    $plugin_definition,
    $configuration['field_definition'],
    $configuration['settings'],
    $configuration['third_party_settings'],
    $container->get('token'),
    $container->get('qrcode_fields.qrimage'),
    $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'network_name' => '',
      'password' => '',
      'hidden' => '',
      'encryption' => '',
      'image' => [
        'width' => 200,
        'height' => 200,
      ],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['network_name'] = [
      '#title' => $this->t('Network name'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('network_name'),
    ];
    $elements['password'] = [
      '#title' => $this->t('Password'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('password'),
    ];
    $elements['hidden'] = [
      '#title' => $this->t('Hidden'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('hidden'),
    ];
    $elements['encryption'] = [
      '#title' => $this->t('Encryption'),
      '#type' => 'radios',
      '#default_value' => $this->getSetting('encryption'),
      '#options' => [
        'nopass' => $this->t('None'),
        'WPA' => $this->t('WPA/WPA2'),
        'WEP' => $this->t('WEP'),
      ],
    ];
    $elements['image'] = [
      '#title' => $this->t('QR code widget settings'),
      '#type' => 'container',
    ];
    $elements['image']['width'] = [
      '#title' => $this->t('Width'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('image')['width'],
    ];
    $elements['image']['height'] = [
      '#title' => $this->t('Height'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('image')['height'],
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $route_match = $this->routeMatch;
    $route_name = $route_match->getRouteName();
    $field_edit_form = FALSE;

    if (strpos($route_name, 'field_edit_form') !== FALSE) {
      $field_edit_form = FALSE;
    }

    $network_name = $items[$delta]->network_name ?? $this->getSetting('network_name');
    $password = $items[$delta]->password ?? $this->getSetting('password');
    $hidden = $items[$delta]->hidden ?? $this->getSetting('hidden');
    $encryption = $items[$delta]->encryption ?? $this->getSetting('encryption');

    $field_definition = $items[$delta]->getFieldDefinition();
    $field_type = $field_definition->getType();

    $qrImageActivePlugin = $this->getFieldSetting('qrcode_plugin');
    $imageWidth = $this->getSetting('image')['width'];
    $imageHeight = $this->getSetting('image')['width'];

    $element['details'] = [
      '#title' => $element['#title'],
      '#type' => 'details',
      '#open' => TRUE,
    ];
    $element['details']['image'] = $this->qrImage
      ->setPlugin($qrImageActivePlugin)
      ->build([
        'network_name' => $network_name,
        'password' => $password,
        'hidden' => $hidden,
        'encryption' => $encryption,
        'field_type' => $field_type,
      ],
                $imageWidth,
                $imageHeight
    );

    $element['details']['network_name'] = [
      '#title' => $this->t('Network name'),
      '#type' => 'textfield',
      '#maxlength' => '30',
      '#required' => $field_edit_form,
      '#placeholder' => '',
      '#default_value' => $network_name,
      '#description' => $this->t('Enter WiFi network name'),
    ];
    $element['details']['password'] = [
      '#title' => $this->t('Password'),
      '#type' => 'textfield',
      '#maxlength' => '90',
      '#placeholder' => '',
      '#default_value' => $password,
      '#description' => $this->t('Enter WiFi password'),
    ];
    $element['details']['hidden'] = [
      '#title' => $this->t('Hidden'),
      '#type' => 'checkbox',
      '#placeholder' => '',
      '#default_value' => $hidden,
      '#description' => $this->t('Is this a hidden WiFi network? ='),
    ];
    $element['details']['encryption'] = [
      '#title' => $this->t('Encryption'),
      '#type' => 'radios',
      '#placeholder' => '',
      '#default_value' => $encryption,
      '#options' => [
        'nopass' => $this->t('None'),
        'WPA' => $this->t('WPA/WPA2'),
        'WEP' => $this->t('WEP'),
      ],
      '#description' => $this->t('The type of security protocol on your network.'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {

    foreach ($values as $delta => $element) {
      if ($element['details']['network_name']) {
        $values[$delta]['network_name'] = $element['details']['network_name'];
      }
      if ($element['details']['password']) {
        $values[$delta]['password'] = $element['details']['password'];
      }
      if ($element['details']['hidden']) {
        $values[$delta]['hidden'] = $element['details']['hidden'];
      }
      if ($element['details']['encryption']) {
        $values[$delta]['encryption'] = $element['details']['encryption'];
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($this->getSetting('network_name')) {
      $summary[] = $this->t('Default network name: @network_name', [
        '@network_name' => $this->getSetting('network_name'),
      ]);
    }

    if ($this->getSetting('password')) {
      $summary[] = $this->t('Default wifi password: @password', [
        '@password' => $this->getSetting('password'),
      ]);
    }

    if ($this->getSetting('hidden')) {
      $summary[] = $this->t('Default hidden WiFi network: @hidden', [
        '@hidden' => $this->getSetting('hidden'),
      ]);
    }

    if ($this->getSetting('encryption')) {
      $summary[] = $this->t('Default encryption: @encryption', [
        '@encryption' => $this->getSetting('encryption'),
      ]);
    }

    $summary[] = $this->t('QR code image dimension: @widthx@height', [
      '@width' => $this->getSetting('image')['width'],
      '@height' => $this->getSetting('image')['height'],
    ]);
    return $summary;
  }

}
