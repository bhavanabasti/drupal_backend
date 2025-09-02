<?php

namespace Drupal\qrcode_fields\Plugin\Field\FieldWidget;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\qrcode_fields\Service\QRImageInterface;
use Drupal\token\Token;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'qrcode_fields_widget'.
 *
 * @FieldWidget(
 *   id = "qrcode_event_field_widget",
 *   label = @Translation("QR MeCard field widget"),
 *   field_types = {
 *      "qrcode_event"
 *   }
 * )
 */
class QRFieldEventWidget extends WidgetBase {

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
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a QRFieldEventWidget object.
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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   A config factory for retrieving required config objects.
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
    ConfigFactoryInterface $configFactory,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->fieldDefinition = $field_definition;
    $this->settings = $settings;
    $this->thirdPartySettings = $third_party_settings;
    $this->token = $token_service;
    $this->qrImage = $qrImage;
    $this->routeMatch = $route_match;
    $this->configFactory = $configFactory;
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
    $container->get('current_route_match'),
    $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'summary' => '',
      'description' => '',
      'location' => '',
      'dstart' => '',
      'dend' => '',
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
    $timezone = $this->configFactory->get('system.date')->get('timezone.default');
    // $timezone = 'UTC';
    $elements = [];
    $elements['summary'] = [
      '#title' => $this->t('Default event title'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('summary'),
    ];
    $elements['description'] = [
      '#title' => $this->t('Default description'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('description'),
    ];
    $elements['location'] = [
      '#title' => $this->t('Default location'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('location'),
    ];
    $elements['dstart'] = [
      '#title' => $this->t('Default event start date'),
      '#type' => 'datetime',
      '#default_value' => $this->getSetting('dstart') ?? '',
      '#date_increment' => 1,
      '#date_timezone' => $timezone,
    ];
    $elements['dend'] = [
      '#title' => $this->t('Default event end date'),
      '#type' => 'datetime',
      '#default_value' => $this->getSetting('dend') ?? '',
      '#date_increment' => 1,
      '#date_timezone' => $timezone,
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
    $summary = $items[$delta]->summary ?? $this->getSetting('summary');
    $description = $items[$delta]->description ?? $this->getSetting('description');
    $location = $items[$delta]->location ?? $this->getSetting('location');
    $dstart = $items[$delta]->dstart ?? $this->getSetting('dstart');
    $dend = $items[$delta]->dend ?? $this->getSetting('dend');
    $datetimeObject = new DrupalDateTime($dstart, DateTimeItemInterface::STORAGE_TIMEZONE);
    $timestamp = $datetimeObject->getTimestamp();
    $timezone = $this->configFactory->get('system.date')->get('timezone.default');

    if ($dstart) {
      $dstart = DrupalDateTime::createFromTimestamp($timestamp);
    }

    if ($dend) {
      $datetimeObject = new DrupalDateTime($dend, DateTimeItemInterface::STORAGE_TIMEZONE);
      $timestamp = $datetimeObject->getTimestamp();
      $dend = DrupalDateTime::createFromTimestamp($timestamp);
    }

    $qrImageActivePlugin = $this->getFieldSetting('qrcode_plugin');
    $imageWidth = $this->getSetting('image')['width'];
    $imageHeight = $this->getSetting('image')['width'];
    $route_match = $this->routeMatch;
    $route_name = $route_match->getRouteName();
    $field_edit_form = FALSE;
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
      ->build([
        'name' => $summary,
        'description' => $description,
        'location' => $location,
        'dstart' => $dstart,
        'dend' => $dend,
        'field_type' => $field_type,
      ],
                $imageWidth,
                $imageHeight
    );
    $year = date('Y');
    $element['details']['summary'] = [
      '#title' => $this->t('Event title'),
      '#type' => 'textfield',
      '#required' => $field_edit_form,
      '#placeholder' => '',
      '#default_value' => $summary,
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [$this->fieldDefinition->getTargetEntityTypeId()],
        '#prefix' => $this->t('Enter the title of the event here (e.g., "Envision @year: Innovation Summit" or a valid token like [node:field_event_title]).<br>This field supports tokens:', ['@year' => $year]),
        '#suffix' => $this->t('<br><small>Utilize a maximum of 30 characters, either through standard text or by employing token generation.</small>'),
      ],
    ];
    $element['details']['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textfield',
      '#placeholder' => '',
      '#default_value' => $description,
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [$this->fieldDefinition->getTargetEntityTypeId()],
        '#prefix' => $this->t('Enter the description of the event here (e.g., "Join us for an evening of inspiration and connection!" or a valid token like [node:field_event_description]).<br>This field supports tokens:'),
        '#suffix' => $this->t('<br><small>Utilize a maximum of 30 characters, either through standard text or by employing token generation.</small>'),
      ],
    ];
    $element['details']['location'] = [
      '#title' => $this->t('Location'),
      '#type' => 'textfield',
      '#placeholder' => '',
      '#default_value' => $location,
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [$this->fieldDefinition->getTargetEntityTypeId()],
        '#prefix' => $this->t('Enter the location of the event here (e.g., "123 Main Street, Cityville, State, 12345" or a valid token like [node:field_event_location]).<br>This field supports tokens:'),
        '#suffix' => $this->t('<br><small>Utilize a maximum of 30 characters, either through standard text or by employing token generation.</small>'),
      ],
    ];

    $element['details']['dstart'] = [
      '#title' => $this->t('Start date'),
      '#type' => 'datetime',
      '#required' => $field_edit_form,
      '#placeholder' => '',
      '#default_value' => $dstart,
      '#description' => $this->t('Enter the start date of the event here'),
      '#date_increment' => 1,
      '#date_timezone' => $timezone,
    ];
    $element['details']['dend'] = [
      '#title' => $this->t('End date'),
      '#type' => 'datetime',
      '#placeholder' => '',
      '#default_value' => $dend,
      '#description' => $this->t('Enter the end date of the event here'),
      '#date_increment' => 1,
      '#date_timezone' => $timezone,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {

    foreach ($values as $delta => $element) {

      if ($element['details']['summary']) {
        $values[$delta]['summary'] = $element['details']['summary'];
      }

      if ($element['details']['description']) {
        $values[$delta]['description'] = $element['details']['description'];
      }

      if ($element['details']['location']) {
        $values[$delta]['location'] = $element['details']['location'];
      }

      if ($element['details']['dstart']) {
        $dstart = $element['details']['dstart'];
        if ($dstart instanceof DrupalDateTime) {
          $to = DrupalDateTime::createFromFormat('U', $dstart->getTimestamp())->format('Y-m-d H:i:s');
          $values[$delta]['dstart'] = $to;
        }
      }

      if ($element['details']['dend']) {
        $dend = $element['details']['dend'];
        if ($dend instanceof DrupalDateTime) {
          $to = DrupalDateTime::createFromFormat('U', $dend->getTimestamp())->format('Y-m-d H:i:s');
          $values[$delta]['dend'] = $to;
        }
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($this->getSetting('summary')) {
      $summary[] = $this->t('Default first name: @summary', [
        '@summary' => $this->getSetting('summary'),
      ]);
    }
    if ($this->getSetting('description')) {
      $summary[] = $this->t('Default last name: @description', [
        '@description' => $this->getSetting('description'),
      ]);
    }
    if ($this->getSetting('location')) {
      $summary[] = $this->t('Default location: @location', [
        '@location' => $this->getSetting('location'),
      ]);
    }
    if ($this->getSetting('dstart')) {
      $summary[] = $this->t('Default dstart: @dstart', [
        '@dstart' => $this->getSetting('dstart'),
      ]);
    }
    if ($this->getSetting('dend')) {
      $summary[] = $this->t('Default dend: @dend', [
        '@dend' => $this->getSetting('dend'),
      ]);
    }

    $summary[] = $this->t('QR image dimension: @widthx@height', [
      '@width' => $this->getSetting('image')['width'],
      '@height' => $this->getSetting('image')['height'],
    ]);
    return $summary;
  }

}
