<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class WorkflowNotification extends Notification
{
    public function __construct(private string $event,private array $payload=[]){}
    public function via(object $notifiable): array{return ['database'];}
    public function toArray(object $notifiable): array{return ['event'=>$this->event,...$this->payload];}
    public function databaseType(object $notifiable): string{return $this->event;}
}