<?php

namespace App\Enum;

enum ActionType: string
{
    case CREATE = 'CREATE';
    case UPDATE = 'UPDATE';
    case VIEW = 'VIEW';
    
    
    // Add these two to match your JavaFX logAction calls
    case PRESENCE_INC = 'PRESENCE_INC';
    case CODE_VERIFIED = 'CODE_VERIFIED';
    case CODE_UNLOCKED = 'CODE_UNLOCKED';
    case PRESENCE_SAVED= 'PRESENCE_SAVED';
    case DELETE = 'DELETE';

}