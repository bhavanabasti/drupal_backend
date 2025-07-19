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
 *   id = "qrcode_url_field_widget",
 *   label = @Translation("QR url field widget"),
 *   field_types = {
 *      "qrcode_url"
 *   }
 * )
 */
class QRFieldUrlWidget extends WidgetBase {

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
   * Constructs a QRFieldUrlWidget object.
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
      'url' => '',
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
    $elements['url'] = [
      '#title' => $this->t('Default url'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('url'),
    ];
    $elements['image'] = [
      '#title' => $this->t('QR widget settings'),
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
    $url = $items[$delta]->url ?? $this->getSetting('url');
    $qrImageActivePlugin = $this->getFieldSetting('qrcode_plugin');
    $imageWidth = $this->getSetting('image')['width'];
    $imageHeight = $this->getSetting('image')['width'];

    $field_edit_form = FALSE;
    $route_match = $this->routeMatch;
    $route_name = $route_match->getRouteName();
    $field_definition = $items[$delta]->getFieldDefinition();
    $field_type = $field_definition->getType();

    if (strpos($route_name, 'field_edit_form') !== FALSE) {
      $field_edit_form = FALSE;
    }

    $element['details'] = [
      '#title' => $element['#title'],
      '#type' => 'details',
      '#open' => TRUE,
    ];
    $element['details']['image'] = $this->qrImage
      ->setPlugin($qrImageActivePlugin)
      ->build(['url' => $url, 'field_type' => $field_type],
                $imageWidth,
                $imageHeight
    );
    $element['details']['url'] = [
      '#title' => $this->t('URL'),
      '#type' => 'textfield',
      '#placeholder' => '',
      '#required' => $field_edit_form,
      '#default_value' => $url,
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [$this->fieldDefinition->getTargetEntityTypeId()],
        '#prefix' => $this->t('Enter the url here (e.g., https://google.com or a valid token like [site:url]).<br>This field supports tokens:'),
        '#suffix' => $this->t('<br><small>Utilize a maximum of 30 characters, either through standard text or by employing token generation.</small>'),
      ],
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $delta => $element) {
      if ($element['details']['url']) {
        $values[$delta]['url'] = $element['details']['url'];
        unset($values[$delta]['details']);
      }
      else {
        unset($values[$delta]);
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($this->getSetting('url')) {
      $summary[] = $this->t('Default url: @url', [
        '@url' => $this->getSetting('url'),
      ]);
    }
    $summary[] = $this->t('QR image dimension: @widthx@height', [
      '@width' => $this->getSetting('image')['width'],
      '@height' => $this->getSetting('image')['height'],
    ]);
    return $summary;
  }

}
