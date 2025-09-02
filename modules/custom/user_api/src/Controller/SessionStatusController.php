<?php

namespace Drupal\user_api\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Controller\ControllerBase;

class SessionStatusController extends ControllerBase {
public function check() {
$user = \Drupal::currentUser();

if ($user->isAnonymous()) {
return new JsonResponse(['logged_in' => false]);
}

return new JsonResponse([
'logged_in' => true,
'uid' => $user->id(),
'name' => $user->getDisplayName(),
]);
}
}