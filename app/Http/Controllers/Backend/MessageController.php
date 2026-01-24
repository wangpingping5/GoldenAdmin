<?php
namespace App\Http\Controllers\Backend {
    class MessageController extends \App\Http\Controllers\Controller
    {
        public function msgtemp_index(\Illuminate\Http\Request $request)
        {
            if (auth()->user()->hasRole('admin')) {
                $msgtemps = \App\Models\MsgTemp::orderBy('order', 'desc');
            } else {
                $msgtemps = \App\Models\MsgTemp::where('writer_id', auth()->user()->id)->orderBy('order', 'desc');
            }
            $msgtemps = $msgtemps->paginate(20);
            return view('notices.msgtemp.list', compact('msgtemps'));
        }

        public function msgtemp_create(\Illuminate\Http\Request $request)
        {
            $edit = $request->edit;
            $message = $request->message;
            if ($edit == 0) {
                $edit = 'false';

            } else {
                $edit = 'true';
            }
            return view('modal.msgtemp', compact('edit', 'message'));
        }
        public function msgtemp_store(\Illuminate\Http\Request $request)
        {
            $data = $request->only([
                'title',
                'content',
                'order',
            ]);
            $data['writer_id'] = auth()->user()->id;
            \App\Models\MsgTemp::create($data);
            return redirect()->back()->withSuccess(['템플릿이 추가되었습니다.']);
        }
        public function msgtemp_edit(\Illuminate\Http\Request $request)
        {
            $id = $request->id;
            $edit = $request->edit;
            $msgtemp = [];
            if ($id != '') {
                $msgtemp = \App\Models\MsgTemp::where([
                    'id' => $id
                ])->firstOrFail();
            }

            return view('modal.msgtemp', compact('msgtemp', 'edit'));
        }
        public function msgtemp_update(\Illuminate\Http\Request $request)
        {
            $data = $request->only([
                'title',
                'content',
                'order',
            ]);
            $data['writer_id'] = auth()->user()->id;
            $msg = '';
            if (isset($request->edit) && $request->edit != false) {
                $msgtemp = $request->id;
                \App\Models\MsgTemp::where('id', $msgtemp)->update($data);
                $msg = '템플릿이 업데이트되었습니다.';
            } else {
                \App\Models\MsgTemp::create($data);
                $msg = '템플릿이 추가되었습니다.';
            }

            return redirect()->back()->withSuccess([$msg]);
        }

        public function msgtemp_delete(\Illuminate\Http\Request $request)
        {
            $msgtemp = $request->ids;
            \App\Models\MsgTemp::whereIn('id', $msgtemp)->delete();
            return response()->json(['error' => false, 'msg' => '템플릿이 삭제되었습니다.']);
        }


