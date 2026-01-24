<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="TMxNDIe7D3qYxZhM8z5ET9aaDxc9Km1wb8FMGiGz">

    <meta name="msapplication-TileColor" content="#0061da">
    <meta name="theme-color" content="#1643a3">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent"/>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="HandheldFriendly" content="True">
    <meta name="MobileOptimized" content="320">
    <link rel="icon" href="/favicon.ico" type="image/x-icon"/>
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />

    <title>배팅 상세내역</title>

    
    <!-- Dashboard css -->
<link href="/argon/css/gamedetail.css?v=20220811" rel="stylesheet" />

<!-- Font family -->
<link href="https://fonts.googleapis.com/css?family=Comfortaa:300,400,700" rel="stylesheet">


<style>

    
    th, td {
        text-align:center;
    }
    td.right{
        text-align:right;
    }
    td.left{
        text-align:left;
    }
    td.center{
        text-align:center;
    }
    th.left{
        text-align:left;
    }
</style>

        <style>
        body {
            background: white !important;
        }

        .cardFrame {
            float: left;
            width: 24px;
            height: 32px;
            border: 1px solid black;
            border-radius: 3px;
            margin-right: 5px;
            padding: 0px;
        }

        .cardFrame .number {
            font-weight: bold;
            position: absolute;
            padding-left: 3px;
        }

        .cardFrame .patten {
            font-weight: bold;
            position: absolute;
            font-size: 15px;
            padding-top: 10px;
            width: 30px;
            text-align: center;
        }

        .cardFrame .font_black {
            color: black;
        }

        .cardFrame .font_red {
            color: red;
        }

        .benefit_r {
            color: red;
        }

        .benefit_b {
            color: blue;
        }

        #bettingTable span {
            top: 25%;
            position: relative;
        }

        #bettingTable th {
            letter-spacing: -1px;
            text-align: center;
            padding: 8px 5px;
        }

        #bettingTable td {
            vertical-align: middle;
            padding: 8px 5px;
        }

        .roulresult {
            font-size: 20px;
            border-radius: 15px;
            width: 30px;
            height: 30px;
            color: white;
        }

    </style>

</head>

<body class="bg-gradient-primary">
    <div class="page">
            <div class="row">
        <div class="card">
            <div class="card-body">
                <table class="table card-table table-vcenter table-border" style="table-layout: fixed;">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th class="text-white">항목</th>
                            <th class="text-white">값</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($res)
                        @if(View::exists('log.sharegame.gamedetail.' . $res['type']))
                            @include('log.sharegame.gamedetail.' . $res['type'])
                        @else
                            @include('log.sharegame.gamedetail.General')
                        @endif
                        @else
                            <tr>
                                <td colspan='2'>상세보기를 지원하지 않는 게임입니다.</td>
                            </tr>
                        @endif
                    </tbody>                                
                </table>
            </div>
        </div>
    </div>
    </div>    
</body>
</html>
