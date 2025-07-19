<?php

namespace Drupal\qrcode_fields\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Define QR URL service plugin.
 *
 * @Annotation
 */
class QRUrlServicePlugin extends Plugin {

  /**
   * Unique plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * Plugin label.
   *
   * @var string
   */
  public $label;

}
