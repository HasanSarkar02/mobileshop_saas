<?php

namespace App\Listeners;

use App\Events\CustomerPaymentRecorded;
use App\Services\CustomerFollowUpService;
use Illuminate\Events\Dispatcher;

class CustomerFollowUpEventSubscriber
{
    public function __construct(private readonly CustomerFollowUpService $followUps) {}

    public function handleCustomerPaymentRecorded(CustomerPaymentRecorded $event): void
    {
        $this->followUps->handlePaymentRecorded($event->transaction, $event->customer, $event->shop);
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(CustomerPaymentRecorded::class, [self::class, 'handleCustomerPaymentRecorded']);
    }
}