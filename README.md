**<p style="text-align:center;">!!! LEARNING ONLY !!!</p>**

_This solution is intended for educational purposes. Its aim is to demonstrate the possibilities of handling payments in a 'modern way'._

_It may become, same day, a fully functional package intended for production use (probably not :smile:)._

_Warning!!!_
_It has never been used in a production environment. It is not tested with real-world scenarios. It most likely contains bugs and shortcomings.
Contact me if you like it and wish to use it, please._

# PayWire

A modern system that integrates various payment service providers. An approach loosely inspired by [Payum](https://github.com/Payum/Payum).

Version numbering strategy:

* no version number – experiments are being carried out; many changes causing incompatibility; a clash of different ideas. No tests, just happy
  programming. Maybe will be squashed at the end of it to keep clean git history.
* 0.0.x – complete concepts one by one
* 0.x – we now have a fairly stable concepts and several working solutions
* 1.x – stable release, ready for real scenarios

---

## Structure

* `src/PayWire/Core` - core PHP functionality for handling payments
* `src/PayWire/Bridge/*` - bridges for specific use case, for example Symfony bundle
* `src/PayWire/Gateway/*` - gateway implementations for different payment providers: PayU, PayPal, etc.

---

### Symfony bridge

#### Configuration

`composer require vda-soft/paywire-bridge-symfony vda-soft/paywire-payu`

Settings:

```yaml
pay_wire:
  logger: null # (default). Put your logger instance here. Must implement psr LoggerInterface.
  continue_url: null # partner do his stuff and redirect to this route, thank you page for example
  notify_url: null # webhook url, partner will notify this route
  outbox_event_bus:
    enabled: true # (default: false)
    table_name: 'outbox_events' # (default)
    # Used for store events on SQL table (to ensure that events are transactional). Run worker to consume events from table for your own.

  gateways:
    payu: # your gateway key, store as gatewayKey on Payment
      adapter: 'PayU' # use for identify gateway strategy, GatewayEnum value
      
```

#### How to use it?

If you want to keep the process extremely simple, use the **PaymentProcessor** service:

```php
namespace Api/Service;

use PayWire\Core\Application\Command\InitializePayment;
use PayWire\Core\Domain\Payment\GatewayEnum;
use PayWire\Core\Application\PaymentProcessor;

class CreatePayment
{
    public function __construct(private PaymentProcessor $paymentProcessor)
    {
    }

    public function createPayment(): array
    {
        $payment = $this->paymentProcessor->prepareAndSubmit(new InitializePayment('23.45', 'PLN', GatewayEnum::PayU));
        
        #The service saves the entity, sends a request to the partner and returns the payment - all in one.
        return [$payment->paymentId, $payment->orderId, $payment->publicToken];
    }
}
```

Otherwise, you can take control of the whole process via messenger:

```php
use PayWire\Core\Application\Command\InitializePayment;
use PayWire\Core\Domain\Payment\GatewayEnum;
use Symfony\Component\Messenger\MessageBusInterface;

class CreatePayment
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }

    public function createPayment(): void
    {
        # you can put InitializePayment on asynchronous bus (default will be synchronous)
        $this->messageBus->dispatch(new InitializePayment('23.45', 'PLN', GatewayEnum::PayU));
    }
}
```

Submit payment (after initialization):

```php

use PayWire\Core\Domain\Payment\PaymentInitialized;
use PayWire\Core\Application\Command\SubmitPayment;
use Symfony\Component\Messenger\MessageBusInterface;

class PaymentInitializedListener
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }
    
    #[AsEventListener]
    public function submitPayment(PaymentInitialized $event) {
    
        # you can put SubmitPayment on asynchronous bus (default will be synchronous)
        $this->messageBus->dispatch(new SubmitPayment($event->paymentId));
    }
}
```

Listen for payment submitted to partner to get redirect data:

```php

use PayWire\Core\Domain\Payment\PaymentSubmitted;
use Symfony\Component\Messenger\MessageBusInterface;

class PaymentSubmittedListener
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }
    
    #[AsEventListener]
    public function onSubmitted(PaymentSubmitted $event): array {
    
        #grab payment data and pass it to the frontend
        
        return [$event->paymentId, $event->orderId, $event->paymentUrl];
    }
}
```

---

On webhook side:

```php
use PayWire\Core\Application\PaymentNotifier;
use PayWire\Core\Domain\Exception\DoubleNotifyRequest;
use PayWire\Core\Domain\Exception\InvalidRequestException;

class NotifyController
{
    public function notify(PaymentNotifier $notifier) {
    
        #verify request, handle state and dispatch events
        # Payment will be updated on success
       try{
        $notifier->grabRequest();
       }catch(DoubleNotifyRequest){
        # handle double notify request
        return new Response('OK');
       }catch(InvalidRequestException){
        # handle invalid request
       }
       
       return new Response('OK');
    }
}
```

Probably you want to listen for `PaymentCompleted`/`PaymentFailed`/`PaymentCancelled` events.

---

# Helpers

In some cases, you might want to run common operations:

* retrieve payment details from partner (and update local payment/refund record/'s)
* retrieve partners payment methods
* sync current payment data with partners data
* ...

We have some command/helpers to do that.

//TODO

---

# How it works:

//flow chart

---

# Refunds

//in the future