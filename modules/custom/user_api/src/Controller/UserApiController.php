<?php

namespace Drupal\user_api\Controller;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\user\Entity\User;
use Drupal\node\Entity\Node;
use Drupal\user_api\Entity\Wallet;
use Drupal\Component\Uuid\Php as UuidService;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Controller\ControllerBase;



class UserApiController
{

  public function register(Request $request)
  {

    $data = json_decode($request->getContent(), true);
    $name = $data['name'] ?? '';
    $mobile = $data['mobile_number'] ?? '';

    if (empty($name) || empty($mobile)) {
      return new JsonResponse([
        'success' => false,
        'message' => 'Missing name or mobile',
      ]);
    }


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
      'field_full_name' => $name,
      'field_mobile' => $mobile,
      'status' => 1,
    ]);
    $user->save();
return new JsonResponse([
  'success' => true,
  'uid' => $user->id(),
  'name' => $user->getAccountName(),
  'mobile' => $mobile,
]);

  }


  // public function login_qr(Request $request)
  // {
  //   $data = json_decode($request->getContent(), TRUE);
  //   $mobile = $data['mobile'] ?? '';

  //   if (empty($mobile)) {
  //     return new JsonResponse([
  //       'success' => false,
  //       'message' => 'Mobile number is required',
  //     ], 400);
  //   }

  //   $query = \Drupal::entityTypeManager()->getStorage(entity_type_id: 'user')->getQuery();
  //   $query->accessCheck(FALSE);
  //   $query->condition('field_mobile', $mobile);
  //   $uids = $query->execute();

  //   if (!empty($uids)) {
  //     $uid = reset($uids);

  //     // ✅ Check if wallet exists using entity storage
  //     $wallet_storage = \Drupal::entityTypeManager()->getStorage('wallet');
  //     $wallets = $wallet_storage->loadByProperties(['uid' => $uid]);

  //     if (empty($wallets)) {

  //       $wallet = $wallet_storage->create([
  //         'uid' => $uid,
  //         'balance' => 0.00,
  //       ]);
  //       $wallet->save();
  //     }

  //    $user = \Drupal\user\Entity\User::load($uid);
  //   if ($user) {
  //     $user->setLastAccessTime(\Drupal::time()->getCurrentTime());
  //     $user->save();
  //   }

  //     return new JsonResponse([
  //       'success' => true,
  //       'uid' => $uid,
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

    // ✅ Ensure wallet exists
    $wallet_storage = \Drupal::entityTypeManager()->getStorage('wallet');
    $wallets = $wallet_storage->loadByProperties(['uid' => $uid]);
    if (empty($wallets)) {
      $wallet = $wallet_storage->create([
        'uid' => $uid,
        'balance' => 0.00,
      ]);
      $wallet->save();
    }

    // ✅ Load user details
    $user = \Drupal\user\Entity\User::load($uid);
    if ($user) {
      $user->setLastAccessTime(\Drupal::time()->getCurrentTime());
      $user->save();

      return new JsonResponse([
        'success' => true,
        'uid' => $uid,
        'username' => $user->getAccountName(), // 👈 add username
        'mail' => $user->getEmail(),          // 👈 optional
        'mobile' => $mobile,                  // 👈 return same mobile
      ]);
    }
  }

  return new JsonResponse([
    'success' => false,
    'message' => 'User not found',
  ], 404);
}


  // public function saveEnergyReading(Request $request)
  // {
  //   $now = new DrupalDateTime();
  //   $data = [
  //      'uid' => $request->query->get('uid'),
  //     'username' => $request->query->get('username'),
  //     'status' => $request->query->get('status'),
  //     'energy' => $request->query->get('energy'),
  //     'deviceid' => $request->query->get('device_id'),
  //   ];

    // $data = [
    //    'uid' => '15',
    //   'username' => 'admin',
    //   'status' => 'created',
    //   'energy' => '1132',
    //   'deviceid' => 'EV00123',
    // ];

  //   \Drupal::logger('vehicle_app')->info('Received: @data', ['@data' => print_r($data, TRUE)]);

  //   try {
  //     $username = $data['username'];
  //     $status = $data['status'];
  //     $energy = $data['energy'];
  //     $deviceid = $data['deviceid'];


  //     $uids = \Drupal::entityQuery('user')
  //       ->condition('name', $username)
  //       ->accessCheck(FALSE)
  //       ->execute();

  //     $user = !empty($uids) ? \Drupal\user\Entity\User::load(reset($uids)) : NULL;


  //     $existing_nids = \Drupal::entityQuery('node')
  //       ->condition('type', 'energy_readings_of_user')
  //       ->condition('field_uid', $username)
  //       ->condition('field_deviceid', $deviceid)
  //       ->sort('created', 'DESC')
  //       ->range(0, 1)
  //       ->accessCheck(FALSE)
  //       ->execute();

  //     $existing_node = !empty($existing_nids) ? \Drupal\node\Entity\Node::load(reset($existing_nids)) : NULL;


  //     if ($status === 'low_current') {
  //       if ($existing_node) {
  //         $existing_node->set('field_final_energy_reading', $energy);


  //         $initial_energy = $existing_node->get('field_energy')->value;

  //         if (is_numeric($initial_energy) && is_numeric($energy)) {
  //           $consumed = floatval($energy) - floatval($initial_energy);
  //           $existing_node->set('field_energy_consumed', $consumed);


  //           \Drupal::logger('vehicle_app')->info('Energy consumed: @consumed', ['@consumed' => $consumed]);


  //           $config = \Drupal::config('vehicle_app_config.settings');
  //           $rate = $config->get('energy_rate') ?? 9;

  //           $amount = $consumed * $rate;


  //           $existing_node->set('field_amount', $amount);
  //           \Drupal::logger('vehicle_app')->info('Amount (Energy x Rate): @amount (Rate: @rate)', [
  //             '@amount' => $amount,
  //             '@rate' => $rate,
  //           ]);
  //         }

  //         $datetime = new DrupalDateTime(); // current time
  //         $existing_node->set('field_plugged_out_time', $datetime->getTimestamp());


  //         $existing_node->save();
  //         return new \Symfony\Component\HttpFoundation\JsonResponse([
  //           'status' => 'updated',
  //           'nid' => $existing_node->id()
  //         ], 200);
  //       } else {
  //         return new \Symfony\Component\HttpFoundation\JsonResponse([
  //           'status' => 'error',
  //           'message' => 'No matching record found for low_current update.'
  //         ], 404);
  //       }
  //     }

  //     $create_new = false;

  //     if (!$existing_node) {
  //       $create_new = true;
  //     } elseif (!$existing_node->get('field_final_energy_reading')->isEmpty()) {
  //       $create_new = true;
  //     }

  //     if ($create_new) {
  //       $node_fields = [
  //         'type' => 'energy_readings_of_user',
  //         'title' => 'Reading for ' . $username,
  //         'field_uid' => $username,
  //         'field_deviceid' => $deviceid,
  //         'field_energy' => $energy,
  //         'field_plugged_in_time' => $now->getTimestamp(),
  //       ];


  //       if ($user) {
  //         $node_fields['field_energy_user'] = ['target_id' => $user->id()];
  //       }

  //       $node = \Drupal\node\Entity\Node::create($node_fields);
  //       $node->save();

  //       return new \Symfony\Component\HttpFoundation\JsonResponse([
  //         'status' => 'created',
  //         'nid' => $node->id()
  //       ], 200);
  //     } else {
  //       return new \Symfony\Component\HttpFoundation\JsonResponse([
  //         'status' => 'skipped',
  //         'message' => 'Open energy record exists; not creating a new node.'
  //       ], 200);
  //     }
  //   } catch (\Exception $e) {
  //     \Drupal::logger('vehicle_app')->error('Save error: @msg', ['@msg' => $e->getMessage()]);
  //     return new \Symfony\Component\HttpFoundation\JsonResponse([
  //       'status' => 'error',
  //       'message' => $e->getMessage()
  //     ], 500);
  //   }
  // }


