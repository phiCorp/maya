<?php

namespace Maya\Http\Middleware\Contract;

use Closure;
use Maya\Http\Request;

interface MiddlewareInterface
{
    public function handle(Request $request, Closure $next);
}
