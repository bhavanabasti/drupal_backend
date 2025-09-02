<?php

namespace Drupal\qrcode_fields\Plugin\qrcode_fields;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Url;
use Drupal\qrcode_fields\QRUrlServicePluginInterface;

/**
 * QR service plugin implementation.
 *
 * @QRUrlServicePlugin(
 *   id = "gchart",
 *   label = "Google Chart API (deprecated)"
 * )
 *
 * Format example:
 *  https://chart.apis.google.com/chart?cht=qr&chs=300x300&chl=DATA&chld=H|0
 */
class GoogleChartAPI extends PluginBase implements QRUrlServicePluginInterface {

  /**
   * Service API URL.
   *
   * @var string
   */
  protected $url = 'https://chart.apis.google.com/chart';

  /**
   * QR URL query params.
   *
   * @var array
   *  Array of params.
   */
  protected $urlQueryParams = [
    'cht' => 'qr',
    'chld' => 'H|0',
  ];

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return Url::fromUri($this->url, [
      'query' => $this->getUrlQueryParams(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlQueryParams() {
    return $this->urlQueryParams += [
      'chl' => $this->configuration['data'],
      'chs' => "{$this->configuration['image_width']}x{$this->configuration['image_height']}",
    ];
  }

}