//   public function saveEnergyReading(Request $request) {
//   $now = new DrupalDateTime();

//   // Example data (replace with $request->query->get(...) in real use)

//     $data = [
//        'uid' => $request->query->get('uid'),
//       'username' => $request->query->get('username'),
//       'status' => $request->query->get('status'),
//       'energy' => $request->query->get('energy'),
//       'deviceid' => $request->query->get('device_id'),
//     ];

// $uid = \Drupal::currentUser()->id();
//   \Drupal::logger('vehicle_app')->info('Received: @data', ['@data' => print_r($data, TRUE)]);

//   try {
//     $username = $data['username'];
//     $status = $data['status'];
//     $energy = $data['energy'];
//     $deviceid = $data['deviceid'];

//     // Find user entity by username
//     $uids = \Drupal::entityQuery('user')
//       ->condition('name', $username)
//       ->accessCheck(FALSE)
//       ->execute();

//     $user = !empty($uids) ? \Drupal\user\Entity\User::load(reset($uids)) : NULL;
//     \Drupal::logger('vehicle_app')->info('User lookup for "@username": UID=@uid', [
//       '@username' => $username,
//       '@uid' => $user ? $user->id() : 'none',
//     ]);

//     // Find existing node
//     $existing_nids = \Drupal::entityQuery('node')
//       ->condition('type', 'energy_readings_of_user')
//       ->condition('field_uid', $username)
//       ->condition('field_deviceid', $deviceid)
//       ->sort('created', 'DESC')
//       ->range(0, 1)
//       ->accessCheck(FALSE)
//       ->execute();

