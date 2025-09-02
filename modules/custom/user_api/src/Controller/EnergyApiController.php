<?php

namespace Drupal\user_api\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;

class EnergyApiController extends ControllerBase {
  public function saveEnergyReading(Request $request) {
    $data = json_decode($request->getContent(), TRUE);

    $uid = $data['uid'] ?? 0;
    $voltage = $data['voltage'] ?? 0;
    $current = $data['current'] ?? 0;
    $power = $data['power'] ?? 0;
    $energy = $data['energy'] ?? 0;
    $amount = $data['amount'] ?? 0;

    // Example save to DB
    \Drupal::database()->insert('energy_readings')->fields([
      'uid' => $uid,
      'voltage' => $voltage,
      'current' => $current,
      'power' => $power,
      'energy' => $energy,
      'amount' => $amount,
      'created' => \Drupal::time()->getCurrentTime(),
    ])->execute();

    return new JsonResponse(['status' => 'success', 'message' => 'Reading saved']);
  }
}
