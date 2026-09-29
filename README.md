**<p style="text-align:center;">!!! LEARNING ONLY !!!</p>**

_This solution is intended for educational purposes. Its aim is to demonstrate the possibilities of handling payments in a ‘modern way’._

_It may become, same day, a fully functional package intended for production use (probably not :smile:)._

_Warning!!!_
_It has never been used in a production environment. It is not tested with real-world scenarios. It most likely contains bugs and shortcomings.
Contact me if you like it and wish to use it, please._

# PayWire

A modern system that integrates various payment service providers.

Version numbering strategy:

* no version number – experiments are being carried out; many changes causing incompatibility; a clash of different ideas. No tests, just happy
  programming. Maybe will be squashed at the end of it to keep clean git history.
* 0.0.x – complete concepts one by one
* 0.x – we now have a fairly stable concepts and several working solutions
* 1.x – stable release, ready for real scenarios

---

## Structure

* `src/Core` - core PHP functionality for handling payments
* `src/Bridge/*` - bridges for specific use case, for example Symfony bundle
* `src/Gateway/*` - gateway implementations for different payment providers: PayU, PayPal, etc.

---

### Symfony bridge

#### Configuration

`composer require vda-soft/paywire-bridge-symfony vda-soft/paywire-payu`

Settings:

```yaml
pay_wire:
  logger: null, # (default). Put your logger instance here. Must implement psr LoggerInterface.
  outbox_event_bus:
    enabled: true # (default: false)
    table_name: 'outbox_events' # (default)
    # Used for store events on sql table (to ensure that events are transactional). Run worker to consume events from table for your own.

  gateways:
    payu: # your gateway key, store as gatewayKey on Payment
      adapter: 'payu', # use for identify gateway strategy
      notify_url: null, # webhook url
      continue_url: null, # redirect user for url
```

#### How to use it?

If you want to keep the process extremely simple, use the service:

```php
namespace Api/Service;

use PayWire\Core\Application\Command\InitializePayment;
use PayWire\Core\Payment\GatewayEnum;
use Symfony\Component\Messenger\MessageBusInterface;

class CreatePayment
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }

    public function createPayment(): array
    {
        $submittedResponse = $this->paymentService->initializeAndCapture(new InitializePayment('23.45', 'PLN', GatewayEnum::PayU));
        
        #The service saves the entity, sends a request to the partner and returns the data - all in one.
        return [$submittedResponse->paymentId, $submittedResponse->orderId, $submittedResponse->paymentUrl];
    }
}
```

Otherwise, you can take control of the whole process:

```php
use PayWire\Core\Application\Command\InitializePayment;
use PayWire\Core\Payment\GatewayEnum;
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

Capture payment:

```php

use PayWire\Core\Payment\PaymentInitialized;
use Symfony\Component\Messenger\MessageBusInterface;

class PaymentInitializedListener
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }
    
    #[AsEventListener]
    public function capturePayment(PaymentInitialized $event) {
    
        # you can put CapturePayment on asynchronous bus (default will be synchronous)
        $this->messageBus->dispatch(new CapturePayment($event->paymentId));
    }
}
```

Listen for payment created on partner site to complete payment:

```php

use Symfony\Component\Messenger\MessageBusInterface;

class PaymentSubmittedListener
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }
    
    #[AsEventListener]
    public function pay(PaymentSubmitted $event): array {
    
        #grab payment data and pass it to the frontend
        
        return [$event->paymentId, $event->orderId, $event->paymentUrl];
    }
}
```

---

On webhook side:

```php
class NotifyController
{
    public function notify() {
    
        #verify request, handle state and dispatch events
        # Payment will be updated on success
       try{
        (PaymentNotifier)->grabRequest();
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

Probably you want to listen for `PaymentCompleted<id, status, ...>/PaymentFailed/PaymentCancelled, ...` events.

---

# Helpers

In some cases, you might want to run common operations:

* retrieve payment details from partner (and update local payment/refund record/'s)
* retrieve partners payment methods
* ...

We have some command/helpers to do that.

//TODO

---

# How it works:

//flow chart

---

# Refunds

//in the future