        public function message_index(\Illuminate\Http\Request $request)
        {
            $type = $request->type;
            if (auth()->user()->hasRole('admin')) {
                $msgs = \App\Models\Message::orderBy('created_at', 'desc')->where('ref_id', 0);
            } else //if (auth()->user()->hasRole('comaster'))
            {
                $msgs = \App\Models\Message::where(function ($query) {
                    $query->where('writer_id', '=', auth()->user()->id)->orWhere('user_id', '=', auth()->user()->id);
                })->where(function ($query) {
                    $query->where('ref_id', '=', 0);
                })->orderBy('created_at', 'desc');
            }
            // else
            // {
            //     $msgs = \App\Models\Message::where('user_id', auth()->user()->id)->get();
            // }
            $msgs = $msgs->where('type', $type);
            $odd = \App\Models\Settings::where('key', 'MaxOdd')->first();
            $win = \App\Models\Settings::where('key', 'MaxWin')->first();
            $data = [
                'MaxOdd' => $odd ? $odd->value : 300,
                'MaxWin' => $win ? $win->value : 5000000
            ];

            $searchName = 'id';
            $searchValue = '';
            if ($request->search != '') {
                $searchValue = $request->search;
            }
            if ($request->searchlist != '') {
                if ($request->searchlist == 1) {
                    $searchName = 'writer_id';
                } else if ($request->searchlist == 2) {
                    $searchName = 'user_id';
                } else if ($request->searchlist == 3) {
                    $searchName = 'title';
                } else if ($request->searchlist == 4) {
                    $searchName = 'type';
                }
            }
            $userid = [];
            if ($searchName != '' && $searchValue != '') {
                if ($searchName == 'title' || $searchName == 'id') {
                    $msgs = $msgs->where($searchName, 'like', '%' . $searchValue . '%');
                } else {
                    $userid = \App\Models\User::where('username', 'like', '%' . $searchValue . '%')->pluck('id');
                    if (!$userid) {
                        return redirect()->back()->withErrors(['수신자(발신자)를 찾을수 없습니다']);
                    }
                    $msgs = $msgs->whereIn($searchName, $userid);

                }
            }
            $msgs = $msgs->paginate(20);
            $msgParentTitle = "";
            $msgElementTitle = "";
            if ($type == 0) {
                //return view('notices.note.list', compact('msgs','data'));
                $msgParentTitle = "notelist";
                $msgElementTitle = "Message-list";
            } else {
                //return view('notices.messages.list', compact('msgs','data'));
                $msgParentTitle = "messagelist";
                $msgElementTitle = "cs";
            }
            return view('notices.messages.list', compact('msgs', 'data', 'type', 'msgParentTitle', 'msgElementTitle'));
        }
        public function message_create(\Illuminate\Http\Request $request)
        {
            $ref = $request->ref;
            $refmsg = null;
            if ($ref != '') {
                $refmsg = \App\Models\Message::where('id', $ref)->first();
                $availableUsers = auth()->user()->availableUsers();
                if ($refmsg == null || !in_array($refmsg->writer_id, $availableUsers)) {
                    return redirect()->back()->withErrors(['수신자를 찾을수 없습니다']);
                }
            }
            $msgtemps = \App\Models\MsgTemp::where('writer_id', auth()->user()->id)->orderBy('order', 'desc')->get();
            $type = $request->type;
            if ($type == 0) {
                //return view('notices.note.add', compact('msgtemps','refmsg','type'));
                $msgParentTitle = "noteconfirm";
                $msgElementTitle = "Message-list";
            } else {
                //return view('notices.messages.add', compact('msgtemps','refmsg','type'));
                $msgParentTitle = "messagesend";
                $msgElementTitle = "cs";
            }
            return view('notices.messages.add', compact('msgtemps', 'refmsg', 'type', 'msgParentTitle', 'msgElementTitle'));
        }
        public function message_store(\Illuminate\Http\Request $request)
        {
            $data = $request->only([
                'title',
                'content',
                'sendtype',
                'ref_id',
                'type'
            ]);
            if (isset($request->user_id) && $request->user_id != ''){
                $data['user_id'] = $request->user_id;
            } else if ($request->user != '' && $request->sendtype == 'shop') {
                $availableUsers = auth()->user()->availableUsers();
                $user = \App\Models\User::where('username', $request->user)->first();

                if (!$user || !in_array($user->id, $availableUsers)) {
                    return redirect()->back()->withErrors('발송할 회원을 찾을수 없습니다.');
                }
                $data['user_id'] = $user->id;
            } else if (auth()->user()->isInoutPartner()) {
                if (auth()->user()->role_id == 7) {
                    $availableUsers = auth()->user()->availableUsers();
                    $data['writer_id'] = auth()->user()->id;
                    foreach ($availableUsers as $userid) {
                        if ($userid != auth()->user()->id) {
                            $data['user_id'] = $userid;
                            \App\Models\Message::create($data);
                        }
                    }
                    return redirect()->back()->withSuccess([trans('SentMessage')]);
                } else {
                    if ($request->sendtype == 'all') {
                        $data['user_id'] = \App\Models\Message::GROUP_MSG_ID;
                    } else if ($request->sendtype == 'live') {
                        $data['user_id'] = \App\Models\Message::LIVE_MSG_ID;
                    }

                }
            } else // 일반 파트너들이 본사에게 발송
            {
                $master = auth()->user();
                while ($master != null && !$master->isInoutPartner()) {
                    $master = $master->referral;
                }

                $data['user_id'] = $master->id;
            }
            $data['writer_id'] = auth()->user()->id;
            \App\Models\Message::create($data);

            return redirect()->back()->withSuccess([trans('SentMessage')]);
        }
        public function message_delete(\Illuminate\Http\Request $request)
        {
            $message = $request->ids;
            // $msg = \App\Models\Message::where('id', $message)->first();
            // if ($msg && $msg->user_id != 0){
            //     $msg->delete();
            // }

            $allIdsToDelete = self::getAllRelatedMessageIds($message);
            \App\Models\Message::whereIn('id', $allIdsToDelete)->delete();

            return response()->json(['error' => false, 'msg' => '쪽지가 삭제되었습니다.']);
        }

