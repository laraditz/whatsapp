<?php

namespace Laraditz\Whatsapp\Enums;

enum MessageDirection: string
{
    case Outbound = 'outbound';
    case Inbound = 'inbound';
}