//     $existing_node = !empty($existing_nids) ? \Drupal\node\Entity\Node::load(reset($existing_nids)) : NULL;

//     // Handle "low_current" case (closing an existing record)
//     if ($status === 'low_current') {
//       if ($existing_node) {
//         $existing_node->set('field_final_energy_reading', $energy);

//         $initial_energy = $existing_node->get('field_energy')->value;
//         if (is_numeric($initial_energy) && is_numeric($energy)) {
//           $consumed = floatval($energy) - floatval($initial_energy);
//           $existing_node->set('field_energy_consumed', $consumed);
//           \Drupal::logger('vehicle_app')->info('Energy consumed: @consumed', ['@consumed' => $consumed]);

//           $config = \Drupal::config('vehicle_app_config.settings');
//           $rate = $config->get('energy_rate') ?? 9;
//           $amount = $consumed * $rate;

//           $existing_node->set('field_amount', $amount);
//           \Drupal::logger('vehicle_app')->info(
//             'Amount (Energy x Rate): @amount (Rate: @rate)',
//             ['@amount' => $amount, '@rate' => $rate]
//           );
//         }

//         $datetime = new DrupalDateTime();
//         $existing_node->set('field_plugged_out_time', $datetime->getTimestamp());
//         $existing_node->save();

//         return new \Symfony\Component\HttpFoundation\JsonResponse([
//           'status' => 'updated',
//           'nid' => $existing_node->id(),
//         ], 200);
//       } else {
//         return new \Symfony\Component\HttpFoundation\JsonResponse([
//           'status' => 'error',
//           'message' => 'No matching record found for low_current update.'
//         ], 404);
//       }
//     }

//     // Decide whether to create new node
//     $create_new = false;
//     if (!$existing_node) {
//       $create_new = true;
//     } elseif (!$existing_node->get('field_final_energy_reading')->isEmpty()) {
//       $create_new = true;
//     }

//     if ($create_new) {
//       $node_fields = [
//         'type' => 'energy_readings_of_user',
//         'title' => 'Reading for ' . $username,
//         'uid' => $uid , // store plain text username
//         'field_deviceid' => $deviceid,
//         'field_energy' => $energy,
//         'field_plugged_in_time' => $now->getTimestamp(),
//       ];

//       if ($user) {
//         $uid_val = $user->id();
//         \Drupal::logger('vehicle_app')->info(
//           'Attaching user reference: UID=@uid for username=@username',
//           ['@uid' => $uid_val, '@username' => $username]
//         );
//         $node_fields['field_energy_user'] = ['target_id' => $uid_val];
//       } else {
//         \Drupal::logger('vehicle_app')->warning(
//           'No Drupal user found for username=@username',
//           ['@username' => $username]
//         );
//       }

//       $node = \Drupal\node\Entity\Node::create($node_fields);
//       $node->save();

//       \Drupal::logger('vehicle_app')->info(
//         'Node created with NID=@nid and linked to user UID=@uid',
//         ['@nid' => $node->id(), '@uid' => $user ? $user->id() : 'none']
//       );

//       return new \Symfony\Component\HttpFoundation\JsonResponse([
//         'status' => 'created',
//         'nid' => $node->id(),
//       ], 200);
//     } else {
//       return new \Symfony\Component\HttpFoundation\JsonResponse([
//         'status' => 'skipped',
//         'message' => 'Open energy record exists; not creating a new node.'
//       ], 200);
//     }
//   } catch (\Exception $e) {
//     \Drupal::logger('vehicle_app')->error('Save error: @msg', ['@msg' => $e->getMessage()]);
//     return new \Symfony\Component\HttpFoundation\JsonResponse([
//       'status' => 'error',
//       'message' => $e->getMessage(),
//     ], 500);
//   }
// }

  

