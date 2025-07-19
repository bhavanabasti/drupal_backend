<?php

namespace Drupal\qrcode_fields\PLugin\Block;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\qrcode_fields\Service\QRImageInterface;
use Drupal\token\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * QR event block.
 *
 * @Block(
 *   id = "qrcode_event_block",
 *   admin_label = @Translation("QR event block"),
 *   category = @Translation("QR Code Fields")
 * )
 */
class QREventBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * QR image service.
   *
   * @var \Drupal\qrcode_fields\Service\QRImageInterface
   */
  protected $qrImage;

  /**
   * Token service.
   *
   * @var \Drupal\token\Token
   */
  protected $token;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    PluginManagerInterface $pluginManager,
    QRImageInterface $qrImage,
    Token $token,
    ConfigFactoryInterface $configFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pluginManager = $pluginManager;
    $this->qrImage = $qrImage;
    $this->token = $token;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
    $configuration,
    $plugin_id,
    $plugin_definition,
    $container->get('plugin.manager.qrcode_fields'),
    $container->get('qrcode_fields.qrimage'),
    $container->get('token'),
    $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'qrcode_plugin' => 'goqr',
      'summary' => '',
      'description' => '',
      'location' => '',
      'dstart' => '',
      'dend' => '',
      'display_text' => FALSE,
      'image' => [
        'width' => 200,
        'height' => 200,
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    $timezone = $this->configFactory->get('system.date')->get('timezone.default');
    $dstart = $config['dstart'] ?? '';
    if ($dstart) {
      $dstart = new DrupalDateTime($dstart);
    }
    $dend = $config['dend'] ?? '';
    if ($dend) {
      $dend = new DrupalDateTime($dend);
    }

    $form['summary'] = [
      '#title' => $this->t('Event title'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $config['summary'] ?? '',
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [],
        '#prefix' => $this->t('This field supports tokens.'),
      ],
    ];
    $form['description'] = [
      '#title' => $this->t('Event description'),
      '#type' => 'textfield',
      '#default_value' => $config['description'] ?? '',
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [],
        '#prefix' => $this->t('This field supports tokens.'),
      ],
    ];
    $form['location'] = [
      '#title' => $this->t('Event location'),
      '#type' => 'textfield',
      '#default_value' => $config['location'] ?? '',
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [],
        '#prefix' => $this->t('This field supports tokens.'),
      ],
    ];
    $form['dstart'] = [
      '#title' => $this->t('Event dstart'),
      '#type' => 'datetime',
      '#required' => TRUE,
      '#default_value' => $dstart,
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [],
        '#prefix' => $this->t('This field supports tokens.'),
      ],
      '#date_timezone' => $timezone,
    ];
    $form['dend'] = [
      '#title' => $this->t('Event dend'),
      '#type' => 'datetime',
      '#default_value' => $dend,
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [],
        '#prefix' => $this->t('This field supports tokens.'),
      ],
      '#date_timezone' => $timezone,
    ];
    $form['display_text'] = [
      '#title' => $this->t('Display text'),
      '#type' => 'checkbox',
      '#description' => $this->t('Shows text encoded in QR code.'),
      '#default_value' => $config['display_text'],
    ];
    $form['qrcode_plugin'] = [
      '#title' => $this->t('QR code service plugin'),
      '#type' => 'select',
      '#options' => $this->pluginManager->getDefinitionsList(),
      '#description' => $this->t('Service to use for QR code generation.'),
      '#default_value' => $config['qrcode_plugin'],
    ];
    $form['image'] = [
      '#type' => 'container',
    ];
    $form['image']['label'] = [
      '#title' => $this->t('QR image dimensions'),
      '#type' => 'label',
    ];
    $form['image']['width'] = [
      '#title' => $this->t('Width'),
      '#type' => 'number',
      '#default_value' => $config['image']['width'],
      '#placeholder' => $this->t('Width'),
    ];
    $form['image']['height'] = [
      '#title' => $this->t('Height'),
      '#type' => 'number',
      '#default_value' => $config['image']['height'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $dstart = $form_state->getValue('dstart');
    $dend = $form_state->getValue('dend');
    if ($dstart instanceof DrupalDateTime) {
      $dstart = DrupalDateTime::createFromFormat('U', $dstart->getTimestamp())->format('Y-m-d H:i:s');
    }
    if ($dend instanceof DrupalDateTime) {
      $dend = DrupalDateTime::createFromFormat('U', $dend->getTimestamp())->format('Y-m-d H:i:s');
    }

    $this->configuration['summary'] = $form_state->getValue('summary');
    $this->configuration['description'] = $form_state->getValue('description');
    $this->configuration['location'] = $form_state->getValue('location');
    $this->configuration['dstart'] = $dstart;
    $this->configuration['dend'] = $dend;
    $this->configuration['display_text'] = $form_state->getValue('display_text');
    $this->configuration['qrcode_plugin'] = $form_state->getValue('qrcode_plugin');
    $this->configuration['image']['width'] = $form_state->getValue('image')['width'];
    $this->configuration['image']['height'] = $form_state->getValue('image')['height'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $build = [];
    $build['image'] = $this->qrImage
      ->setPlugin($config['qrcode_plugin'])
      ->build(
                [
                  'summary' => $config['summary'],
                  'description' => $config['description'],
                  'location' => $config['location'],
                  'dstart' => $config['dstart'],
                  'dend' => $config['dend'],
                  'plugin_id' => $this->pluginId,
                  'field_type' => 'qrcode_event',
                ],
                 $config['image']['width'], $config['image']['height']);
    if ($config['display_text']) {
      $build['summary'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->token->replace($config['summary']),
        '#attributes' => [
          'class' => 'qrcode_event-' . $this->pluginId,
        ],
      ];
      $build['description'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->token->replace($config['description']),
        '#attributes' => [
          'class' => 'qrcode_event-' . $this->pluginId,
        ],
      ];
      $build['location'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->token->replace($config['location']),
        '#attributes' => [
          'class' => 'qrcode_event-' . $this->pluginId,
        ],
      ];
      $build['dstart'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->token->replace($config['dstart']),
        '#attributes' => [
          'class' => 'qrcode_event-' . $this->pluginId,
        ],
      ];
      $build['dend'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->token->replace($config['dend']),
        '#attributes' => [
          'class' => 'qrcode_event-' . $this->pluginId,
        ],
      ];
    }

    return $build;
  }

}
