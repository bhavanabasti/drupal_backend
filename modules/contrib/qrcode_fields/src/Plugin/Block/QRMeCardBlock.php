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
 * QR mecard block.
 *
 * @Block(
 *   id = "qrcode_mecard_block",
 *   admin_label = @Translation("QR mecard block"),
 *   category = @Translation("QR Code Fields")
 * )
 */
class QRMeCardBlock extends BlockBase implements ContainerFactoryPluginInterface {

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

    $form['fname'] = [
      '#title' => $this->t('First name'),
      '#type' => 'textfield',
      '#default_value' => $config['fname'] ?? '',
      '#required' => TRUE,
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [],
        '#prefix' => $this->t('This field supports tokens.'),
      ],
    ];
    $form['lname'] = [
      '#title' => $this->t('Last name'),
      '#type' => 'textfield',
      '#default_value' => $config['lname'] ?? '',
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [],
        '#prefix' => $this->t('This field supports tokens.'),
      ],
    ];
    $form['email'] = [
      '#title' => $this->t('Email'),
      '#type' => 'textfield',
      '#default_value' => $config['email'] ?? '',
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [],
        '#prefix' => $this->t('This field supports tokens.'),
      ],
    ];
    $form['phone'] = [
      '#title' => $this->t('Phone'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $config['phone'] ?? '',
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [],
        '#prefix' => $this->t('This field supports tokens.'),
      ],
    ];
    $form['address'] = [
      '#title' => $this->t('Address'),
      '#type' => 'textfield',
      '#default_value' => $config['address'] ?? '',
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [],
        '#prefix' => $this->t('This field supports tokens.'),
      ],
    ];
    $form['note'] = [
      '#title' => $this->t('Note'),
      '#type' => 'textares',
      '#default_value' => $config['note'] ?? '',
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [],
        '#prefix' => $this->t('This field supports tokens.'),
      ],
    ];
    $form['organization'] = [
      '#title' => $this->t('Organization'),
      '#type' => 'textfield',
      '#default_value' => $config['organization'] ?? '',
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [],
        '#prefix' => $this->t('This field supports tokens.'),
      ],
    ];
    $form['url'] = [
      '#title' => $this->t('Url'),
      '#type' => 'textfield',
      '#default_value' => $config['url'] ?? '',
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [],
        '#prefix' => $this->t('This field supports tokens.'),
      ],
    ];
    $form['title'] = [
      '#title' => $this->t('Job title'),
      '#type' => 'textfield',
      '#default_value' => $config['title'] ?? '',
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [],
        '#prefix' => $this->t('This field supports tokens.'),
      ],
    ];
    $form['birthday'] = [
      '#title' => $this->t('Birthday'),
      '#type' => 'date',
      '#default_value' => $config['birthday'] ?? '',
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [],
        '#prefix' => $this->t('This field supports tokens.'),
      ],
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

    $this->configuration['fname'] = $form_state->getValue('fname');
    $this->configuration['lname'] = $form_state->getValue('lname');
    $this->configuration['email'] = $form_state->getValue('email');
    $this->configuration['phone'] = $form_state->getValue('phone');
    $this->configuration['address'] = $form_state->getValue('address');
    $this->configuration['url'] = $form_state->getValue('url');
    $this->configuration['note'] = $form_state->getValue('note');
    $this->configuration['organization'] = $form_state->getValue('organization');
    $this->configuration['title'] = $form_state->getValue('title');
    $this->configuration['birthday'] = $form_state->getValue('birthday');
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
                  'fname' => $config['fname'],
                  'lname' => $config['lname'],
                  'email' => $config['email'],
                  'phone' => $config['phone'],
                  'address' => $config['address'],
                  'url' => $config['url'],
                  'note' => $config['note'],
                  'organization' => $config['organization'],
                  'title' => $config['title'],
                  'birthday' => $config['birthday'],
                  'plugin_id' => $this->pluginId,
                  'field_type' => 'qrcode_mecard',
                ],
                 $config['image']['width'], $config['image']['height']);
    if ($config['display_text']) {
      $build['fname'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->token->replace($config['fname']),
        '#attributes' => [
          'class' => 'qrcode_mecard-' . $this->pluginId,
        ],
      ];
      $build['lname'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->token->replace($config['lname']),
        '#attributes' => [
          'class' => 'qrcode_mecard-' . $this->pluginId,
        ],
      ];
      $build['email'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->token->replace($config['email']),
        '#attributes' => [
          'class' => 'qrcode_mecard-' . $this->pluginId,
        ],
      ];
      $build['phone'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->token->replace($config['phone']),
        '#attributes' => [
          'class' => 'qrcode_mecard-' . $this->pluginId,
        ],
      ];
      $build['address'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->token->replace($config['address']),
        '#attributes' => [
          'class' => 'qrcode_mecard-' . $this->pluginId,
        ],
      ];
      $build['url'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->token->replace($config['url']),
        '#attributes' => [
          'class' => 'qrcode_mecard-' . $this->pluginId,
        ],
      ];
      $build['note'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->token->replace($config['note']),
        '#attributes' => [
          'class' => 'qrcode_mecard-' . $this->pluginId,
        ],
      ];
      $build['organization'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->token->replace($config['organization']),
        '#attributes' => [
          'class' => 'qrcode_mecard-' . $this->pluginId,
        ],
      ];
      $build['title'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->token->replace($config['title']),
        '#attributes' => [
          'class' => 'qrcode_mecard-' . $this->pluginId,
        ],
      ];
      $build['birthday'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->token->replace($config['birthday']),
        '#attributes' => [
          'class' => 'qrcode_mecard-' . $this->pluginId,
        ],
      ];
    }

    return $build;
  }

}
