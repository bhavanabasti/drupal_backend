<?php

namespace Drupal\ev_wallet_buttons\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

class EvWalletButtonsController extends ControllerBase {

  public function content() {
    $current_user = $this->currentUser();
    $uid = $current_user->id();
    $wallet_id = 'EVWALLET-' . $uid;

    // Generate base URLs dynamically
    $history_url = Url::fromUri('internal:/energy-readings-of-users')->toString();
    $scanner_url = Url::fromUri('internal:/qr-scanner-simple')->toString();

    return [
      '#type' => 'markup',
      '#markup' => '
        <div style="display:flex; flex-direction:column; gap:15px; max-width:300px; margin:auto; padding-top:40px;">
          <a href="' . $history_url . '" class="button">📜 History</a>
          <a href="#" onclick="alert(\'Your Wallet ID: ' . $wallet_id . '\')" class="button">💳 EV Wallet ID</a>
          <a href="' . $scanner_url . '"  class="button">📷 Scan QR</a>

          <!-- QR scanner container -->
          <div id="reader" style="width:100%; max-width:500px; margin:auto; margin-top:12px;"></div>
          <div id="scanResult" style="margin-top:12px;"></div>
        </div>
      ',
      '#attached' => [
        'library' => [
          'ev_wallet_buttons/qr_scanner',
        ],
        'drupalSettings' => [
          'evWallet' => [
            'uid' => $uid,
          ],
        ],
      ],
    ];
  }


}
