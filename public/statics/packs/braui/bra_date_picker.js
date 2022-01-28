// 布拉组件 个个超神
define("bra_date_picker", ['braui', "mui.picker"], function () {

    return {
        bra_picker: {},
        init_picker: function (max_days) {

            var root = this;
            var now = new Date, now_minus_fided = now.getMinutes() % 10;
            now.setMinutes(now.getMinutes() + 10 - now_minus_fided);
            var days = [{
                text: "今天",
                value: (new Date).AddDay(0)
            }, {text: "明天", value: (new Date).AddDay(1)}, {
                text: "后天",
                value: (new Date).AddDay(2)
            }];
            var hours = [{text: "凌晨0点", value: 0}, {text: "凌晨1点", value: 1}, {
                text: "凌晨2点",
                value: 2
            }, {text: "凌晨3点", value: 3}, {text: "凌晨4点", value: 4}, {text: "凌晨5点", value: 5}, {
                text: "早上6点",
                value: 6
            }, {text: "早上7点", value: 7}, {text: "早上8点", value: 8}, {text: "上午9点", value: 9}, {
                text: "上午10点",
                value: 10
            }, {text: "上午11点", value: 11}, {text: "中午12点", value: 12}, {text: "下午13点", value: 13}, {
                text: "下午14点",
                value: 14
            }, {text: "下午15点", value: 15}, {text: "下午16点", value: 16}, {text: "下午17点", value: 17}, {
                text: "下午18点",
                value: 18
            }, {text: "晚上19点", value: 19}, {text: "晚上20点", value: 20}, {text: "晚上21点", value: 21}, {
                text: "晚上22点",
                value: 22
            }, {
                text: "晚上23点",
                value: 23
            }];

            var miniutes = [{
                text: "00分",
                value: 0
            }, {text: "10分", value: 10}, {text: "20分", value: 20}, {text: "30分", value: 30}, {
                text: "40分",
                value: 40
            }, {text: "50分", value: 50}];


            for (var i = 3; i < max_days; i++) {
                days.push({
                    text: new Date((new Date).AddDay(i)).Format("MM月dd日"),
                    value: (new Date).AddDay(i)
                });
            }
            for (i = 0; i < days.length; i++) {

                days[i].children = hours;
                for (var iu = 0; iu < days[i].children.length; iu++) {
                    days[i].children[iu].children = miniutes;
                }

            }

            days[0].children = days[0].children.slice(now.getHours());
            days[0].children[0].children = days[0].children[0].children.slice(now.getMinutes() / 10);
            window.picker || (window.picker = new mui.PopPicker({layer: 3}));
            this.bra_picker = window.picker;
            this.bra_picker.setData(days);
            now.setHours(now.getHours() + 2);
            var default_date = root.GoTime ? new Date(root.GoTime.replace(/-/g, "/")) : now;
            this.bra_picker.pickers[0].setSelectedValue(default_date.Format("yyyy-MM-dd"), 0, function () {
                picker.pickers[1].setSelectedValue(default_date.getHours(), 0, function () {
                    picker.pickers[2].setSelectedValue(default_date.getMinutes(), 0)
                })
            });
        },

        show: function (callback) {

            this.bra_picker.show(function (n) {

                var TimeShow = n[0].text + " " + n[1].value + ":" + n[2].value;
                var TimeValue = n[0].value + " " + n[1].value + ":" + n[2].value + ":00";

                if (callback && typeof callback === 'function') {
                    callback(TimeShow, TimeValue);
                }

            })
        }
    }

});