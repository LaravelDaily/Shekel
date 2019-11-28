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

In order to use shekel you need to run migrations. It will add a **meta** field to your **users**
table. Additionally **subscriptions** and **plans** tables will be created.

//TODO make migrations publishable and add flags to disable migrations


## Configuration

### Billable model

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


# Usage

### Plans

In order to make subscriptions first you need to create a new plan.

```php
use Shekel\Models\Plan;

$plan = Plan::create([
    'title' => 'Basic monthly', //Your plan name
    'price' => 999, //Plan price in cents
    'billing_period' => 'monthly', //Supported periods - daily, weekly, monthly, yearly
    'trial_period_days' => 30, //trial days (max 730)
]);
```

All data is validated under the hood, and a plan in stripe database is created assuming stripe is enabled.

To update a plan you can use laravels default update method and the plan will update in stripe also.
NOTICE: stripe does not allow updating any fields except ***trial_period_days*** 
so all updates are validated and will throw ValidationException if you try to update any restricted fields.

```php
$plan->update(['title' => 'my-title']) //WILL THROW AN EXCEPTION
    
$plan->update(['trial_period_days' => 10]); //WILL UPDATE NORMALY
```

To delete a plan just call the delete method.
Stripe plan will also be deleted (but the product that the plan is attached to will remain intact).
```php
$plan->delete();
```


If all is successful you should go to your stripe dashboard > Billing > Products and see a new product created.

### Forms

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

### Subscriptions

After submitting a form to your controller you need to subscribe the user to a plan. To do that all you need to do is call ***stripeSubscription*** method on the user model.
First argument is plan id that you are subscribing to and the second parameter is a payment method string privided by stripe from the credit card form.

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

### Canceling a subscripton

To cancel a subscription just run:

```php
$user->subscription()->cancel();
```

To cancel multiple subscriptions use the provided ***subscritions*** relationship:

```php 
use Shekel\Models\Subscription;

$user->subscriptions()->each(function(Subscription $subscription) {
    $subscription->cancel();
});
```

By default canceling a subscription will hold it's grace period until the subscription expires. 
If you want to cancel the subscription now use ***cancelNow*** method.

```php
$user->subscription()->cancelNow();
```

### Changing the plan that user subscribes to

If you want to change users subscription plan just call ***changePlan*** method on any subscription.
Single argument needed is the plan id that you want to swap to.
```php
$user->subscription()->changePlan(2);
```


### Handling subscription quantities

If you have a quantity based subscription (eg. subscription price is based on how many users a team has) then you can use ***changeQuantity*** method to increase or decrease it.
Single argument required is the quantity amount number.

```php
$user->subscription()->changeQuantity(2);
```

By default quantity is always set to 1 so if you want to adjust the quantity while creating the subscription use ***quantity*** method.

```php 
$user->stripeSubscription($plan_id, $paymentMethod)->quantity(2)->create();
```

