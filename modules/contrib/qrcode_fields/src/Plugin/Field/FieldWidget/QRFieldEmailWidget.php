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
 *   id = "qrcode_email_field_widget",
 *   label = @Translation("Email message field widget"),
 *   field_types = {
 *      "qrcode_email"
 *   }
 * )
 */
class QRFieldEmailWidget extends WidgetBase {

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
   * Constructs a QRFieldEmailWidget object.
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
    // Get the configuration factory service.
    $config_factory = \Drupal::configFactory();

    // Get the site configuration.
    $config = $config_factory->getEditable('system.site');

    // Get the site email address.
    $site_email = $config->get('mail');

    return [
      'email' => $site_email,
      'subject' => '',
      'message' => '',
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

    $elements['email'] = [
      '#title' => $this->t('Email'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('email'),
    ];
    $elements['subject'] = [
      '#title' => $this->t('Subject'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('subject'),
    ];
    $elements['message'] = [
      '#title' => $this->t('Message'),
      '#type' => 'textarea',
      '#default_value' => $this->getSetting('message'),
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
    $field_definition = $items[$delta]->getFieldDefinition();
    $field_type = $field_definition->getType();
    $email = $items[$delta]->email ?? $this->getSetting('email');
    $subject = $items[$delta]->subject ?? $this->getSetting('subject');
    $message = $items[$delta]->message ?? $this->getSetting('message');
    $qrImageActivePlugin = $this->getFieldSetting('qrcode_plugin');
    $imageWidth = $this->getSetting('image')['width'];
    $imageHeight = $this->getSetting('image')['width'];
    $field_edit_form = FALSE;
    $route_match = $this->routeMatch;
    $route_name = $route_match->getRouteName();

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
        'email' => $email,
        'subject' => $subject,
        'message' => $message,
        'field_type' => $field_type,
      ],
                $imageWidth,
                $imageHeight
    );
    $element['details']['email'] = [
      '#title' => $this->t('Email'),
      '#type' => 'textfield',
      '#required' => $field_edit_form,
      '#maxlength' => '50',
      '#placeholder' => '',
      '#default_value' => $email,
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [$this->fieldDefinition->getTargetEntityTypeId()],
        '#prefix' => $this->t('Enter the email recipient here (e.g., your@company.com or a valid token like [site:mail]).<br>This field supports tokens:'),
        '#suffix' => $this->t('<br><small>Utilize a maximum of 30 characters, either through standard text or by employing token generation.</small>'),
      ],
    ];
    $element['details']['subject'] = [
      '#title' => $this->t('Subject'),
      '#type' => 'textfield',
      '#maxlength' => '90',
      '#placeholder' => '',
      '#default_value' => $subject,
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [$this->fieldDefinition->getTargetEntityTypeId()],
        '#prefix' => $this->t('Enter the email subject here (e.g., "Feedback on [node:title] for next newsletter").<br>This field supports tokens:'),
        '#suffix' => $this->t('<br><small>Utilize a maximum of 90 characters, either through standard text or by employing token generation.</small>'),
      ],
    ];
    $element['details']['message'] = [
      '#title' => $this->t('Message'),
      '#type' => 'textarea',
      '#maxlength' => '230',
      '#placeholder' => '',
      '#default_value' => $message,
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [$this->fieldDefinition->getTargetEntityTypeId()],
        '#prefix' => $this->t('Enter the email message here (e.g.: "Hi dear, I hope this message finds you well! I recently came across information about your newsletter, [node:title] , and I am interested in subscribing to stay updated with your latest content and announcements. I appreciate your time and assistance in providing this information. I am looking forward to becoming a part of your newsletter community.").<br>This field supports tokens:'),
        '#suffix' => $this->t('<br><small>Utilize a maximum of 230 characters, either through standard text or by employing token generation.</small>'),
      ],
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {

    foreach ($values as $delta => $element) {
      if ($element['details']['email']) {
        $values[$delta]['email'] = $element['details']['email'];
      }
      if ($element['details']['subject']) {
        $values[$delta]['subject'] = $element['details']['subject'];
      }
      if ($element['details']['message']) {
        $values[$delta]['message'] = $element['details']['message'];
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($this->getSetting('email')) {
      $summary[] = $this->t('Default email recipient: @email', [
        '@email' => $this->getSetting('email'),
      ]);
    }

    if ($this->getSetting('subject')) {
      $summary[] = $this->t('Default email subject: @subject', [
        '@subject' => $this->getSetting('subject'),
      ]);
    }

    if ($this->getSetting('message')) {
      $summary[] = $this->t('Default email message: @message', [
        '@message' => $this->getSetting('message'),
      ]);
    }

    $summary[] = $this->t('QR code image dimension: @widthx@height', [
      '@width' => $this->getSetting('image')['width'],
      '@height' => $this->getSetting('image')['height'],
    ]);
    return $summary;
  }

}
