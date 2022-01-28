<nav class="navbar is-fixed-bottom has-text-centered is-flex is-box"
     style="width: 100%;border-top: 1px solid #eee;z-index: 999">
    @foreach($navs as $k=>$nav)
        @if(strpos($nav['old_data']['position'] , ',4,')!==false  &&  $nav['old_data']['show_menu'] ==1)
            <a href="{{$nav['__url']}}" class="tabbar__item_{{$k}} column is-relative">
                <div class="braui-tabbar__icon bra-img is-32x32 is-centered" style="margin:auto">
                    {!!$nav['image']!!}
                </div>
                <div class="braui-tabbar__txt" style="font-size:12px;padding-top:2px">

                    <div class="is-inline-block is-relative" style="width: 60px">{{$nav['title']}}
                        <div id="num_{{$k}}" style="display: none;position: absolute;height:20px;width:20px;line-height:20px;right: 10px;top:-35px" class=" num has-bg-danger has-text-white is-rounded"></div>
                    </div>

                </div>

            </a>
        @endif
    @endforeach
</nav>
