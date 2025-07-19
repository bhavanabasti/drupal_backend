<?php

namespace Drupal\user_api\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\user\Entity\User;
use Drupal\user_api\Entity\Wallet; 
use Drupal\Component\Uuid\Php as UuidService;

class UserApiController {

  public function register(Request $request) {
    $data = json_decode($request->getContent(), true);
    $name = $data['name'] ?? '';
    $mobile = $data['mobile_number'] ?? '';

    if (empty($name) || empty($mobile)) {
      return new JsonResponse([
        'success' => false,
        'message' => 'Missing name or mobile',
      ]);
    }

    // Check if mobile already exists using field_mobile.
    $query = \Drupal::entityTypeManager()->getStorage('user')->getQuery();
    $query->accessCheck(FALSE);
    $query->condition('field_mobile', $mobile);
    $uids = $query->execute();

    if (!empty($uids)) {
      return new JsonResponse([
        'success' => false,
        'message' => 'Mobile number already registered',
      ], 409);
    }

    // Also check if username (name field) already exists.
    $existing_user = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties(['name' => $name]);


    if (!empty($existing_user)) {
      return new JsonResponse([
        'success' => false,
        'message' => 'Username already exists',
      ], 409);
    }

    // Create a new user
      $user = User::create([
      'name' => $name, // actual user name
      'mail' => $mobile . '@example.com',
      'field_full_name' => $name, // optional if you want full name separately
      'field_mobile' => $mobile,
      'status' => 1,
    ]);
    $user->save();

    return new JsonResponse([
      'success' => true,
      'nid' => $user->id(),
    ]);
  }

  // public function login_qr(Request $request) {
  //   $data = json_decode($request->getContent(), TRUE);
  //   $mobile = $data['mobile'] ?? '';

  //   if (empty($mobile)) {
  //     return new JsonResponse([
  //       'success' => false,
  //       'message' => 'Mobile number is required',
  //     ], 400);
  //   }

  //   $query = \Drupal::entityTypeManager()->getStorage('user')->getQuery();
  //   $query->accessCheck(FALSE);
  //   $query->condition('field_mobile', $mobile);
  //   $uids = $query->execute();

  //   if (!empty($uids)) {
  //     $uid = reset($uids);
  //     return new JsonResponse([
  //       'success' => true,
  //       'nid' => $uid,
  //     ]);
  //   }

  //   return new JsonResponse([
  //     'success' => false,
  //     'message' => 'User not found',
  //   ], 404);
  // }
public function login_qr(Request $request) {
  $data = json_decode($request->getContent(), TRUE);
  $mobile = $data['mobile'] ?? '';

  if (empty($mobile)) {
    return new JsonResponse([
      'success' => false,
      'message' => 'Mobile number is required',
    ], 400);
  }

  $query = \Drupal::entityTypeManager()->getStorage('user')->getQuery();
  $query->accessCheck(FALSE);
  $query->condition('field_mobile', $mobile);
  $uids = $query->execute();

  if (!empty($uids)) {
    $uid = reset($uids);

    // ✅ Check if wallet exists using entity storage
    $wallet_storage = \Drupal::entityTypeManager()->getStorage('wallet');
    $wallets = $wallet_storage->loadByProperties(['uid' => $uid]);

    if (empty($wallets)) {
      // ✅ Create wallet using Drupal's API (automatically handles uuid, created, changed)
      $wallet = $wallet_storage->create([
        'uid' => $uid,
        'balance' => 0.00,
      ]);
      $wallet->save();
    }

  // $user = \Drupal\user\Entity\User::load($uid);
  //   if ($user) {
  //     $user->set('field_last_login', \Drupal::time()->getCurrentTime());
  //     $user->save();
  //   }

  $user = \Drupal\user\Entity\User::load($uid);
if ($user) {
  $user->set('field_last_login', \Drupal::time()->getCurrentTime());
  $user->save();
}

    return new JsonResponse([
      'success' => true,
      'uid' => $uid,
    ]);
  }

  return new JsonResponse([
    'success' => false,
    'message' => 'User not found',
  ], 404);
}



public function saveEnergyReading(Request $request) {
  $data = json_decode($request->getContent(), TRUE);

  $uid = $data['uid'] ?? 0;
  $voltage = $data['voltage'] ?? 0;
  $current = $data['current'] ?? 0;
  $power = $data['power'] ?? 0;
  $energy = $data['energy'] ?? 0;
  $amount = $data['amount'] ?? 0;

  // Save to custom table or log (example only)
  \Drupal::database()->insert('energy_readings')->fields([
    'uid' => $uid,
    'voltage' => $voltage,
    'current' => $current,
    'power' => $power,
    'energy' => $energy,
    'amount' => $amount,
    'created' => \Drupal::time()->getCurrentTime(),
  ])->execute();

  return new JsonResponse(['status' => 'success', 'message' => 'Reading saved.']);
}





}
