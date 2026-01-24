<?php
namespace App\Http\Controllers\Backend
{
    class SettingsController extends \App\Http\Controllers\Controller
    {
        public function system_values(\Illuminate\Http\Request $request)
        {
            if( !auth()->user()->hasRole('admin') ) 
            {
                return redirect()->back()->withErrors([trans('app.no_permission')]);
            }
            $serverstat = server_stat();
            $agents = [];
            foreach (\App\Console\Commands\GameLaunchCommand::GAME_PROVIDERS as $provider)
            {
                $object = '\\App\\Http\\Controllers\\GameProviders\\' . strtoupper($provider)  . 'Controller';
                if (!class_exists($object))
                {
                    continue;
                }
                if (method_exists('\\App\\Http\\Controllers\\GameProviders\\' . strtoupper($provider) . 'Controller','getAgentBalance'))
                {
                    $money = call_user_func('\\App\\Http\\Controllers\\GameProviders\\' . strtoupper($provider) . 'Controller::getAgentBalance');
                    $agents[strtoupper($provider)] =  $money;
                }
            }
            $strinternallog = '';
            $filesize = 0;
            $laravel = storage_path('logs/laravel.log');
            if ( file_exists($laravel) )
            {
                $filesize = filesize($laravel);
                $operating_system = PHP_OS_FAMILY;
                if ($operating_system === 'Windows') {
                    $strinternallog = file_get_contents($laravel);
                }
                else
                {
                    $strinternallog = `tail -100 $laravel`;
                }

            }
            else
            {
                $strinternallog = 'Could not find log file';
            }
            
            return view('setting.system', compact('serverstat','agents','strinternallog', 'filesize'));
        }


        public function logreset(\Illuminate\Http\Request $request)
        {
            $laravel = storage_path('logs/laravel.log');
            if (file_exists($laravel)) {     // Make sure we don't create the file
                $fp = fopen($laravel, 'w');  // Sets the file size to zero bytes
                fclose($fp);
            }
            return redirect()->back()->withSuccess(['로그파일을 리셋하였습니다']);
        }


        public function ipblock_list(\Illuminate\Http\Request $request)
        {     
            $availablePartners = auth()->user()->hierarchyPartners();
            $availablePartners[] = auth()->user()->id;       
            if( !auth()->user()->isInoutPartner()) 
            {
                return redirect()->back()->withErrors([trans('app.no_permission')]);
            }
            $ipblocks = \App\Models\IPBlockList::whereIn('user_id', $availablePartners)->orderBy('created_at', 'DESC');
            if($request->search != ''){
                if( $request->status == '1' ) 
                {
                    $findusers = \App\Models\User::where('username', 'like', '%' . $request->search . '%')->pluck('id')->toArray();
                    $ipblocks = $ipblocks->whereIn('user_id', $findusers);
                }
                if( $request->status == '2' ) 
                {
                    $ipblocks = $ipblocks->where('ip_address', 'like', '%' . $request->search . '%');
                }
            }
            
            $ipblocks = $ipblocks->paginate(50);
            return view('setting.ipblocklist', compact('ipblocks'));
        }

        public function ipblock_add(\Illuminate\Http\Request $request)
        {            
            $edit = $request->edit;
            $ipblock = null;
            if(isset($request->id)){
                $ipblock = \App\Models\IPBlockList::where('id', $request->id)->first();
            }

            return view('modal.ipblock', compact('edit', 'ipblock'));
        }

        public function ipblock_store(\Illuminate\Http\Request $request)
        {
            if( $request->ip_address == '') 
            {
                return redirect()->back()->withErrors(trans('auth.ipaddress'));
            }
            $ip = \App\Models\IPBlockList::create([
                'user_id' => auth()->user()->id,
                'ip_address' => $request->ip_address
            ]);
            return redirect()->back()->withSuccess(['도메인이 추가되었습니다.']);
        }

