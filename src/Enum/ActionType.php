<?php

namespace App\Enum;

enum ActionType: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case VIEW = 'view';
}
