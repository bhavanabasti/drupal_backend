<?php

namespace Drupal\qrcode_fields\PLugin\Block;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\qrcode_fields\Service\QRImageInterface;
use Drupal\token\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * QR url block.
 *
 * @Block(
 *   id = "qrcode_url_block",
 *   admin_label = @Translation("QR url block"),
 *   category = @Translation("QR Code Fields")
 * )
 */
class QRUrlBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    PluginManagerInterface $pluginManager,
    QRImageInterface $qrImage,
    Token $token,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pluginManager = $pluginManager;
    $this->qrImage = $qrImage;
    $this->token = $token;
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
    $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'qrcode_plugin' => 'goqr',
      'text' => $this->t('Block: Enter you QR url here (e.g.: Welcome to [site:name] [site:url])'),
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
    $form['url'] = [
      '#title' => $this->t('URL'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $config['url'],
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [],
        '#prefix' => $this->t('This field supports tokens.'),
      ],
    ];
    $form['display_text'] = [
      '#title' => $this->t('Display QR url'),
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
    $this->configuration['url'] = $form_state->getValue('url');
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
                  'url' => $config['url'],
                  'plugin_id' => $this->pluginId,
                  'field_type' => 'qrcode_url',
                ], $config['image']['width'], $config['image']['height']);
    if ($config['display_text']) {
      $build['url'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->token->replace($config['url']),
        '#attributes' => [
          'class' => 'qrcode_fields-' . $this->pluginId,
        ],
      ];
    }
    return $build;
  }

}
