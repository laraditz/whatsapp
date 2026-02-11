<?php

namespace Laraditz\Whatsapp\Enums;

enum MessageStatus: string
{
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Read = 'read';
    case Failed = 'failed';
}