        public function deleteall(\Illuminate\Http\Request $request)
        {
            $type = $request->type ?? 0;

            // 삭제 대상 메시지 기본 쿼리
            if (auth()->user()->hasRole('admin')) {
                $baseMessages = \App\Models\Message::where('type', $type)->pluck('id')->toArray();
            } else {
                $availableUsers = auth()->user()->availableUsers(); // 유저가 쓸 수 있는 대상들
                $baseMessages = \App\Models\Message::where('type', $type)
                    ->whereIn('writer_id', $availableUsers)
                    ->pluck('id')
                    ->toArray();
            }

            // 메시지 ID와 하위 댓글을 모두 포함한 ID 목록 얻기
            $allMessageIds = $this->getAllRelatedMessageIds($baseMessages);

            // 실제 삭제
            \App\Models\Message::whereIn('id', $allMessageIds)->delete();

            return redirect()->back()->withSuccess(['모든 쪽지가 삭제되었습니다']);
        }


        function getAllRelatedMessageIds(array $messageIds): array
        {
            $allIds = collect($messageIds); // 시작 ID들

            $stack = $messageIds;

            while (!empty($stack)) {
                $children = \App\Models\Message::whereIn('ref_id', $stack)->pluck('id')->toArray();

                if (empty($children)) {
                    break;
                }

                $allIds = $allIds->merge($children);
                $stack = $children;
            }

            return $allIds->unique()->values()->toArray();
        }

        public function message_updatemonitor(\Illuminate\Http\Request $request)
        {
            $max_odd = $request->max_odd;
            $max_win = $request->max_win;
            $odd = \App\Models\Settings::where('key', 'MaxOdd')->first();
            if ($odd) {
                $odd->update(['value' => $max_odd]);
            } else {
                \App\Models\Settings::create(
                    [
                        'key' => 'MaxOdd',
                        'value' => $max_odd
                    ]
                );
            }
            $win = \App\Models\Settings::where('key', 'MaxWin')->first();
            if ($win) {
                $win->update(['value' => $max_win]);
            } else {
                \App\Models\Settings::create(
                    [
                        'key' => 'MaxWin',
                        'value' => $max_win
                    ]
                );
            }

            return redirect()->back()->withSuccess(['알림설정이 업데이트되었습니다']);
        }

        public function readMessage(\Illuminate\Http\Request $request)
        {
            if (!\Illuminate\Support\Facades\Auth::check()) {
                return response()->json(['error' => 0]);
            }
            $msg = \App\Models\Message::where('id', $request->id)->first();
            if ($msg) {
                if ($msg->user_id == auth()->user()->id) {
                    $msg->update([
                        'read_at' => \Carbon\Carbon::now(),
                        'count' => 1
                    ]);
                } else if ($msg->user_id == \App\Models\Message::GROUP_MSG_ID) {
                    $msg->update([
                        'read_at' => \Carbon\Carbon::now(),
                        'count' => $msg->count + 1
                    ]);
                }
            }
            return response()->json(['error' => 0]);
        }
    }

}
