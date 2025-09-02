<?php

namespace Drupal\webcam_reg\Controller;

use Drupal\Core\Controller\ControllerBase;

class QrScannerController extends ControllerBase {
  public function scannerPage() {
    $build = [];
    $build['#attached']['library'][] = 'webcam_reg/qrscanner';

    $build['content'] = [
      '#markup' => '<div id="preview" style="width:100%; height:400px; background:#000;"></div>
                    <button id="startScan">Start Scan</button>
                    <p id="result"></p>',
    ];
    return $build;
  }
}
