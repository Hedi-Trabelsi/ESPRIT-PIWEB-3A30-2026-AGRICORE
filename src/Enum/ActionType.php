<?php

namespace App\Enum;

enum ActionType: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case VIEW = 'view';
    
    // Add these two to match your JavaFX logAction calls
    case PRESENCE_INC = 'PRESENCE_INC';
    case CODE_VERIFIED = 'CODE_VERIFIED';
    case CODE_UNLOCKED = 'CODE_UNLOCKED';
    case PRESENCE_SAVED= 'PRESENCE_SAVED';
}