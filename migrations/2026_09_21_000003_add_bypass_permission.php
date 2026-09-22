<?php

declare(strict_types=1);

use Flarum\Database\Migration;
use Flarum\Group\Group;

return Migration::addPermissions([
    'lowseekai-content-risk-fee.bypass' => Group::MODERATOR_ID,
]);
