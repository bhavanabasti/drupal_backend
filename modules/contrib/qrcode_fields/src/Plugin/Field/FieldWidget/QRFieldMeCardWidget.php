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
 *   id = "qrcode_mecard_field_widget",
 *   label = @Translation("QR MeCard field widget"),
 *   field_types = {
 *      "qrcode_mecard"
 *   }
 * )
 */
class QRFieldMeCardWidget extends WidgetBase {

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
   * Constructs a QRFieldMeCardWidget object.
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
      'fname' => '',
      'lname' => '',
      'email' => '',
      'phone' => '',
      'address' => '',
      'url' => '',
      'note' => '',
      'organization' => '',
      'title' => '',
      'birthday' => '',
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
    $elements['fname'] = [
      '#title' => $this->t('Default first name'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('fname'),
    ];
    $elements['lname'] = [
      '#title' => $this->t('Default last name'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('lname'),
    ];
    $elements['email'] = [
      '#title' => $this->t('Default email'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('email'),
    ];
    $elements['phone'] = [
      '#title' => $this->t('Default phone'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('phone'),
    ];
    $elements['address'] = [
      '#title' => $this->t('Default address'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('address'),
    ];
    $elements['url'] = [
      '#title' => $this->t('Default url'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('url'),
    ];
    $elements['note'] = [
      '#title' => $this->t('Default note'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('note'),
    ];
    $elements['organization'] = [
      '#title' => $this->t('Default organization'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('organization'),
    ];
    $elements['title'] = [
      '#title' => $this->t('Default job title'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('title'),
    ];
    $elements['birthday'] = [
      '#title' => $this->t('Default birthday'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('birthday'),
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
    $fname = $items[$delta]->fname ?? $this->getSetting('fname');
    $lname = $items[$delta]->lname ?? $this->getSetting('lname');
    $email = $items[$delta]->email ?? $this->getSetting('email');
    $phone = $items[$delta]->phone ?? $this->getSetting('phone');
    $address = $items[$delta]->address ?? $this->getSetting('address');
    $url = $items[$delta]->url ?? $this->getSetting('url');
    $note = $items[$delta]->note ?? $this->getSetting('note');
    $organization = $items[$delta]->organization ?? $this->getSetting('organization');
    $title = $items[$delta]->title ?? $this->getSetting('title');
    $birthday = $items[$delta]->birthday ?? $this->getSetting('birthday');
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
      ->build([
        'name' => $fname,
        'lname' => $lname,
        'email' => $email,
        'phone' => $phone,
        'address' => $address,
        'url' => $url,
        'note' => $note,
        'organization' => $organization,
        'title' => $title,
        'birthday' => $birthday,
        'field_type' => $field_type,
      ],
                $imageWidth,
                $imageHeight
    );
    $element['details']['fname'] = [
      '#title' => $this->t('First name'),
      '#type' => 'textfield',
      '#required' => $field_edit_form,
      '#placeholder' => '',
      '#default_value' => $fname,
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [$this->fieldDefinition->getTargetEntityTypeId()],
        '#prefix' => $this->t('Enter the name of the contact here (e.g., John or a valid token like [node:field_first_name]).<br>This field supports tokens:'),
        '#suffix' => $this->t('<br><small>Utilize a maximum of 30 characters, either through standard text or by employing token generation.</small>'),
      ],
    ];
    $element['details']['lname'] = [
      '#title' => $this->t('Last name'),
      '#type' => 'textfield',
      '#placeholder' => '',
      '#default_value' => $lname,
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [$this->fieldDefinition->getTargetEntityTypeId()],
        '#prefix' => $this->t('Enter the last name of the contact here (e.g., Doe or a valid token like [node:field_last_name]).<br>This field supports tokens:'),
        '#suffix' => $this->t('<br><small>Utilize a maximum of 30 characters, either through standard text or by employing token generation.</small>'),
      ],
    ];
    $element['details']['email'] = [
      '#title' => $this->t('Email'),
      '#type' => 'textfield',
      '#placeholder' => '',
      '#default_value' => $email,
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [$this->fieldDefinition->getTargetEntityTypeId()],
        '#prefix' => $this->t('Enter the email of the contact here (e.g., Doe or a valid token like [site:mail]).<br>This field supports tokens:'),
        '#suffix' => $this->t('<br><small>Utilize a maximum of 30 characters, either through standard text or by employing token generation.</small>'),
      ],
    ];
    $element['details']['phone'] = [
      '#title' => $this->t('Phone'),
      '#required' => $field_edit_form,
      '#type' => 'textfield',
      '#placeholder' => '',
      '#default_value' => $phone,
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [$this->fieldDefinition->getTargetEntityTypeId()],
        '#prefix' => $this->t('Enter the phone of the contact here (e.g., 123456789 or a valid token like [node:field_phone]).<br>This field supports tokens:'),
        '#suffix' => $this->t('<br><small>Utilize a maximum of 30 characters, either through standard text or by employing token generation.</small>'),
      ],
    ];
    $element['details']['address'] = [
      '#title' => $this->t('Address'),
      '#type' => 'textfield',
      '#placeholder' => '',
      '#default_value' => $address,
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [$this->fieldDefinition->getTargetEntityTypeId()],
        '#prefix' => $this->t('Enter the address of the contact here (e.g., 123 Main St, Cityville, CA, 12345, USA or a valid token like [node:field_address]).<br>This field supports tokens:'),
        '#suffix' => $this->t('<br><small>Utilize a maximum of 30 characters, either through standard text or by employing token generation.</small>'),
      ],
    ];
    $element['details']['url'] = [
      '#title' => $this->t('URL'),
      '#type' => 'textfield',
      '#placeholder' => '',
      '#default_value' => $url,
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [$this->fieldDefinition->getTargetEntityTypeId()],
        '#prefix' => $this->t('Enter the url of the contact here (e.g., http://www.example.com or a valid token like [node:field_url]).<br>This field supports tokens:'),
        '#suffix' => $this->t('<br><small>Utilize a maximum of 30 characters, either through standard text or by employing token generation.</small>'),
      ],
    ];
    $element['details']['note'] = [
      '#title' => $this->t('Note'),
      '#type' => 'textarea',
      '#placeholder' => '',
      '#default_value' => $note,
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [$this->fieldDefinition->getTargetEntityTypeId()],
        '#prefix' => $this->t('Enter any additional notes or information of the contact here (e.g., Met at the conference or a valid token like [node:field_note]).<br>This field supports tokens:'),
        '#suffix' => $this->t('<br><small>Utilize a maximum of 30 characters, either through standard text or by employing token generation.</small>'),
      ],
    ];
    $element['details']['organization'] = [
      '#title' => $this->t('Organization'),
      '#type' => 'textfield',
      '#placeholder' => '',
      '#default_value' => $organization,
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [$this->fieldDefinition->getTargetEntityTypeId()],
        '#prefix' => $this->t('Enter the organization or company the contact is associated with the contact here (e.g., ABC Corporation or a valid token like [node:field_organization]).<br>This field supports tokens:'),
        '#suffix' => $this->t('<br><small>Utilize a maximum of 30 characters, either through standard text or by employing token generation.</small>'),
      ],
    ];
    $element['details']['title'] = [
      '#title' => $this->t('Job title'),
      '#type' => 'textfield',
      '#placeholder' => '',
      '#default_value' => $title,
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [$this->fieldDefinition->getTargetEntityTypeId()],
        '#prefix' => $this->t('Enter the job title associated with the contact here (e.g., Manager or a valid token like [node:field_job_title]).<br>This field supports tokens:'),
        '#suffix' => $this->t('<br><small>Utilize a maximum of 30 characters, either through standard text or by employing token generation.</small>'),
      ],
    ];
    $element['details']['birthday'] = [
      '#title' => $this->t('Birthday'),
      '#type' => 'date',
      '#placeholder' => '',
      '#default_value' => $birthday,
      '#description' => $this->t('Enter the birthday of the contact'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {

    foreach ($values as $delta => $element) {

      if ($element['details']['fname']) {
        $values[$delta]['fname'] = $element['details']['fname'];
      }

      if ($element['details']['lname']) {
        $values[$delta]['lname'] = $element['details']['lname'];
      }

      if ($element['details']['email']) {
        $values[$delta]['email'] = $element['details']['email'];
      }

      if ($element['details']['phone']) {
        $values[$delta]['phone'] = $element['details']['phone'];
      }

      if ($element['details']['address']) {
        $values[$delta]['address'] = $element['details']['address'];
      }

      if ($element['details']['url']) {
        $values[$delta]['url'] = $element['details']['url'];
      }

      if ($element['details']['note']) {
        $values[$delta]['note'] = $element['details']['note'];
      }

      if ($element['details']['organization']) {
        $values[$delta]['organization'] = $element['details']['organization'];
      }

      if ($element['details']['title']) {
        $values[$delta]['title'] = $element['details']['title'];
      }

      if ($element['details']['birthday']) {
        $values[$delta]['birthday'] = $element['details']['birthday'];
      }

    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($this->getSetting('fname')) {
      $summary[] = $this->t('Default first name: @fname', [
        '@fname' => $this->getSetting('fname'),
      ]);
    }
    if ($this->getSetting('lname')) {
      $summary[] = $this->t('Default last name: @lname', [
        '@lname' => $this->getSetting('lname'),
      ]);
    }
    if ($this->getSetting('email')) {
      $summary[] = $this->t('Default email: @email', [
        '@email' => $this->getSetting('email'),
      ]);
    }
    if ($this->getSetting('phone')) {
      $summary[] = $this->t('Default phone: @phone', [
        '@phone' => $this->getSetting('phone'),
      ]);
    }
    if ($this->getSetting('address')) {
      $summary[] = $this->t('Default address: @address', [
        '@address' => $this->getSetting('address'),
      ]);
    }
    if ($this->getSetting('url')) {
      $summary[] = $this->t('Default url: @url', [
        '@url' => $this->getSetting('url'),
      ]);
    }
    if ($this->getSetting('note')) {
      $summary[] = $this->t('Default note: @note', [
        '@note' => $this->getSetting('note'),
      ]);
    }
    if ($this->getSetting('organization')) {
      $summary[] = $this->t('Default organization: @organization', [
        '@organization' => $this->getSetting('organization'),
      ]);
    }
    if ($this->getSetting('title')) {
      $summary[] = $this->t('Default title: @title', [
        '@title' => $this->getSetting('title'),
      ]);
    }
    if ($this->getSetting('birthday')) {
      $summary[] = $this->t('Default birthday: @birthday', [
        '@birthday' => $this->getSetting('birthday'),
      ]);
    }

    $summary[] = $this->t('QR image dimension: @widthx@height', [
      '@width' => $this->getSetting('image')['width'],
      '@height' => $this->getSetting('image')['height'],
    ]);
    return $summary;
  }

}