        public function ipblock_delete(\Illuminate\Http\Request $request)
        {
            $ip =$request->ids;            
            $ipDeleteResult = \App\Models\IPBlockList::whereIn('id', $ip)->delete();
            return response()->json(['error'=>false, 'msg'=> 'IP가 삭제되었습니다.']);
            // return redirect()->to(argon_route('ipblock.list'))->withSuccess(['IP가 삭제되었습니다.']);            
        }
        public function ipblock_update(\Illuminate\Http\Request $request, $ip)
        {
            if( $request->ip_address == '') 
            {
                return redirect()->back()->withErrors(trans('auth.ipaddress'));
            }
            \App\Models\IPBlockList::where('id', $ip)->update(['ip_address' => $request->ip_address]);
            return redirect()->back()->withSuccess(['도메인이 변경되었습니다.']);
        }


        public function web_index(\Illuminate\Http\Request $request)
        {
            $availablePartners = auth()->user()->hierarchyPartners();
            $availablePartners[] = auth()->user()->id;
            if(auth()->user()->hasRole(['admin'])){
                $websites = \App\Models\WebSite::get();
            }else{
                $websites = \App\Models\WebSite::whereIn('adminid', $availablePartners)->get();
            }
            return view('websites.list', compact('websites'));
        }

        public function web_status_update(\Illuminate\Http\Request $request)
        {
            $websiteid =$request->website;
            $status = ($request->status=='1'?1:0);

            $availablePartners = auth()->user()->hierarchyPartners();
            $availablePartners[] = auth()->user()->id;

            $websites = \App\Models\WebSite::where('id', $websiteid)->first();
            if (!$websites || !in_array($websites->adminid, $availablePartners))
            {
                return response()->json(['error'=>true, 'msg'=> '허용되지 않은 접근입니다.']);
            }

            $websites->update(['status' => $status]);
            //return redirect()->to(argon_route('website.list'))->withSuccess(['상태를 업데이트했습니다']);
            return response()->json(['error'=>false, 'msg'=> '상태를 업데이트했습니다.']);
        }   
        public function web_add(\Illuminate\Http\Request $request)
        {
            $edit = $request->edit; 
            foreach( glob(public_path().'/back/layout/*', GLOB_ONLYDIR) as $fileinfo ) 
            {
                $dirname = basename($fileinfo);
                $frontends[$dirname] = $dirname;
            }
            //$frontends = [];
            // foreach( glob(resource_path() . '/views/backend/argon/layouts/*', GLOB_ONLYDIR) as $fileinfo ) 
            // {
            //     $dirname = basename($fileinfo);
            //     $backends[$dirname] = $dirname;
            // }
            if($edit == 0){
                $edit = 'false';
            }else{
                $edit = 'true';
            }
            $website=[];
            return view('modal.websites',compact('edit','website'));
        }
        
        public function web_create(\Illuminate\Http\Request $request)
        {
            $edit = $request->edit; 
            foreach( glob(public_path().'/back/layout/*', GLOB_ONLYDIR) as $fileinfo ) 
            {
                $dirname = basename($fileinfo);
                $frontends[$dirname] = $dirname;
            }
            //$frontends = [];
            // foreach( glob(resource_path() . '/views/backend/argon/layouts/*', GLOB_ONLYDIR) as $fileinfo ) 
            // {
            //     $dirname = basename($fileinfo);
            //     $backends[$dirname] = $dirname;
            // }
            $message = $request->message;
            return view('modal.websites',compact('edit','message'));
        }

