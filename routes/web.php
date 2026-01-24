<?php
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', function () {
//     return redirect('/login');
// });

// Auth::routes(['register' => false,'reset'=>false,'verify'=>false]);

// Route::get('/home', 'App\Http\Controllers\HomeController@index')->name('home');
Route::middleware(['argonbackend'])->group(function () { //prefix('{slug}')->
    Route::namespace('Backend')->group(function () {
        Route::get('login', [
            'as' => 'auth.login',
            'uses' => 'AuthController@getLogin'
        ]);
        Route::post('login', [
            'as' => 'auth.login.post',
            'uses' => 'AuthController@postLogin'
        ]);
        Route::get('/reload-captcha','AuthController@reloadCaptcha');
        Route::post('/post','AuthController@postCaptcha');
    });
});

Route::middleware(['argonbackend', 'auth', 'argonaccessrule'])->group(function () { //prefix('{slug}')->
	Route::namespace('Backend')->group(function () {   
        Route::get('logout', [
            'as' => 'auth.logout',
            'uses' => 'AuthController@getLogout'
        ]);   
        
        //대시보드
		Route::get('/', [
            'as' => 'dashboard',
            'uses' => 'DashboardController@index',
        ]);
        Route::get('statistics', [
            'as' => 'statistics',
            'uses' => 'DashboardController@statistics'
        ]);	
        Route::get('partner_statistics', [
            'as' => 'statistics_partner',
            'uses' => 'DashboardController@statistics_partner'
        ]);

        //유저
        Route::get('/player/list', [
            'as' => 'player.list',
            'uses' => 'UsersController@player_list',
        ]);
        Route::get('/player/noconnect', [
            'as' => 'player.noconnect',
            'uses' => 'UsersController@player_noconnect',
        ]);
        Route::get('/player/connected', [
            'as' => 'player.connected',
            'uses' => 'UsersController@player_connected_list',
        ]);
        Route::get('/player/join', [
            'as' => 'player.join',
            'uses' => 'UsersController@player_join',
        ]);
        Route::get('/player/create', [
            'as' => 'player.create',
            'uses' => 'UsersController@player_create',
        ]);
        Route::post('/player/create', [
            'as' => 'player.store',
            'uses' => 'UsersController@player_store',
        ]);
        Route::get('/player/multicreate', [
            'as' => 'player.multicreate',
            'uses' => 'UsersController@player_multicreate',
        ]);
        Route::post('/player/multistore', [
            'as' => 'player.multistore',
            'uses' => 'UsersController@player_multistore',
        ]);
        Route::delete('/player/terminate', [
            'as' => 'player.terminate',
            'uses' => 'UsersController@player_terminate',
        ])->middleware('simultaneous:1');
        
        Route::get('/player/logout', [
            'as' => 'player.logout',
            'uses' => 'UsersController@player_logout',
        ])->middleware('simultaneous:1');

        Route::get('/player/exportcsv', [
            'as' => 'player.exportcsv',
            'uses' => 'UsersController@exportCSV',
        ]);
        Route::get('/player/refresh', [
            'as' => 'player.refresh',
            'uses' => 'UsersController@player_refresh',
        ]);
        Route::get('/player/delete', [
            'as' => 'player.delete',
            'uses' => 'UsersController@deleteUser',
        ]);
        Route::get('/player/active', [
            'as' => 'player.active',
            'uses' => 'UsersController@activeUser',
        ]);
        Route::get('/user/checkid', [
            'as' => 'user.checkid',
            'uses' => 'UsersController@checkId',
        ]);

        //파트너
        Route::get('/agent/create', [
            'as' => 'patner.create',
            'uses' => 'UsersController@agent_create',
        ]);
        Route::post('/patner/create', [
            'as' => 'patner.store',
            'uses' => 'UsersController@agent_store',
        ]);

        Route::get('/patner/joinlist', [
            'as' => 'patner.joinlist',
            'uses' => 'UsersController@agent_joinlist',
        ]);

        Route::get('/patner/move', [
            'as' => 'patner.move',
            'uses' => 'UsersController@agent_move',
        ]);

        Route::post('/patner/move', [
            'as' => 'patner.movestore',
            'uses' => 'UsersController@agent_update',
        ]);
        Route::get('/patner/list', [
            'as' => 'patner.list',
            'uses' => 'UsersController@agent_list',
        ]);
        
        Route::get('/patner/elist', [
            'as' => 'patner.eachlist',
            'uses' => 'UsersController@agent_eachlist',
        ]);

        Route::get('/patner/child', [
            'as' => 'patner.child',
            'uses' => 'UsersController@agent_child',
        ]);

        /**
         * Game Controller
         */
        Route::get('game/patner', [
            'as' => 'game.patner',
            'uses' => 'GameController@game_category',
        ]);

        Route::get('game/patner/status', [
            'as' => 'game.patner.status',
            'uses' => 'GameController@category_update',
        ]);

        Route::get('game/domain', [
            'as' => 'game.domain',
            'uses' => 'GameController@domain_category',
        ]);

        Route::get('game/domain/status', [
            'as' => 'game.domain.status',
            'uses' => 'GameController@domain_update',
        ]);

        Route::get('game/game', [
            'as' => 'game.game',
            'uses' => 'GameController@game_game',
        ]);

        Route::get('game/game/status', [
            'as' => 'game.game.status',
            'uses' => 'GameController@game_update',
        ]);

        Route::get('game/missbet', [
            'as' => 'game.missbet',
            'uses' => 'GameController@game_missrole',
        ]);
        Route::post('game/missbetupdate', [
            'as' => 'game.missbetupdate',
            'uses' => 'GameController@game_missroleupdate',
        ]);

        Route::get('game/missbet/status', [
            'as' => 'game.missbetstatus',
            'uses' => 'GameController@game_missrolestatus',
        ]);
        // 환수금 설정
        Route::get('game/slotbank', [
            'as' => 'game.slotbank',
            'uses' => 'GameController@game_bank',
        ]);
        Route::get('game/bankbalance', [
            'as' => 'game.bankbalance',
            'uses' => 'GameController@game_bankbalance',
        ]);
        Route::post('game/bankbalance', [
            'as' => 'game.bankbalance.post',
            'uses' => 'GameController@game_bankstore',
        ]);
        Route::get('game/bonusbank', [
            'as' => 'game.bonusbank',
            'uses' => 'GameController@game_bonusbank',
        ]);
        Route::post('game/banksetting', [
            'as' => 'game.banksetting',
            'uses' => 'GameController@gamebanks_setting',
        ]);
        Route::get('game/betlimit', [
            'as' => 'game.betlimit',
            'uses' => 'GameController@game_betlimit',
        ]);
        Route::post('game/betlimit', [
            'as' => 'game.betlimitupdate',
            'uses' => 'GameController@game_betlimitupdate',
        ]);
        Route::get('game/gactable', [
            'as' => 'game.gactable',
            'uses' => 'GameController@game_gactable',
        ]);

        Route::get('game/gactable/update', [
            'as' => 'game.gactable.update',
            'uses' => 'GameController@game_gactableupdate',
        ]);

        Route::get('game/switching', [
            'as' => 'game.switching',
            'uses' => 'GameController@game_switching',
        ]);

        Route::post('game/switching/update', [
            'as' => 'game.switching.update',
            'uses' => 'GameController@game_switchingupdate',
        ]);
        
        Route::get('game/gackind', [
            'as' => 'game.gackind',
            'uses' => 'GameController@game_gackind',
        ]);

        Route::post('game/gackind', [
            'as' => 'game.gackind.update',
            'uses' => 'GameController@game_gackindupdate',
        ]);
        // 콜설정
        Route::get('game/call', [
            'as' => 'game.call.list',
            'uses' => 'GameController@game_call',
            'middleware' => 'permission:happyhours.manage'
        ]);
        Route::get('game/call/create', [
            'as' => 'game.call.create',
            'uses' => 'GameController@call_create',
            'middleware' => 'permission:happyhours.add'
        ]);
        Route::post('game/call/create', [
            'as' => 'game.call.store',
            'uses' => 'GameController@call_store',
            'middleware' => 'permission:happyhours.add'
        ]);
        Route::delete('game/call/delete', [
            'as' => 'game.call.delete',
            'uses' => 'GameController@call_delete',
            'middleware' => 'permission:happyhours.delete'
        ]);

        //받치기 
        Route::get('/game/share', [
            'as' => 'game.share',
            'uses' => 'ShareBetController@index',
        ]);
        Route::get('/game/share/setting', [
            'as' => 'game.share.setting',
            'uses' => 'ShareBetController@setting',
        ]);
        Route::post('/game/share/setting', [
            'as' => 'game.share.setting.post',
            'uses' => 'ShareBetController@setting_store',
        ]);

        Route::get('/share/rolling/convert', [
            'as' => 'share.rolling.convert',
            'uses' => 'ShareBetController@convert_deal',
        ]);

        Route::get('/share/gamestat', [
            'as' => 'share.gamestat',
            'uses' => 'ShareBetController@gamestat',
        ]);
        Route::get('/share/report/daily', [
            'as' => 'share.report.daily',
            'uses' => 'ShareBetController@report_daily',
        ]);
        Route::get('/share/report/childdaily', [
            'as' => 'share.report.childdaily',
            'uses' => 'ShareBetController@report_childdaily',
        ]);
        Route::get('/share/report/game', [
            'as' => 'share.report.game',
            'uses' => 'ShareBetController@report_game',
        ]);

        /// 입출금관리
        Route::get('/charge/request', [
            'as' => 'charge.request',
            'uses' => 'DWController@addrequest',
        ]);
        Route::get('/exchang/erequest', [
            'as' => 'exchang.request',
            'uses' => 'DWController@outrequest',
        ]);
        Route::get('/comp/request', [
            'as' => 'comp.dealconvert',
            'uses' => 'DWController@dealconvert',
        ]);
        Route::get('/ec/history', [
            'as' => 'ec.history',
            'uses' => 'DWController@history',
        ]);
        // Route::get('/ec/process', [
        //     'as' => 'ec.process',
        //     'uses' => 'DWController@process',
        // ]);
        Route::delete('/ec/reject', [
            'as' => 'ec.reject',
            'uses' => 'DWController@rejectDW',
        ]);
        Route::get('/ec/process', [
            'as' => 'ec.process',
            'uses' => 'DWController@processDW',
        ]);
        Route::get('/ec/manage', [
            'as' => 'ec.manage',
            'uses' => 'DWController@addmanage',
        ]);
        Route::get('/exchange/manage', [
            'as' => 'exchange.outmanage',
            'uses' => 'DWController@outmanage',
        ]);
        // Common

        Route::get('/common/wait_in_out', [
            'as' => 'common.wait_in_out',
            'uses' => 'CommonController@waitInOut',
        ]);
        Route::get('/common/depositAccount', [
            'as' => 'common.depositAccount',
            'uses' => 'CommonController@depositAccount',
        ]);
        Route::post('/common/outbalance', [
            'as' => 'common.withdraw',
            'uses' => 'CommonController@withdraw',
        ]);
        Route::post('/common/addbalance', [
            'as' => 'common.deposit',
            'uses' => 'CommonController@deposit',
        ]);

        Route::get('/common/balance', [
            'as' => 'common.balance',
            'uses' => 'CommonController@balance',
        ]);
        Route::post('/common/balance', [
            'as' => 'common.balance.store',
            'uses' => 'CommonController@updateBalance',
        ])->middleware('simultaneous:1');

        Route::get('/common/profile', [
            'as' => 'common.profile',
            'uses' => 'CommonController@profile',
        ]);
        Route::post('/common/profile/update', [
            'as' => 'common.profile.detail',
            'uses' => 'CommonController@updateProfile',
        ]);
        Route::post('/common/profile/password', [
            'as' => 'common.profile.password',
            'uses' => 'CommonController@updatePassword',
        ]);
        Route::post('/common/profile/accessrule', [
            'as' => 'common.profile.accessrule',
            'uses' => 'CommonController@updateAccessrule',
        ]);
        Route::post('/common/profile/dwpass', [
            'as' => 'common.profile.dwpass',
            'uses' => 'CommonController@updateDWPass',
        ]);
        Route::get('/common/profile/resetdwpass', [
            'as' => 'common.profile.resetdwpass',
            'uses' => 'CommonController@resetDWPass',
        ]);
        Route::get('/common/percent', [
            'as' => 'common.percent',
            'uses' => 'CommonController@percent',
        ]);
        Route::post('/common/percent', [
            'as' => 'common.percent.update',
            'uses' => 'CommonController@updatePercent',
        ]);
        Route::get('/common/message', [
            'as' => 'common.message',
            'uses' => 'CommonController@message',
        ]);
        Route::post('/common/message', [
            'as' => 'common.message.send',
            'uses' => 'CommonController@sendMessage',
        ]);
        Route::post('common/convert_deal_balance', [
            'as' => 'common.convert_deal_balance',
            'uses' => 'CommonController@convertDealBalance',
        ]);
        Route::get('common/inoutlist', [
            'as' => 'common.inoutlist',
            'uses' => 'CommonController@inoutList_json',
        ]);

        //시스템
        Route::get('system/alramset', [
            'as' => 'system.alramset',
            'uses' => 'SettingsController@alramset',
        ]);
        Route::get('system/soundset', [
            'as' => 'system.soundset',
            'uses' => 'SettingsController@soundset',
        ]);
        Route::get('system/statistics', [
            'as' => 'system.statistics',
            'uses' => 'SettingsController@system_values',
        ]);
        Route::get('system/logreset', [
            'as' => 'system.logreset',
            'uses' => 'SettingsController@logreset',
        ]);

        //세팅
        Route::get('ipblocks', [
            'as' => 'ipblock.list',
            'uses' => 'SettingsController@ipblock_list',
        ]);

        Route::get('ipblocks/add', [
            'as' => 'ipblock.add',
            'uses' => 'SettingsController@ipblock_add',
        ]);

        Route::post('ipblocks/store', [
            'as' => 'ipblock.store',
            'uses' => 'SettingsController@ipblock_store',
        ]);

        Route::get('ipblocks/delete/', [
            'as' => 'ipblock.delete',
            'uses' => 'SettingsController@ipblock_delete',
        ]);

        Route::post('ipblocks/{ip}/update', [
            'as' => 'ipblock.update',
            'uses' => 'SettingsController@ipblock_update',
        ]);
        
        //도메인
        Route::get('websites', [
            'as' => 'websites.list',
            'uses' => 'SettingsController@web_index',
        ]);
        Route::get('websites/status', [
            'as' => 'websites.status',
            'uses' => 'SettingsController@web_status_update',
        ]);
        
        Route::get('websites/create', [
            'as' => 'websites.store',
            'uses' => 'SettingsController@web_store',
        ]);
        Route::get('websites/edit', [
            'as' => 'websites.edit',
            'uses' => 'SettingsController@web_edit',
        ]);
        Route::post('websites/update', [
            'as' => 'websites.update',
            'uses' => 'SettingsController@web_update',
        ]);
        Route::get('websites/delete/', [
            'as' => 'websites.delete',
            'uses' => 'SettingsController@web_delete',
        ]);

        //템플릿
        Route::get('msgtemp', [
            'as' => 'msgtemp.list',
            'uses' => 'MessageController@msgtemp_index',
        ]);

        Route::post('msgtemp/create', [
            'as' => 'msgtemp.store',
            'uses' => 'MessageController@msgtemp_store',
        ]);
        Route::get('msgtemp/edit', [
            'as' => 'msgtemp.edit',
            'uses' => 'MessageController@msgtemp_edit',
        ]);
        Route::post('msgtemp/update', [
            'as' => 'msgtemp.update',
            'uses' => 'MessageController@msgtemp_update',
        ]);

        Route::get('msgtemp/delete', [
            'as' => 'msgtemp.delete',
            'uses' => 'MessageController@msgtemp_delete',
        ]);

        //고객센터
        Route::get('/messages', [
            'as' => 'msg.list',
            'uses' => 'MessageController@message_index',
        ]);
        Route::get('messages/create', [
            'as' => 'msg.create',
            'uses' => 'MessageController@message_create',
        ]);
        Route::post('messages/monitor', [
            'as' => 'msg.monitor',
            'uses' => 'MessageController@message_updatemonitor',
        ]);
        Route::post('messages/create', [
            'as' => 'msg.store',
            'uses' => 'MessageController@message_store',
        ]);
        Route::get('messages/delete', [
            'as' => 'msg.delete',
            'uses' => 'MessageController@message_delete',
        ]);
        Route::delete('messages/deleteall', [
            'as' => 'msg.deleteall',
            'uses' => 'MessageController@message_deleteall',
        ]);
        Route::get('messages/readMsg', [
            'as' => 'msg.readmsg',
            'uses' => 'MessageController@readMessage',
        ]);

        //공지
        Route::get('/notices', [
            'as' => 'notices.list',
            'uses' => 'NoticeController@index',
        ]);
        Route::post('notices/create', [
            'as' => 'notices.store',
            'uses' => 'NoticeController@store',
        ]);
        Route::get('notices/status', [
            'as' => 'notices.status',
            'uses' => 'NoticeController@create',
        ]);
        Route::get('notices/edit', [
            'as' => 'notices.edit',
            'uses' => 'NoticeController@edit',
        ]);
        Route::post('notices/update', [
            'as' => 'notices.update',
            'uses' => 'NoticeController@update',
        ]);
        Route::get('notices/delete', [
            'as' => 'notices.delete',
            'uses' => 'NoticeController@delete',
        ]);

        Route::get('notices/statusupdate', [
            'as' => 'notices.statusupdate',
            'uses' => 'NoticeController@status_update',
        ]);


        //로그
        //파트너지급
        Route::get('/log/transaction', [                             
            'as' => 'partner.transaction',
            'uses' => 'LogController@patner_transaction',
        ]);
        //파트너롤링
        Route::get('/partner/rolling', [                            
            'as' => 'partner.dealstat',
            'uses' => 'LogController@patner_deal_stat',
        ]);
        //유저지급
        Route::get('/player/transaction', [                         
            'as' => 'player.transaction',
            'uses' => 'LogController@player_transaction',
        ]);
        //유저게임
        Route::get('/player/gamehistory', [                         
            'as' => 'player.gamehistory',
            'uses' => 'LogController@player_game_stat',
        ]);
        //받치기
        Route::get('/player/gamedetail', [                          
            'as' => 'player.gamedetail',
            'uses' => 'UsersController@player_game_detail',
        ]);
        //미결중 게임
        Route::get('/player/gamepending', [
            'as' => 'player.gamepending',
            'uses' => 'UsersController@player_game_pending',
        ]);
        
        Route::get('/player/processgame', [
            'as' => 'player.processgame',
            'uses' => 'UsersController@player_game_process',
        ]);

        //슬롯환수금
        Route::get('game/transaction', [                                
            'as' => 'game.transaction',
            'uses' => 'LogController@game_transaction',
        ]);

        //접속로그
        Route::get('activity', [
            'as' => 'activity.index',
            'uses' => 'LogController@activity_index',
        ]);

        // 보고서관리
        Route::get('/report/daily/old', [
            'as' => 'report.daily.old',
            'uses' => 'ReportController@report_daily',
        ]);
        Route::get('/report/childdaily', [
            'as' => 'report.childdaily',
            'uses' => 'ReportController@report_childdaily',
        ]);
        Route::post('/report/daily', [
            'as' => 'report.daily.post',
            'uses' => 'ReportController@update_dailydw',
        ]);
        Route::get('/report/daily', [
            'as' => 'report.daily',
            'uses' => 'ReportController@report_dailydw',
        ]);
        Route::get('/report/childdaily/{daily_type}', [
            'as' => 'report.childdaily.dw',
            'uses' => 'ReportController@report_childdaily',
        ]);
        Route::get('/report/childmonthly', [
            'as' => 'report.childmonthly',
            'uses' => 'ReportController@report_childmonthly',
        ]);
        Route::get('/report/monthly', [
            'as' => 'report.monthly',
            'uses' => 'ReportController@report_monthly',
        ]);
        Route::post('/report/game', [
            'as' => 'report.game.post',
            'uses' => 'ReportController@update_game',
        ]);    
        Route::get('/report/game', [
            'as' => 'report.game',
            'uses' => 'ReportController@report_game',
        ]);
        Route::get('/report/gamedetails', [
            'as' => 'report.game.details',
            'uses' => 'ReportController@report_game_details',
        ]);
        Route::get('/report/user', [
            'as' => 'report.user',
            'uses' => 'ReportController@report_user',
        ]);
        Route::get('/report/userdetails', [
            'as' => 'report.user.details',
            'uses' => 'ReportController@report_user_details',
        ]);
    });
});


