<?php

namespace Laraditz\Whatsapp\Channels;

use Illuminate\Notifications\Notification;
use Laraditz\Whatsapp\Messages\WhatsappMessage;
use Laraditz\Whatsapp\Responses\MessageResponse;
use Laraditz\Whatsapp\Whatsapp;

class WhatsappChannel
{
    public function __construct(
        protected Whatsapp $whatsapp,
    ) {}

    public function send(mixed $notifiable, Notification $notification): ?MessageResponse
    {
        /** @var WhatsappMessage $message */
        $message = $notification->toWhatsapp($notifiable);

        $to = $notifiable->routeNotificationFor('whatsapp', $notification);

        if (! $to) {
            return null;
        }

        $whatsapp = $message->account
            ? $this->whatsapp->account($message->account)
            : $this->whatsapp;

        $service = $whatsapp->message()->to($to);

        $service = $service->{$message->type}(...$message->data);

        foreach ($message->components as $component) {
            $service = $service->component(...$component);
        }

        if ($message->customPayload) {
            $service = $service->payload($message->customPayload);
        }

        return $service->send();
    }
}
