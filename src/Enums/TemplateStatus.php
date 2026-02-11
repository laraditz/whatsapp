<?php

namespace Laraditz\Whatsapp\Enums;

enum TemplateStatus: string
{
    case Approved = 'APPROVED';
    case Pending = 'PENDING';
    case Rejected = 'REJECTED';
}