public function saveEnergyReading(Request $request) {
  $now = new DrupalDateTime();
  $data = [
    'username' => $request->query->get('username'),
    'status' => $request->query->get('status'),
    'energy' => $request->query->get('energy'),
    'deviceid' => $request->query->get('device_id'),
  ];

    //   $data = [
    //    'uid' => '15',
    //   'username' => 'admin',
    //   'status' => 'created',
    //   'energy' => '1132',
    //   'deviceid' => 'EV00123',
    // ];

  \Drupal::logger('vehicle_app')->info('📥 Incoming request: @data', ['@data' => print_r($data, TRUE)]);

  try {
    $username = $data['username'];
    $status = $data['status'];
    $energy = $data['energy'];
    $deviceid = $data['deviceid'];

    // Find user by username
    $uids = \Drupal::entityQuery('user')
      ->condition('name', $username)
      ->accessCheck(FALSE)
      ->execute();

    \Drupal::logger('vehicle_app')->info('🔍 User lookup for "@user": found IDs = @uids', [
      '@user' => $username,
      '@uids' => print_r($uids, TRUE),
    ]);

    $user = !empty($uids) ? \Drupal\user\Entity\User::load(reset($uids)) : NULL;

    if ($user) {
      \Drupal::logger('vehicle_app')->info('✅ Loaded user: @id (@name)', [
        '@id' => $user->id(),
        '@name' => $user->getAccountName(),
      ]);
    } else {
      \Drupal::logger('vehicle_app')->warning('⚠️ No Drupal user found for username: @user', ['@user' => $username]);
    }

    // Find latest node for same user + device
    $existing_nids = \Drupal::entityQuery('node')
      ->condition('type', 'energy_readings_of_user')
      ->condition('field_uid', $username)
      ->condition('field_deviceid', $deviceid)
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->accessCheck(FALSE)
      ->execute();

    \Drupal::logger('vehicle_app')->info('📦 Existing node IDs = @nids', ['@nids' => print_r($existing_nids, TRUE)]);

    $existing_node = !empty($existing_nids) ? \Drupal\node\Entity\Node::load(reset($existing_nids)) : NULL;

    // If status = low_current → update existing
    if ($status === 'low_current') {
      if ($existing_node) {
        \Drupal::logger('vehicle_app')->info('🔄 Updating existing node ID: @nid', ['@nid' => $existing_node->id()]);

        $existing_node->set('field_final_energy_reading', $energy);

        $initial_energy = $existing_node->get('field_energy')->value;
        \Drupal::logger('vehicle_app')->info('⚡ Initial energy = @initial | Final energy = @final', [
          '@initial' => $initial_energy,
          '@final' => $energy,
        ]);

        if (is_numeric($initial_energy) && is_numeric($energy)) {
          $consumed = floatval($energy) - floatval($initial_energy);
          $existing_node->set('field_energy_consumed', $consumed);
          \Drupal::logger('vehicle_app')->info('📊 Energy consumed = @consumed', ['@consumed' => $consumed]);

          $config = \Drupal::config('vehicle_app_config.settings');
          $rate = $config->get('energy_rate') ?? 9;
          $amount = $consumed * $rate;

          $existing_node->set('field_amount', $amount);
          \Drupal::logger('vehicle_app')->info('💰 Amount calculated = @amount (rate @rate)', [
            '@amount' => $amount,
            '@rate' => $rate,
          ]);
        }

        $datetime = new DrupalDateTime();
        $existing_node->set('field_plugged_out_time', $datetime->getTimestamp());

        $existing_node->save();
        return new JsonResponse(['status' => 'updated', 'nid' => $existing_node->id()], 200);
      } else {
        return new JsonResponse(['status' => 'error', 'message' => 'No matching record found for low_current update.'], 404);
      }
    }

    // Otherwise create a new node if needed
            $create_new = (!$existing_node || !$existing_node->get('field_final_energy_reading')->isEmpty());

            if ($create_new) {
              \Drupal::logger('vehicle_app')->info('🆕 Creating new node for user @user, device @device', [
                '@user' => $username,
                '@device' => $deviceid,
              ]);

              
            $node_fields = [
                'type' => 'energy_readings_of_user',
                'title' => 'Reading for ' . $username,
                'field_uid' => $username,
                'field_deviceid' => $deviceid,
                'field_energy' => $energy,
                'field_plugged_in_time' => $now->getTimestamp(),
                'uid' => $user ? $user->id() : 0, // 👈 attach node to the Drupal user as author
              ];

              if ($user) {
                $node_fields['field_energy_user'] = ['target_id' => $user->id()];
              }


              $node = \Drupal\node\Entity\Node::create($node_fields);
              $node->save();

              \Drupal::logger('vehicle_app')->info('✅ New node created ID: @nid', ['@nid' => $node->id()]);


            if ($user && $user->hasField('field_node_reference')) {
              $user->get('field_node_reference')->appendItem(['target_id' => $node->id()]);
              $user->save();

              \Drupal::logger('vehicle_app')->info('🔗 Linked node @nid to user @uid', [
                '@nid' => $node->id(),
                '@uid' => $user->id(),
              ]);
            }


              return new JsonResponse(['status' => 'created', 'nid' => $node->id()], 200);
            } else {
              return new JsonResponse(['status' => 'skipped', 'message' => 'Open energy record exists; not creating a new node.'], 200);
            }
            } catch (\Exception $e) {
                \Drupal::logger('vehicle_app')->error('💥 Save error: @msg', ['@msg' => $e->getMessage()]);
                return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
              }
            }
