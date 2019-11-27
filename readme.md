## Installation

Via Composer

```bash
$ composer require shekel/shekel
```

#### For Laravel version < 5.5

If you don't use auto-discovery, add the ServiceProvider to the providers array in `config/app.php`

```php
Shekel\ShekelServiceProvider::class,
```

## Migrations

In order to use shekel you need to run migrations. It will add a **meta** table to your **users**
table. Additionally **subscriptions** and **plans** tables will be created.

//TODO make migrations publishable and add flags to disable migrations


## Configuration

###Billable model

To use shekel just add **Billable** trait to your model that you will be billing.

```php
use Shekel\Traits\Billable;

class User extends Authenticatable
{
    use Billable;
}
```

//TODO make user model configurable incase someone wants to use different billable model

### API keys

In order to use stripe you need to add your stripe public and secret keys to the .env file.
You can find your api keys in your stripe **[dashboard](https://dashboard.stripe.com/)** > **Developers** > **API keys**

```
STRIPE_PUBLIC=pk_test_xxxxxx
STRIPE_SECRET=sk_test_xxxxxx
```

### Currency configuration

//TODO CURRENCY (now default us dollar)


#Usage

###Plans

In order to make subscriptions first you need to create a new plan. To do so you can use ***CreatePlan*** action in your controller provided by the package.
This action has it's own validation so you don't need to validate the data yourself.

```php
use Shekel\Actions\CreatePlan

(new CreatePlan())([
    'title' => 'Basic monthly', //Your plan name
    'price' => 999, //Plan price in cents
    'billing_period' => 'monthly', //Supported periods - daily, weekly, monthly, yearly
    'trial_period_days' => 30, //trial days (max 730)
]);
```

//TODO make a static method for actions that invokes them. (Just so were pretty 'n all)

If all is successful you should go to your stripe dashboard > Billing > Products and see a new product created.

###Forms

After creating a plan you need to setup a payment form.

First you will need to list your plans so use can pick whitch plan he is subscribing to.

//TODO create a @include('Shekel::plans') view helper.

```html
<div class="mb-3 mt-3">
    <label>Plan</label>
    <select name="billing_plan_id" class="form-control">
        @foreach($plans as $plan)
            <option value="{{ $plan->id }}">{{ $plan->title }} - {{ number_format($plan->price / 100, 2) }}</option>
        @endforeach
    </select>
</div>
```

After that you need to add a credit card field in your form. Package provides a helper so you don't need to deal with stripes html/css/js.

```html
@include('Shekel::stripe.payment_form')
```

Last thing to do is adding `shekel-form` class to your html form.

```html
<form method="POST" action="{{ route('register') }}" class="shekel-form">
```

//TODO Think through use case scenarios and make more convenient helpers. Adding a class to a form is not practical and register form is not the only use case.

###Subscriptions

After submitting a form to your controller you need to subscribe the user to a plan. To do that all you need to do is call ***stripeSubscription*** method on the user model.

```php
    class RegisterController extends Controller {
    
        public function create(Request $request) {
            $user = User::create($request->all());
            
            $plan_id = $request->input('billing_plan_id');
            $paymentMethod = $request->input('payment_method');
           
            $user->stripeSubscription($plan_id, $paymentMethod)->create();
        }
        
    }
```

And thats it. Your user is now subscribed to your defined plan.




