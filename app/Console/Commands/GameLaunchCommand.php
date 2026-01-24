<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GameLaunch;
use App\Models\User;
use App\Models\Game;
use Carbon\Carbon;
use Exception;
use Log;

class GameLaunchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game:launch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    const GAME_PROVIDERS = [
        // 'XMX',
        // 'TAISHAN',
        'HONOR',
        // 'NEXUS',        
        // 'WILDGAME',
        // 'HDLIVE',
        // 'KTEN',
        'HOLDEM',
        'TRUXBET',
    ];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        foreach (self::GAME_PROVIDERS as $provider)
        {
            $object = '\\App\\Http\\Controllers\\GameProviders\\' . strtoupper($provider)  . 'Controller';
            if (!class_exists($object))
            {
                continue;
            }

            $launchRequests = GameLaunch::where('finished', 0)->orderby('created_at', 'desc')->get();
            $launchRequests = GameLaunch::where(['finished'=> 0, 'provider' => strtolower($provider)])->orderby('created_at', 'desc')->get();
            $processed_users = [];
            foreach ($launchRequests as $request)
            {
                if (in_array($request->user_id, $processed_users)) //process 1 request per one user
                {
                    $this->info('skipping userid=' . $request->user_id . ', id=' . $request->id);
                    $request->delete();
                    continue;
                }
                $processed_users[] = $request->user_id;
                $url = call_user_func('\\App\\Http\\Controllers\\GameProviders\\' . strtoupper($provider) . 'Controller::makelink', $request->gamecode, $request->user_id);
                if ($url != null)
                {
                    $request->update([
                        'launchUrl' => $url,
                        'finished' => 1,
                    ]);
                }
                else
                {
                    $this->info('makelink failed');
                }
            }
        }

    }
}