///login//
public function login(Request $request) {
    $data = json_decode($request->getContent(), TRUE);
    $username = $data['name'] ?? '';
    $password = $data['pass'] ?? '';

    $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $username]);
    $user = reset($users);

    if ($user instanceof User && $user->isActive() && \Drupal::service('password')->check($password, $user->getPassword())) {
        $secret = \Drupal::service('key.repository')->getKey('jwt_key')->getKeyValue();

        $payload = [
            'uid' => $user->id(),
            'name' => $user->getAccountName(),
            'exp' => time() + 3600,
        ];

        $token = JWT::encode($payload, $secret, 'HS256');

        return new JsonResponse([
            'current_user' => ['uid' => $user->id(), 'name' => $user->getAccountName()],
            'token' => $token,
        ]);
    }

    return new JsonResponse(['error' => 'Invalid credentials'], 403);
  }


public function currentUser(Request $request) {
  $authHeader = $request->headers->get('Authorization');
  if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    return new JsonResponse(['message' => 'Not logged in'], 401);
  }

  $jwt = $matches[1];

  // Load JWT secret from Drupal Key module
  $key_storage = \Drupal::service('key.repository')->getKey('jwt_key');
  $secret = $key_storage->getKeyValue();

  try {
    $payload = \Firebase\JWT\JWT::decode($jwt, new \Firebase\JWT\Key($secret, 'HS256'));
    return new JsonResponse([
      'uid' => $payload->uid,
      'username' => $payload->name,
    ]);
  } catch (\Exception $e) {
    return new JsonResponse(['message' => 'Invalid token'], 401);
  }
}


public function getUsernameFromMobile(Request $request) {
  $mobile = $request->request->get('mobile');

  if (!$mobile) {
    return new JsonResponse(['status' => 'error', 'message' => 'Mobile is required'], 400);
  }

  $uids = \Drupal::entityQuery('user')
    ->condition('field_mobile', $mobile) // Adjust to your mobile field machine name
    ->accessCheck(FALSE)
    ->execute();

  if (empty($uids)) {
    return new JsonResponse(['status' => 'error', 'message' => 'User not found'], 404);
  }

  $user = \Drupal\user\Entity\User::load(reset($uids));
  return new JsonResponse([
    'status' => 'success',
    'username' => $user->getAccountName(),
  ], 200);
}






public function deviceEventLog(Request $request) {
  // Get raw content
  $raw = $request->getContent();

  // Decode JSON safely
  $data = json_decode($raw, TRUE);

  if (json_last_error() !== JSON_ERROR_NONE) {
    return new JsonResponse([
      'error' => 'Invalid JSON',
      'raw' => $raw,
    ], 400);
  }

  // Debug: log what we got
  \Drupal::logger('user_api')->notice('<pre>@data</pre>', ['@data' => print_r($data, TRUE)]);

  $uid = $data['uid'] ?? NULL;
  $device_id = $data['device_id'] ?? NULL;
  $mobile = $data['mobile'] ?? NULL;
  $status = $data['status'] ?? 'unknown';
  $scanned_time = $data['scanned_time'] ?? \Drupal::time()->getCurrentTime();

  // Validate
  if (empty($uid) || empty($device_id)) {
    return new JsonResponse([
      'error' => 'Missing uid or device_id',
      'received' => $data,
    ], 400);
  }

  try {
    // Create node of type device_event_log
    $node = Node::create([
      'type' => 'device_event_log',
      'title' => "Log for user $uid",
      'field_deviceids' => $device_id,
      'field_mobile' => $mobile,
      'field_scanned_time' => $scanned_time,
      'field_statuss' => $status,
      'field_user_reference' => ['target_id' => $uid],
    ]);
    $node->save();

    return new JsonResponse([
      'message' => 'Event logged successfully',
      'nid' => $node->id(),
      'uid' => $uid,
      'device_id' => $device_id,
    ], 200);

  } catch (\Exception $e) {
    return new JsonResponse([
      'error' => 'Failed to log event',
      'details' => $e->getMessage(),
    ], 500);
  }
}



}
