<?php 
namespace App\Http\Controllers\GameProviders
{
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    class ApiController extends \App\Http\Controllers\Controller
    {    
        public function userSignal($provider, \Illuminate\Http\Request $request)
        {
            $user = \App\Models\User::lockForUpdate()->where('id',auth()->id())->first();
            if (!$user)
            {
                return response()->json([
                    'error' => '1',
                    'description' => 'unlogged']);
            }
            $providers = explode('_', $user->playing_game);
            if (count($providers) >= 2)
            {
                if ($providers[0] != strtolower($provider))
                {
                    return response()->json([
                        'error' => '1',
                        'description' => 'no playing this game']);
                }
            }
            else if ($user->playing_game != strtolower($provider))
            {
                return response()->json([
                    'error' => '1',
                    'description' => 'Idle TimeOut']);
            }

            if ($request->name == 'exitGame')
            {
                Log::channel('monitor_game')->info(strtoupper($provider) . ' : ' . $user->id . ' is exiting game');
                if (count($providers) >= 2)
                {
                    $user->update([
                        'playing_game' => $providers[0] .  '_' . $providers[1] . '_exit',
                        'played_at' => time()
                    ]);
                }
                else
                {
                    $user->update([
                        'playing_game' => strtolower($provider) . 'exit',
                        'played_at' => time()
                    ]);
                }
            }
            else
            {
                if (count($providers) >= 2)
                {
                    $balance = call_user_func('\\App\\Http\\Controllers\\GameProviders\\' . strtoupper($provider) . 'Controller::getUserBalance', $providers[1], $user);
                }
                else
                {
                    $balance = call_user_func('\\App\\Http\\Controllers\\GameProviders\\' . strtoupper($provider) . 'Controller::getUserBalance', $user);
                }
                if ($balance >= 0)
                {
                    $user->update([
                        'balance' => $balance,
                        'played_at' => time()
                    ]);
                }
            }
            return response()->json([
                'error' => '0',
                'description' => 'OK']);
        }
    }

}
