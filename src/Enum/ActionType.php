<?php

namespace App\Enum;

enum ActionType: string
{
    case CREATE = 'create';
    case CREATE_UPPER = 'CREATE';
    case UPDATE = 'update';
    case UPDATE_UPPER = 'UPDATE';
    case VIEW = 'view';
    case VIEW_UPPER = 'VIEW';
    case PRESENCE_INC = 'presence_inc';
    case PRESENCE_INC_UPPER = 'PRESENCE_INC';
    case CODE_VERIFIED = 'code_verified';
    case CODE_VERIFIED_UPPER = 'CODE_VERIFIED';
    case CODE_UNLOCKED = 'code_unlocked';
    case CODE_UNLOCKED_UPPER = 'CODE_UNLOCKED';
    case PRESENCE_SAVED = 'presence_saved';
    case PRESENCE_SAVED_UPPER = 'PRESENCE_SAVED';
    case DELETE = 'delete';
    case DELETE_UPPER = 'DELETE';

}