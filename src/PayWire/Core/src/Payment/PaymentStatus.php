<?php

namespace PayWire\Core\Payment;

enum PaymentStatus: string
{
    case NEW = 'new'; // payment created locally
    case SUBMITTED = 'submitted'; // payment submitted and partner created own ID
    case COMPLETED = 'completed'; // paid, user charged
    case CANCELED = 'canceled'; // payment canceled, cannot retry it
    case FAILED = 'failed'; // payment failed, cannot retry it
}
