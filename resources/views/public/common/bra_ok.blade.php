<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, minimal-ui">

    <link rel="stylesheet" href="/statics/packs/braui/bra.min.css">
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->

    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body >
<div class="columns is-mobile is-vcentered is-gapless is-centered" style="width:100vw;height:90vh;">


    <div class="column is-narrow bra-dialog is-radius-large is-clipped is-box" style="min-width:300px;border-radius:6px">
        <div class="bra-dialog-header" style="font-size: 12px">错误提示</div>

        <div class="blockquote">
            <div class="content ">
                <p class="error has-text-danger"><?php echo(strip_tags($msg));?></p>
            </div>
        </div>


        <div class="bra-dialog-header has-bg-grey-light " style="font-size: 12px">
            <div class="jump has-text-right is-fullwidth"  style="width:100%">页面自动
                <a id="href" href="<?php echo($url);?>">跳转</a> 等待时间：
                <b id="wait">{{ $wait ?? 2 }}</b>
            </div>
        </div>
    </div>

</div>

@if(!isset($data['auto']))

    <script type="text/javascript">
        (function(){
            var wait = document.getElementById('wait'),
                href = document.getElementById('href').href;
            var interval = setInterval(function(){
                var time = --wait.innerHTML;
                if(time <= 0) {
                    location.href = href;
                    clearInterval(interval);
                }
            }, 1000);
        })();
    </script>


@endif

</body>
</html>
