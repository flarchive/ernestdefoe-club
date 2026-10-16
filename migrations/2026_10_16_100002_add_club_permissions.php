<?php

use Flarum\Database\Migration;
use Flarum\Group\Group;

return Migration::addPermissions([
    'ernestdefoe-club.join' => Group::MEMBER_ID,
    'ernestdefoe-club.create' => Group::MODERATOR_ID,
    'ernestdefoe-club.moderate' => Group::MODERATOR_ID,
]);
