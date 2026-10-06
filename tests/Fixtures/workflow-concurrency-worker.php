<?php

// Independent PHP process/connection; never wrap this worker in a test transaction.
$completed = false;
register_shutdown_function(function () use (&$completed): void {
    if (! $completed) {
        fwrite(STDERR, "Workflow concurrency worker did not complete.\n");
        exit(1);
    }
});
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (getenv('CI') !== 'true' || ! $app->environment('testing')
    || config('database.default') !== 'pgsql'
    || ! in_array(Illuminate\Support\Facades\DB::connection()->getConfig('host'), ['127.0.0.1', 'localhost'], true)) {
    throw new RuntimeException('Concurrency workers require isolated CI testing PostgreSQL.');
}
$input = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
$request = Illuminate\Http\Request::create('/workflow-concurrency', 'POST', $input['payload']);
$session = $app->make('session')->driver();
$session->start();
$request->setLaravelSession($session);
$app->instance('request', $request);
$app->make('url')->setRequest($request);
Illuminate\Support\Facades\Auth::setUser(App\Models\User::findOrFail($input['staff_id']));
$request->setUserResolver(fn () => Illuminate\Support\Facades\Auth::user());
$application = App\Models\LandTransferApplication::findOrFail($input['application_id']);
$route = new Illuminate\Routing\Route('POST', 'workflow-concurrency', fn () => null);
$route->name('staff.applications.concurrency')->bind($request);
$route->setParameter('application', $application);
$request->setRouteResolver(fn () => $route);
$pid = Illuminate\Support\Facades\DB::selectOne('SELECT pg_backend_pid() AS pid')->pid;
echo json_encode(['pid' => (int) $pid], JSON_THROW_ON_ERROR)."\n";
flush();
try {
    $controller = $app->make(App\Http\Controllers\Staff\ApplicationWorkflowController::class);
    $app->make(App\Http\Middleware\LockApplicationMutation::class)->handle($request, function ($request) use ($controller, $input) {
        return $controller->{$input['method']}($request, $request->route('application'));
    });
    $errors = $session->get('errors');
    $result = $errors && $errors->any()
        ? ['result' => 'validation', 'fields' => array_keys($errors->getBag('default')->getMessages())]
        : ($session->has('success') ? ['result' => 'success'] : ['result' => 'error', 'message' => $session->get('error')]);
} catch (Illuminate\Validation\ValidationException $exception) {
    $result = ['result' => 'validation', 'fields' => array_keys($exception->errors())];
}
echo json_encode($result, JSON_THROW_ON_ERROR)."\n";
$completed = true;
