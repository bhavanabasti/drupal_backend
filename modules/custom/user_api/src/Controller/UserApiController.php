<?php

namespace Drupal\user_api\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\user\Entity\User;
use Drupal\node\Entity\Node; 
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

  
public function login_qr(Request $request) {
  $data = json_decode($request->getContent(), TRUE);
  $mobile = $data['mobile'] ?? '';

  if (empty($mobile)) {
    return new JsonResponse([
      'success' => false,
      'message' => 'Mobile number is required',
    ], 400);
  }

  $query = \Drupal::entityTypeManager()->getStorage(entity_type_id: 'user')->getQuery();
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
  $data = [
    'username' => $request->query->get('username'),
    'status' => $request->query->get('status'),
    'energy' => $request->query->get('energy'),
    'deviceid' => $request->query->get('device_id'),
  ];

  \Drupal::logger('vehicle_app')->info('Received: @data', ['@data' => print_r($data, TRUE)]);

  try {
    $username = $data['username'];
    $status = $data['status'];
    $energy = $data['energy'];
    $deviceid = $data['deviceid'];

    // Get user
    $uids = \Drupal::entityQuery('user')
      ->condition('name', $username)
      ->accessCheck(FALSE)
      ->execute();

    $user = !empty($uids) ? \Drupal\user\Entity\User::load(reset($uids)) : NULL;

    // Always get latest node for username + deviceid
    $existing_nids = \Drupal::entityQuery('node')
      ->condition('type', 'energy_readings_of_user')
      ->condition('field_uid', $username)
      ->condition('field_deviceid', $deviceid)
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->accessCheck(FALSE)
      ->execute();

    $existing_node = !empty($existing_nids) ? \Drupal\node\Entity\Node::load(reset($existing_nids)) : NULL;

    // 🟠 Case 1: If status is "low_current", update final energy
    if ($status === 'low_current') {
      if ($existing_node) {
        $existing_node->set('field_final_energy_reading', $energy);
        $existing_node->save();

        return new \Symfony\Component\HttpFoundation\JsonResponse([
          'status' => 'updated',
          'nid' => $existing_node->id()
        ], 200);
      } else {
        return new \Symfony\Component\HttpFoundation\JsonResponse([
          'status' => 'error',
          'message' => 'No matching record found for low_current update.'
        ], 404);
      }
    }

    // 🟢 Case 2: For high_current or other statuses, create only if:
    // - No existing node, OR
    // - existing node has final_energy_reading 
    $create_new = false;

    if (!$existing_node) {
      $create_new = true;
    } elseif (!$existing_node->get('field_final_energy_reading')->isEmpty()) {
      $create_new = true;
    }

    if ($create_new) {
      $node_fields = [
        'type' => 'energy_readings_of_user',
        'title' => 'Reading for ' . $username,
        'field_uid' => $username,
        'field_deviceid' => $deviceid,
        'field_energy' => $energy,
      ];

      if ($user) {
        $node_fields['field_energy_user'] = ['target_id' => $user->id()];
      }

      $node = \Drupal\node\Entity\Node::create($node_fields);
      $node->save();

      return new \Symfony\Component\HttpFoundation\JsonResponse([
        'status' => 'created',
        'nid' => $node->id()
      ], 200);
    } else {
      return new \Symfony\Component\HttpFoundation\JsonResponse([
        'status' => 'skipped',
        'message' => 'Open energy record exists; not creating a new node.'
      ], 200);
    }
  } catch (\Exception $e) {
    \Drupal::logger('vehicle_app')->error('Save error: @msg', ['@msg' => $e->getMessage()]);
    return new \Symfony\Component\HttpFoundation\JsonResponse([
      'status' => 'error',
      'message' => $e->getMessage()
    ], 500);
  }
}


public function saveDeviceId(Request $request) {
    // Decode the JSON payload
    $data = json_decode($request->getContent(), true);
    
    // Check for JSON decoding errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        return new JsonResponse(['success' => false, 'message' => 'Invalid JSON payload'], 400);
    }

    $username = $data['username'] ?? '';
    $deviceId = $data['device_id'] ?? '';

    if (empty($username) || empty($deviceId)) {
        return new JsonResponse(['success' => false, 'message' => 'Missing username or device_id'], 400);
    }

    // Find the user by username
    $query = \Drupal::entityTypeManager()->getStorage('user')->getQuery();
    $query->condition('name', $username);
    $uids = $query->execute();

    if (!empty($uids)) {
        $uid = reset($uids);
        
        // Create a new node of the content type "Device ID"
        $deviceNode = \Drupal\node\Entity\Node::create([
            'type' => 'device_id', // Replace with your content type machine name
            'title' => 'Device ID for ' . $username,
            'field_device_id' => $deviceId, // Assuming you have a field for device ID
            'uid' => $uid, // Set the user ID
        ]);
        
        // Save the node
        try {
            $deviceNode->save();
            return new JsonResponse(['success' => true, 'message' => 'Device ID saved as a node.']);
        } catch (\Exception $e) {
            \Drupal::logger('user_api')->error('Error saving device ID node: @message', ['@message' => $e->getMessage()]);
            return new JsonResponse(['success' => false, 'message' => 'Error saving device ID'], 500);
        }
    }

    return new JsonResponse(['success' => false, 'message' => 'User  not found'], 404);
}




