<?php 

namespace App\Http\Middleware
{
    class ArgonAccessRule
    {
        const CLOUDFLARE_IPS = [
            "103.21.244.0/22",
            "103.22.200.0/22",
            "103.31.4.0/22",
            "104.16.0.0/13",
            "104.24.0.0/14",
            "108.162.192.0/18",
            "131.0.72.0/22",
            "141.101.64.0/18",
            "162.158.0.0/15",
            "172.64.0.0/13",
            "173.245.48.0/20",
            "188.114.96.0/20",
            "190.93.240.0/20",
            "197.234.240.0/22",
            "198.41.128.0/17",
            "172.104.96.219/32"
        ];
        public function handle($request, \Closure $next)
        {
            if( auth()->check() ) 
            {
                
                
                $ip_address = $request->server('HTTP_CF_CONNECTING_IP')??($request->server('X_FORWARDED_FOR')??$request->server('REMOTE_ADDR'));
                $ipversion = strpos($ip_address, ":") === false ? 4 : 6;
                $user = auth()->user();
                // if ($user->accessrule)
                // {
                //     if ($user->accessrule->check_cloudflare == 1){
                //         $remote_addr = $request->server('REMOTE_ADDR');
                //         $bIsCloud = false;
                //         foreach (self::CLOUDFLARE_IPS as $clf)
                //         {
                //             if ($this->cidr_match($remote_addr, $clf))
                //             {
                //                 $bIsCloud = true;
                //                 break;
                //             }
                //         }
                //         if (!$bIsCloud)
                //         {
                //             $response = \Response::json(['error' => '허용되지 않은 접근입니다. CLOUDIP=' . $remote_addr], 401, []);
                //             $response->header('Content-Type', 'application/json');
                //             return $response;
                //         }
                //     }

                //     $allow_ips = explode(',', $user->accessrule->ip_address);
                //     if ($ipversion == 6 && $user->accessrule->allow_ipv6 == 1)
                //     {
                //         return $next($request);
                //     }
                //     if (in_array($ip_address,$allow_ips))
                //     {
                //         return $next($request);
                //     }
                //     else
                //     {
                //         $response = \Response::json(['error' => '허용되지 않은 접근입니다. IPADDRESS='. $ip_address], 401, []);
                //         $response->header('Content-Type', 'application/json');
                //         return $response;
                //     }
                // }
            }
            return $next($request);
        }
        public function cidr_match($ip, $range)
        {
            list ($subnet, $bits) = explode('/', $range);
            if ($bits === null) {
                $bits = 32;
            }
            $ip = ip2long($ip);
            $subnet = ip2long($subnet);
            $mask = -1 << (32 - $bits);
            $subnet &= $mask; # nb: in case the supplied subnet wasn't correctly aligned
            return ($ip & $mask) == $subnet;
        }
    }
}
