<?php

namespace Drupal\qr_scanner_simple\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use GuzzleHttp\Exception\RequestException;
use Drupal\node\Entity\Node;


/**
 * Provides the simple QR scanner page.
 */
class QrScannerSimpleController extends ControllerBase {

  /**
   * Page callback for scanner page.
   */
  public function page(): array {
    return [
      '#theme' => 'qr_scanner_simple_page',
      '#attached' => [
        'library' => ['qr_scanner_simple/scanner'],
        'drupalSettings' => [
          'qr_scanner_simple' => [
            'stopAfterFirst' => FALSE,
          ],
        ],
      ],
    ];
  }

  /**
   * AJAX callback to receive scanned QR value.
   */
  // public function submit(Request $request): JsonResponse {
  //   $qrValue = $request->request->get('qr_value');
  //   if (!$qrValue) {
  //     $data = json_decode($request->getContent(), TRUE);
  //     $qrValue = $data['qr_value'] ?? '';
  //   }

  //   if (!$qrValue) {
  //     \Drupal::logger('qr_scanner_simple')->warning('⚠️ QR scan received but empty value');
  //     return new JsonResponse(['status' => 'error', 'message' => 'Empty QR value'], 400);
  //   }

  //   // ✅ Build payload like Flutter
  //   $currentUser = \Drupal::currentUser();
  //   $payload = [
  //     'uid' => $currentUser->id(),
  //     'username' => $currentUser->getDisplayName(),
  //     'device_id' => $qrValue,
  //     'status' => 'success',
  //   ];

  //   $httpClient = \Drupal::httpClient();
  //   $responses = [];

  //   // 1️⃣ Send to Drupal internal API (device_event_log)
  //   try {
  //     $drupalResp = $httpClient->post('http://172.16.218.68/vehicle_app/api/device_event_log', [
  //       'json' => $payload,
  //     ]);
  //     $responses['drupal'] = json_decode($drupalResp->getBody()->getContents(), TRUE);
  //   }
  //   catch (RequestException $e) {
  //     \Drupal::logger('qr_scanner_simple')->error('❌ Error sending to Drupal API: @msg', ['@msg' => $e->getMessage()]);
  //     $responses['drupal'] = ['error' => $e->getMessage()];
  //   }

  //   // 2️⃣ Send to ESP API (check_device)
  //   try {
  //     $espResp = $httpClient->post('http://172.16.236.120/api/check_device', [
  //       'json' => $payload,
  //     ]);
  //     $responses['esp'] = json_decode($espResp->getBody()->getContents(), TRUE);
  //   }
  //   catch (RequestException $e) {
  //     \Drupal::logger('qr_scanner_simple')->error('❌ Error sending to ESP API: @msg', ['@msg' => $e->getMessage()]);
  //     $responses['esp'] = ['error' => $e->getMessage()];
  //   }

  //   // ✅ Log scanned value
  //   \Drupal::logger('qr_scanner_simple')->notice('📷 Scanned QR Value: @val | Responses: @resp', [
  //     '@val' => $qrValue,
  //     '@resp' => json_encode($responses),
  //   ]);

  //   return new JsonResponse([
  //     'status' => 'success',
  //     'received' => $qrValue,
  //     'responses' => $responses,
  //   ]);
  // }


public function submit(Request $request): JsonResponse {
  $qrValue = $request->request->get('qr_value');
  if (!$qrValue) {
    $data = json_decode($request->getContent(), TRUE);
    $qrValue = $data['qr_value'] ?? '';
  }

  if (!$qrValue) {
    return new JsonResponse(['status' => 'error', 'message' => 'Empty QR value'], 400);
  }

  $currentUser = \Drupal::currentUser();

  // 🔎 Get latest node for this device
  $last_nid = \Drupal::entityQuery('node')
    ->condition('type', 'energy_readings_of_user')
    ->condition('field_deviceid', $qrValue)
    ->sort('created', 'DESC')
    ->range(0, 1)
    ->accessCheck(FALSE)
    ->execute();

  // $last_node = !empty($last_nid) ? Node::load(reset($last_nid)) : NULL;
  $last_node = !empty($last_nid) ? \Drupal\node\Entity\Node::load(reset($last_nid)) : NULL;


  if ($last_node && $last_node->get('field_final_energy_reading')->isEmpty()) {
    // 🚨 Last session still open → block new scan
    return new JsonResponse([
      'status' => 'error',
      'message' => '⚠️ This QR code is already in use (charging in progress).',
      'received' => $qrValue,
    ], 403);
  }

  // ✅ No open session → allow new scan
  $payload = [
    'uid' => $currentUser->id(),
    'username' => $currentUser->getDisplayName(),
    'device_id' => $qrValue,
    'status' => 'success',
  ];

  $httpClient = \Drupal::httpClient();
  $responses = [];

  try {
    $drupalResp = $httpClient->post('http://172.16.218.68/vehicle_app/api/device_event_log', [
      'json' => $payload,
    ]);
    $responses['drupal'] = json_decode($drupalResp->getBody()->getContents(), TRUE);
  }
  catch (\Exception $e) {
    $responses['drupal'] = ['error' => $e->getMessage()];
  }

  try {
    $espResp = $httpClient->post('http://172.16.236.120/api/check_device', [
      'json' => $payload,
    ]);
    $responses['esp'] = json_decode($espResp->getBody()->getContents(), TRUE);
  }
  catch (\Exception $e) {
    $responses['esp'] = ['error' => $e->getMessage()];
  }

  \Drupal::logger('qr_scanner_simple')->notice('📷 Scanned QR Value: @val | Responses: @resp', [
    '@val' => $qrValue,
    '@resp' => json_encode($responses),
  ]);

  return new JsonResponse([
    'status' => 'success',
    'received' => $qrValue,
    'responses' => $responses,
  ]);
}

}
