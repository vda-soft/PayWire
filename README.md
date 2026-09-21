**<p style="text-align:center;">!!! LEARNING ONLY !!!</p>**

_This solution is intended for educational purposes. Its aim is to demonstrate the possibilities of handling payments in a ‘modern way’._

_It may become a fully functional package intended for production use (probably not :smile:)._

_It has never been tested in a production environment. It most likely contains bugs and shortcomings. Contact me if you wish to use it, please._


# PayWire
A modern system that integrates various payment service providers.


## Structure

* `src/Core` - core PHP functionality for handling payments
* `src/Bridge/*` - bridges for specific use case, for example Symfony bundle
* `src/Gateway/*` - gateway implementations for different payment providers: PayU, PayPal, etc.