public function saveScan(Request $request) {
  $deviceid = $request->get('deviceid');
  $username = $request->get('username');
  $status = $request->get('status', 'pending');

  if (!$deviceid || !$username) {
    return new JsonResponse(['message' => 'Missing data'], 400);
  }

  $node = Node::create([
    'type' => 'scan_event', // your content type machine name
    'title' => $username . ' - ' . date('Y-m-d H:i:s'),
    'field_deviceid' => $deviceid,
    'field_username' => $username,
    'field_status' => $status,
  ]);

  $node->save();

  return new JsonResponse(['message' => 'Saved successfully']);
}


public function verifyDevice(Request $request) {
  $data = json_decode($request->getContent(), TRUE);

  $device_id = $data['device_id'] ?? '';
  $username = $data['username'] ?? '';

  if (!$device_id || !$username) {
    return new JsonResponse([
      'success' => false,
      'message' => 'Missing device_id or username'
    ], 400);
  }

  // 1. Check if the device exists using content type "device"
  $nids = \Drupal::entityQuery('node')
    ->condition('type', 'device')
    ->condition('field_device_id', $device_id)
    ->execute();

  if (!empty($nids)) {
    // ✅ Authorized
    $status = "authorized";

    // 2. Load user ID by username
    $uids = \Drupal::entityQuery('user')
      ->condition('name', $username)
      ->execute();

    $user_reference = !empty($uids) ? reset($uids) : NULL;

    // 3. Save new node of type "device_log"
    $log = Node::create([
      'type' => 'device_log',
      'title' => 'Log: ' . $device_id . ' - ' . date('Y-m-d H:i:s'),
      'field_deviceids' => $device_id,
      'field_statuss' => $status,
      'field_scanned_time' => strtotime('now'),
    ]);

    // Only set user reference if found
    if ($user_reference) {
      $log->set('field_user_reference', ['target_id' => $user_reference]);
    }

    $log->save();

    return new JsonResponse([
      'success' => true,
      'status' => $status,
      'message' => 'Device is authorized and log saved',
    ], 200);
  } else {
    // ❌ Unauthorized
    return new JsonResponse([
      'success' => false,
      'status' => 'unauthorized',
      'message' => 'Device not found',
    ], 403);
  }
}




 public function logDeviceEvent(Request $request) {
  // Case 1: Handle POST API call (from Flutter/Arduino)
  if ($request->getMethod() === 'POST') {
    $data = json_decode($request->getContent(), TRUE);
    $mobile = $data['mobile'] ?? '';
    $device_id = $data['device_id'] ?? '';
    $status = $data['status'] ?? '';

    if (empty($mobile) || empty($device_id) || empty($status)) {
      return new JsonResponse([
        'success' => false,
        'message' => 'Missing mobile, device_id, or status',
      ], 400);
    }

    $users = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties(['field_mobile' => $mobile]);

    $user = reset($users);

    if (!$user) {
      return new JsonResponse([
        'success' => false,
        'message' => 'User not found with mobile: ' . $mobile,
      ], 404);
    }

    $node = Node::create([
      'type' => 'device_event_log',
      'title' => 'Log for ' . $device_id,
      'field_deviceids' => $device_id,
      'field_statuss' => $status,
      'field_scanned_time' => \Drupal::time()->getCurrentTime(),
      'field_user_reference' => ['target_id' => $user->id()],
    ]);
    $node->save();

    return new JsonResponse([
      'success' => true,
      'message' => 'Device event logged',
      'nid' => $node->id(),
    ]);
  }

  // Case 2: Handle GET request (show table of logs in browser)
  $header = [
    'title' => $this->t('Title'),
    'device_id' => $this->t('Device ID'),
    'scanned_time' => $this->t('Scanned Time'),
    'status' => $this->t('Status'),
    'mobile' => $this->t('Mobile'),
  ];

  $rows = [];

  $query = \Drupal::entityTypeManager()
    ->getStorage('node')
    ->getQuery()
    ->condition('type', 'device_event_log')
    ->sort('created', 'DESC')
    ->range(0, 50); // latest 50 logs

  $nids = $query->execute();
  $nodes = Node::loadMultiple($nids);

  foreach ($nodes as $node) {
    $device_id = $node->get('field_deviceids')->value ?? '-';
    $status = $node->get('field_statuss')->value ?? '-';
    $timestamp = $node->get('field_scanned_time')->value ?? 0;
    $scanned_time = \Drupal::service('date.formatter')->format($timestamp, 'custom', 'D, d/m/Y - H:i');

    $user = $node->get('field_user_reference')->entity ?? NULL;
    $mobile = $user ? $user->get('field_mobile')->value : '-';

    $rows[] = [
      'data' => [
        $node->label(),
        $device_id,
        $scanned_time,
        $status,
        $mobile,
      ],
    ];
  }

  $build['table'] = [
    '#type' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#empty' => $this->t('No scan logs found.'),
  ];

  return $build;
}

}


//  public function logDeviceEvent(Request $request) {
//     // Get POST JSON body
//     $data = json_decode($request->getContent(), TRUE);

//     if (!$data || !isset($data['device_id']) || !isset($data['mobile']) || !isset($data['status'])) {
//       return new JsonResponse(['error' => 'Missing required parameters.'], 400);
//     }

//     $device_id = $data['device_id'];
//     $mobile = $data['mobile'];
//     $status = $data['status'];

//     // Log for now — later you can insert into DB
//     \Drupal::logger('vehicle_app')->notice("Log received: device_id=$device_id, mobile=$mobile, status=$status");

//     return new JsonResponse([
//       'message' => 'Log received successfully.',
//       'device_id' => $device_id,
//       'mobile' => $mobile,
//       'status' => $status,
//     ]);
//   }
// }


