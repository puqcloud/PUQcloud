<?php

namespace App\Enums;

enum ServiceStatus: string
{
    case Active = 'active';
    case Pending = 'pending';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';
    case Terminated = 'terminated';
    case Error = 'error';
    case Deploying = 'deploying';
}
