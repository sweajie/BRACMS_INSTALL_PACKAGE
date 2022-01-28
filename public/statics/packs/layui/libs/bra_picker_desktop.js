layui.define(['jquery', 'form'], function (exports) {
    var MOD_NAME = 'bra_picker_desktop';
    var $ = layui.jquery;
    var obj = function (config) {
        //当前选中数据值名数据
        this.selected = [];
        //当前选中的值
        this.values = [];
        //当前选中的名
        this.names = [];
        //当前选中最后一个值
        this.lastValue = '';
        //当前选中最后一个值
        this.lastName = '';
        //是否已选
        this.isSelected = false;
        //初始化配置
        this.config = {
            //选择器id或class
            elem: '',
            //无限级分类数据
            data: [],
            //默认选中值
            selected: [],
            //空值项提示，可设置为数组['请选择省','请选择市','请选择县']
            tips: '请选择',
            //是否允许搜索，可设置为数组[true,true,true]
            search: false,
            //选择项宽度，可设置为数组['80','90','100']
            width: null,
            //为真只取最后一个值
            last: false,
            //值验证，与lay-verify一致
            verify: '',
            //事件过滤器，lay-filter名
            filter: '',
            //input的name 不设置与选择器相同(去#.)
            name: '',
            form_id: '',
            //数据分隔符
            delimiter: ',',
            //数据的键名 status=0为禁用状态
            field: {idName: 'id', titleName: 'name', statusName: 'status', childName: 'children'},
            //多表单区分 form.render(type, filter); 为class="layui-form" 所在元素的 lay-filter="" 的值
            formFilter: null
        }

        //实例化配置
        this.config = $.extend(this.config, config);

        //“请选择”文字
        this.setTips = function () {
            var o = this, c = o.config;
            if (Object.prototype.toString.call(c.tips) != '[object Array]') {
                return c.tips;
            }
            else {
                var i = $(c.elem).find('select').length;
                return c.tips.hasOwnProperty(i) ? c.tips[i] : '请选择';
            }
        }


        //设置是否允许搜索
        this.setWidth = function () {
            var o = this, c = o.config;
            if (Object.prototype.toString.call(c.width) != '[object Array]') {
                return /^\d+$/.test(c.width) ? 'style="width:' + c.width + 'px;" ' : ' ';
            }
            else {
                var i = $(c.elem).find('select').length;
                if (c.width.hasOwnProperty(i)) {
                    return /^\d+$/.test(c.width[i]) ? 'style="width:' + c.width[i] + 'px;" ' : ' ';
                }
            }
        }

        //创建一个Select
        this.createSelect = function (optionData) {
            var o = this, c = o.config, f = c.field;
            var html = '';
            html += '<div class="bra-select layui-input-inline" ' + o.setWidth() + '>';
            html += ' <select lay-search '  + 'lay-filter="' + c.filter + '">';
            html += '  <option value="">' + o.setTips() + '</option>';
            for (var i = 0; i < optionData.length; i++) {
                var disabled = optionData[i][f.statusName] == 0 ? 'disabled="" ' : '';
                html += '  <option ' + disabled + 'value="' + optionData[i][f.idName] + '">' + optionData[i][f.titleName] + '</option>';
            }
            html += ' </select>';
            html += '</div>';
            return html;
        };

        //获取当前option的数据
        this.getOptionData = function (catData, optionIndex) {
            var f = this.config.field;
            var item = catData;
            for (var i = 0; i < optionIndex.length; i++) {
                if ('undefined' == typeof item[optionIndex[i]]) {
                    item = null;
                    break;
                }
                else if ('undefined' == typeof item[optionIndex[i]][f.childName]) {
                    item = null;
                    break;
                }
                else {
                    item = item[optionIndex[i]][f.childName];
                }
            }
            return item;
        };

        //初始化
        this.set = function (selected) {
            var o = this, c = o.config;
            $E = $(c.elem);
            //创建顶级select
            var verify = c.verify == '' ? '' : 'lay-verify="' + c.verify + '" ';
            var html = '<div style="height:0px;width:0px;overflow:hidden"><input value="' + c.default_value +  '" ' + verify + 'name="' + c.name + '"></div>';
            html += o.createSelect(c.data);
            $E.html(html);
            selected = typeof selected == 'undefined' ? c.selected : selected;
            var index = [];
            for (var i = 0; i < selected.length; i++) {
                //设置最后一个select的选中值
                $E.find('select:last').val(selected[i]);
                //获取该选中值的索引
                var lastIndex = $E.find('select:last').get(0).selectedIndex - 1;
                index.push(lastIndex);
                //取出下级的选项值
                var childItem = o.getOptionData(c.data, index);
                //下级选项值存在则创建select
                if (childItem && childItem.length > 0) {
                    var html = o.createSelect(childItem);
                    $E.append(html);
                }
            }
            o.getSelected();
        };

        //下拉事件
        this.change = function (elem) {
            var root = this, config = root.config;
            var $thisItem = elem.parent();
            //移除后面的select
            $thisItem.nextAll('div.layui-input-inline').remove();
            var index = [];
            //获取所有select，取出选中项的值和索引
            $thisItem.parent().find('select').each(function () {
                index.push($(this).get(0).selectedIndex - 1);
            });
            $('#' + config.form_id).val( $(elem).val())
            var childItem = root.getOptionData(config.data, index);
            if (childItem && childItem.length > 0) {
                var html = root.createSelect(childItem);
                $thisItem.after(html);
            }
            root.getSelected();
        };

        //获取所有值-数组 每次选择后执行
        this.getSelected = function () {
            var root = this, c = root.config;
            var values = [];
            var names = [];
            var selected = [];
            $E = $(c.elem);
            $E.find('select').each(function () {
                var item = {};
                var v = $(this).val()
                var n = $(this).find('option:selected').text();
                item.value = v;
                item.name = n;
                values.push(v);
                names.push(n);
                selected.push(item);



                $(this).one('change', function () {
                    root.change($(this));
                });
            });
            root.selected = selected;
            root.values = values;
            root.names = names;
            root.lastValue = $E.find('select:last').val();
            root.lastName = $E.find('option:selected:last').text();

            root.isSelected = root.lastValue == '' ? false : true;
            var inputVal = c.last === true ? root.lastValue : root.values.join(c.delimiter);
            if(root.lastValue === ''){
                if(root.values[root.values.length - 2]){
                    $('#' + c.form_id).val(root.values[root.values.length - 2])
                }
            }
            if(root.lastValue){
                console.log(root.lastValue)
                $('#' + c.form_id).val(root.lastValue)
            }

        };
        //ajax方式获取候选数据
        this.getData = function (url) {
            var d;
            $.ajax({
                url: url,
                dataType: 'json',
                async: false,
                success: function (json) {
                    d = json;
                },
                error: function () {
                    console.error(MOD_NAME + ' hint：候选数据ajax请求错误 ');
                    d = false;
                }
            });
            return d;
        }


    };

    //渲染一个实例
    obj.prototype.render = function () {
        var root = this, config = root.config;
        $E = $(config.elem);
        if ($E.length == 0) {
            console.error(MOD_NAME + ' hint：找不到容器 ' + config.elem);
            return false;
        }

        if (Object.prototype.toString.call(config.data) != '[object Array]') {
            var data = root.getData(config.data);
            if (data === false) {
                console.error(MOD_NAME + ' hint：缺少分类数据');
                return false;
            }
            root.config.data = data;
        }

        config.filter = config.filter == '' ? config.elem.replace('#', '').replace('.', '') : config.filter;
        config.name = config.name == '' ? config.elem.replace('#', '').replace('.', '') : config.name;
        root.config = config;

        //初始化
        root.set();

        //监听下拉事件
        $E.find('select').one('change', function (data) {
            root.change($(this));
        });

        //验证失败样式
        $E.find('input[name=' + config.name + ']').focus(function () {
            var t = $(config.elem).offset().top;
            $('html,body').scrollTop(t - 200);
            $(config.elem).find('select:last').addClass('layui-form-danger');
            setTimeout(function () {
                $(config.elem).find('select:last').removeClass('layui-form-danger');
            }, 3000);
        });
    }

    //输出模块
    exports(MOD_NAME, function (config) {
        var _this = new obj(config);
        _this.render();
        return _this;
    });
});
