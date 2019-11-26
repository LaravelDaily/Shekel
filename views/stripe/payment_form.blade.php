<?php $intent = \Stripe\SetupIntent::create(); ?>

<div class="shekel-stripe-card-form mt-3 mb-3">
    <input type="hidden" name="payment_method" class="payment-method">
    <input class="StripeElement mb-3" name="card_holder_name" placeholder="Card holder name" required>
    <div id="card-element"></div>
    <div id="card-errors" role="alert"></div>
</div>

@section('shekel_scripts')
    @parent
    <script src="https://js.stripe.com/v3/"></script>
    <script>
      let stripe = Stripe("{{ config('shekel.stripe.public_key') }}")
      let elements = stripe.elements()
      let style = {
        base: {
          color: '#32325d',
          fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
          fontSmoothing: 'antialiased',
          fontSize: '16px',
          '::placeholder': {
            color: '#aab7c4'
          }
        },
        invalid: {
          color: '#fa755a',
          iconColor: '#fa755a'
        }
      }

      let card = elements.create('card', {style: style})
      card.mount('#card-element')

      let paymentMethod = null

      $('.shekel-form').on('submit', function (e) {
        if (paymentMethod) {
          return true
        }
        stripe.confirmCardSetup(
          "{{ $intent->client_secret }}",
          {
            payment_method: {
              card: card,
              billing_details: {name: $('.card_holder_name').val()}
            }
          }
        ).then(function (result) {
          if (result.error) {
            console.log(result)
            alert('error')
          } else {
            paymentMethod = result.setupIntent.payment_method
            $('.payment-method').val(paymentMethod)
            $('.shekel-form').submit()
          }
        })
        return false
      })
    </script>
@endsection

@section('shekel_styles')
    @parent
    <style>
        .StripeElement {
            box-sizing: border-box;

            height: 40px;

            padding: 10px 12px;

            border: 1px solid transparent;
            border-radius: 4px;
            background-color: white;

            box-shadow: 0 1px 3px 0 #e6ebf1;
            -webkit-transition: box-shadow 150ms ease;
            transition: box-shadow 150ms ease;
        }

        .StripeElement--focus {
            box-shadow: 0 1px 3px 0 #cfd7df;
        }

        .StripeElement--invalid {
            border-color: #fa755a;
        }

        .StripeElement--webkit-autofill {
            background-color: #fefde5 !important;
        }
    </style>
@endsection