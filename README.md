# bellissimo-log
### install 

```angular2html
composer require bellissimopizza/bellissimo-log
```

### usage
Add flowing code on your AppServiceProvider on boot method

```php
 public function boot(): void
    {
        // Generate unique identifier for this request-response cycle
        $request = request();
        $requestId = Str::uuid()->toString();

        $request->attributes->add(['X-Request-ID' => $requestId]);
    }
```

### For route log 

```php
    use App\Utils\Log\RouteLogMiddleware;


    Route::post('orders', [OrderController::class, 'store'])->middleware(RouteLogMiddleware::class);
```

### For guzzle log

```php
  use App\Utils\Log\GuzzleLogMiddleware;


  Http::baseUrl('https://dummyjson.com')
    ->withHeaders([
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ])
    ->withMiddleware(new GuzzleLogMiddleware())
      ->get("/products/1")->json();
```