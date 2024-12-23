<?php

namespace App\Aspects;

use AhmadVoid\SimpleAOP\Aspect;
use Illuminate\Support\Facades\Log;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Logger implements Aspect
{

    // The constructor can accept parameters for the attribute
    public function __construct(public string $message = 'Logging...')
    {

    }

    public function executeBefore($request, $controller, $method)  
    {
        // TODO: Implement executeBefore() method.
        Log::info($this->message);
        Log::info('Request: ' . $request->fullUrl());
        Log::info('Controller: ' . get_class($controller));
        Log::info('Method: ' . $method);
    }

    public function executeAfter($request, $controller, $method, $response)
    {
        // TODO: Implement executeAfter() method.
        Log::info('Response: ' . $response->getContent());
    }

    public function executeException($request, $controller, $method, $exception)
    {
        // TODO: Implement executeException() method.
        Log::error($exception->getMessage());
    }
}
