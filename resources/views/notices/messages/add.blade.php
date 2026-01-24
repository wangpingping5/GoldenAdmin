@extends('layouts.app',[
        'parentSection' => $msgParentTitle,
        'elementName' => $msgElementTitle
    ])

@section('content')
<div class="container-fluid py-4">
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <h5 class="modal-title pt-4 ps-4">{{$type==1?('cs'):__('Message-list')}}</h5>
                    <div class="row px-5 py-4">
                        <form action="{{argon_route('msg.store')}}" class="border" method="POST">
                        @csrf
                        @if($refmsg != null)
                            <input type="hidden" value="{{$refmsg->writer_id?$refmsg->writer_id:''}}" name="user_id">
                        @endif                    
                            
                            <input type="hidden" id="ref_id" name="ref_id" value="{{$refmsg?$refmsg->id:0}}">
                            <input type="hidden" id="type" name="type" value="{{\Request::get('type')}}">
                            <div class="col">
                                <div class="row">
                                    <div class="col-md-9  col-12">
                                        <div class="row">
                                            <div class="align-items-center row py-2">
                                                <div class="col-md-2 col-3  text-center">
                                                    <label class="form-control-label mb-0">{{__('agent.Maker')}}</label>
                                                </div>
                                                <div class="col-md-10 col-9">
                                                    <input class="form-control" type="text" value="{{auth()->user()->username}}" disabled>
                                                </div>
                                            </div>
                                            @if($refmsg == null)
                                            <div class="align-items-center row py-2">
                                                <div class="col-md-2 col-3  text-center">
                                                    <label class="form-control-label mb-0">{{__('agent.SendType')}}</label>
                                                </div>
                                                <div class="col-md-2 col-3">
                                                    {!! Form::select('sendtype', \App\Models\Message::type(),['id' => 'sendtype', 'class' => 'form-control']) !!}                                              
                                                </div>
                                                <div class="col-md-2 col-3  text-center">
                                                    <label class="form-control-label mb-0">{{__('agent.Receiver')}}</label>
                                                </div>
                                                <div class="col-md-6 col-3">
                                                    <textarea class="form-control" type="text" id="user" name="user" rows="1"></textarea>                                               
                                                </div>
                                            </div>
                                            @else
                                            <div class="align-items-center row py-2">
                                                <div class="col-md-2 col-3  text-center">
                                                    <label class="form-control-label mb-0">{{__('agent.Receiver')}}</label>
                                                </div>
                                                <div class="col-md-10">
                                                    <input class="form-control" type="text" value="{{$refmsg->writer->username}}" disabled>
                                                </div>
                                            </div>
                                            @endif
                                            <div class="align-items-center row py-2">
                                                <div class="col-md-2 col-3  text-center">
                                                    <label class="form-control-label mb-0">{{__('agent.Title')}}</label>
                                                </div>
                                                <div class="col-md-10 col-9">
                                                    @if($refmsg == null)
                                                        <textarea class="form-control" type="text" rows="1" id="title" name="title"></textarea>
                                                    @else
                                                        <textarea class="form-control" type="text" rows="1" readonly id="title" name="title">[RE]{{$refmsg->title}}</textarea>
                                                    @endif
                                                    
                                                </div>
                                            </div>
                                            @if($refmsg)
                                                <div class="align-items-center row py-2">
                                                    <div class="col-md-2 col-3  text-center">
                                                        <label class="form-control-label mb-0">{{__('agent.Content')}}</label>
                                                    </div>
                                                    <div class="col-md-10 col-9">
                                                        <textarea class="form-control" type="text" rows="2" readonly>{{$refmsg->content}}</textarea>
                                                    </div>
                                                </div>
                                                
                                                <div class="align-items-center row py-2">
                                                    <div class="col-md-2 col-3  text-center">
                                                        <label class="form-control-label mb-0">{{__('agent.DateTime')}}</label>
                                                    </div>
                                                    <div class="col-md-10 col-9">
                                                        <input class="form-control" type="text" value="{{$refmsg?$refmsg->created_at:''}}" disabled>
                                                    </div>
                                                </div>
                                            @endif
                                            <div class="align-items-center row py-2">
                                                <div class="col-md-2 col-3  text-center">
                                                    @if($refmsg)
                                                        <label class="form-control-label mb-0">{{__('agent.Response')}}</label>
                                                    @else
                                                        <label class="form-control-label mb-0">{{__('agent.Content')}}</label>
                                                    @endif
                                                </div>
                                                <div class="col-md-10 col-9">
                                                    <div id="quillMsg" class="mb-3 leaflet" style="overflow: auto;" contenteditable="true"></div>
                                                    <textarea id="content" name="content" class="mb-3 d-none" rows="6"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-12 py-2">
                                        <select class="form-control col-12" size="29" id="selectbox" name="selectbox" onchange="chageLangSelect()">
                                            <option value="">--{{__('agent.Select')}}--</option>
                                            @foreach ($msgtemps as $msgtemp)
                                                <option value="{{$msgtemp->content}}">{{$msgtemp->title}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col border-top">
                                <div class="text-center row py-2 justify-content-center">
                                    <button type="submit" class="btn btn-primary mb-0 col-md-3 col-5 mx-2">{{__('agent.Send')}}</button>
                                    <a type="button" class="btn btn-danger mb-0 col-md-3 col-5 mx-2" id="frmclose" href="{{ argon_route('msg.list', ['type'=>\Request::get('type')])}}">{{__('Close')}}</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@stop

@push('js')
<script src="{{ asset('argon') }}/js/plugins/quill.min.js"></script>
<script>
  function chageLangSelect(){
        var msgtempSelect = document.getElementById("selectbox");
        var selectValue = msgtempSelect.options[msgtempSelect.selectedIndex].value;
        document.getElementById('content').innerHTML = selectValue;
        document.getElementById('quillMsg').innerHTML = selectValue;
    }

    var toolbarOptions = [
        ['bold', 'italic'],
        [{ 'size': ['small', false, 'large', 'huge'] }],
        [{ 'color': [] }, { 'background': [] }]
    ];

   // if (document.getElementById('content')) {
        var editor = new Quill('#quillMsg', {
            theme: 'snow'
        });
        
        var quillEditor = document.getElementById('content');
        editor.root.innerHTML = quillEditor.value;

        var userEditor = document.getElementById('user');
        var titleEditor = document.getElementById('title');

        quillEditor.addEventListener("change",(event)=>{
            alert('textarea has changed');
        });
        
        editor.on('text-change', function(delta, oldDelta, source) {
            quillEditor.value = editor.root.innerHTML;
        });
        quillEditor.addEventListener('input', function() {
            editor.root.innerHTML = quillEditor.value;
        });

        document.addEventListener('paste', (event) => {
            const clipboardData = event.clipboardData || window.clipboardData;
            const pastedText = clipboardData.getData('text/plain'); // 단순 텍스트 가져오기

            if (pastedText) {
                console.log('Notepad에서 복사된 텍스트:', pastedText);
                if(document.activeElement.id === "user")
                {
                    userEditor.value = pastedText;
                }
                else if(document.activeElement.id === "title")
                {
                    titleEditor.value = pastedText;
                }
                else
                {
                    // 텍스트를 Quill에 삽입
                    var prevText = editor.root.innerHTML;
                    editor.clipboard.dangerouslyPasteHTML(pastedText);
                    editor.root.innerHTML = prevText + editor.root.innerHTML;
                    event.preventDefault(); // 기본 붙여넣기 동작 방지
                }
            }
        });
        
       
    //}
    
</script>

@endpush