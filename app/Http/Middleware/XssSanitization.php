<?php

namespace App\Http\Middleware;
use Illuminate\Http\Request;

use Closure;

class XssSanitization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $input = $request->all();
       
        // array_walk_recursive($input, function(&$input) {
        //     $input =  strip_tags($input);
        // });
        // $request->merge($input);

     
        return $next($request);
    }
}
