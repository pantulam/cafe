
/*
protected $routeMiddleware = [
    // ... existing middleware
//    'basicauth' => \App\Http\Middleware\BasicAuthMiddleware::class,
	'basicauth' => \App\Http\Middleware\ConfigAuthMiddleware::class,


];
*/

protected $routeMiddleware = [
    'auth' => \App\Http\Middleware\Authenticate::class,
    'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
    'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
    // ... other middleware
    
    // Add this line:
    'config.auth' => \App\Http\Middleware\ConfigAuthMiddleware::class,
];
