<?php

namespace App\Http\Middleware;

use Closure;
// use App\Models\Player;
use Illuminate\Http\Request;

class TokenAuthentication
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
        // $playerId = $request->route('id'); 
        
        // $player = Player::find($playerId);
        //     if(!$player){
        //     return response()->json(['message' => 'Player does not exist'], 404);
        //     }
        $token = $request->header('Authorization');
        
        if(!$token){
            return response()->json(['error' => 'Token is missing'], 401);
        }
        $token = str_replace('Bearer ', '', $token);
        $expectedToken = env('API_SECRET_KEY');

        
        if ($token !== $expectedToken) {
            return response()->json(['error' => 'Token is invalid'], 401);
        }
        return $next($request);
    }
}
