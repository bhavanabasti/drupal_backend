<?php

namespace Drupal\user_api\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\user\Entity\User;

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

  public function login(Request $request) {
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
      return new JsonResponse([
        'success' => true,
        'nid' => $uid,
      ]);
    }

    return new JsonResponse([
      'success' => false,
      'message' => 'User not found',
    ], 404);
  }
}