        public function web_store(\Illuminate\Http\Request $request)
        {
            $data = $request->only([
                'domain',
                'title',
                'frontend',
                'backend',
            ]);
            if($data['domain'] == null || $data['title'] == null || $data['backend'] == null){
                return redirect()->back()->withErrors('message','입력이 올바르지 않습니다.');        
            }
            if ($request->admin != '')
            {
                $admin = \App\Models\User::where('username', $request->admin)->first();
                if (!$admin)
                {
                    return redirect()->back()->withErrors('message','총본사를 찾을수 없습니다.');
                }
                $data['adminid'] = $admin->id;
            }
            else
            {
                return redirect()->back()->withErrors('총본사를 입력하세요.');
            }
            /**
             * $site = \App\Models\WebSite::where('domain', $data['domain'])->first();
            if($site != null)
            {
                return redirect()->back()->withErrors($data['domain'] . ' 도메인이 이미 설정되어있습니다.');
            }
             */
            $site = \App\Models\WebSite::create($data);
            // create categories
            $categories = \App\Models\Category::where([
                'shop_id' => 0, 
                'parent' => 0,
                'site_id' => 0,
            ])->get();
            if( count($categories) ) 
            {
                foreach( $categories as $category ) 
                {
                    $newCategory = $category->replicate();
                    $newCategory->site_id = $site->id;
                    $newCategory->save();
                    $categories_2 = \App\Models\Category::where([
                        'shop_id' => 0, 
                        'parent' => $category->id
                    ])->get();
                    if( count($categories_2) ) 
                    {
                        foreach( $categories_2 as $category_2 ) 
                        {
                            $newCategory_2 = $category_2->replicate();
                            $newCategory_2->site_id = $site->id;
                            $newCategory_2->parent = $newCategory->id;
                            $newCategory_2->save();
                        }
                    }
                }
            }
            return redirect()->back()->withSuccess(['도메인이 추가되었습니다.']);
        }
        public function web_edit(\Illuminate\Http\Request $request)
        {
            $websiteId=$request->id;
            $edit = $request->edit;
            foreach( glob(public_path().'/back/layout/*', GLOB_ONLYDIR) as $fileinfo ) 
            {
                $dirname = basename($fileinfo);
                $frontends[$dirname] = $dirname;
            }

            foreach( glob(resource_path() . '/views/backend/Default/layouts/*', GLOB_ONLYDIR) as $fileinfo ) 
            {
                $dirname = basename($fileinfo);
                $backends[$dirname] = $dirname;
            }
            $backends = [];
            $website = [];
            if($websiteId != ''){
                $website = \App\Models\WebSite::where([
                    'id' => $websiteId])->firstOrFail();
            }
            
            return view('modal.websites', compact('website','frontends','backends','edit'));
        }
        public function web_update(\Illuminate\Http\Request $request)
        {
            $data = $request->only([
                'domain',
                'title',
                'frontend',
                'backend',
            ]);
            if ($request->admin != '')
            {
                $admin = \App\Models\User::where('username', $request->admin)->first();
                if (!$admin)
                {
                    return redirect()->back()->withErrors('총본사를 찾을수 없습니다.');
                }
                $data['adminid'] = $admin->id;
            }
            else
            {
                return redirect()->back()->withErrors('총본사를 입력하세요.');
            }
            $website = $request->id;
            \App\Models\WebSite::where('id', $website)->update($data);
            return redirect()->back()->withSuccess(['도메인이 수정되었습니다.']);
        }
        public function web_delete(\Illuminate\Http\Request $request)
        {
            $website = $request->ids;
            \App\Models\WebSite::whereIn('id', $website)->delete();
            //create categories
                \App\Models\Category::where('shop_id', 0)->whereIn('site_id',$website)->delete();
            
            return response()->json(['error'=>false, 'msg'=> '도메인이 삭제되었습니다.']);
        }
        public function alramset(\Illuminate\Http\Request $request)
        {
            $odd = \App\Models\Settings::where('key', 'MaxOdd')->first();
            $win = \App\Models\Settings::where('key', 'MaxWin')->first();
            $data = [
                'MaxOdd' => $odd?$odd->value:300,
                'MaxWin' => $win?$win->value:5000000
            ];
            return view('setting.alramset', compact('data'));
        }
        public function soundset(\Illuminate\Http\Request $request)
        {
            return view('setting.soundset');
        }
    }
}