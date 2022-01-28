(function (require, define) {
    var path_obj = {
        baseUrl: '/statics/packs',
        paths: {
            'css': '../js/css.min',
            'async': '../packs/requirejs/async',
            'braui': '../packs/braui/braui',
            'bra_upload': '../packs/braui/bra_upload',
            'bra_page': '../packs/braui/bra_page',
            'bra_pay': '../packs/braui/bra_pay',
            'bra_map': '../packs/braui/bra_map',
            'bra_slider': '../packs/braui/bra_slider',
            'mui': '../packs/mui/mui.min',
            'mui.picker': '../packs/mui/mui.picker.min',
            'bra_date_picker': '../packs/braui/bra_date_picker',
            'bra_form': '../packs/braui/bra_form',
            'bra_verify': '../packs/braui/bra_verify',
            'iziToast': '../packs/izitoast/iziToast.min',
            'codemirror': "../packs/markdown/codemirror/lib/codemirror",
            "SimpleMDE": "../packs/simplemde/simplemde.min",
            "editormd": "../packs/editormd/editormd.amd",
            "marked": "../packs/markdown/marked.min",
            "prettify": "../packs/markdown/prettify.min",
            "sequenceDiagram": "../packs/markdown/sequence-diagram.min",
            "Raphael": "../packs/markdown/raphael.min",
            "underscore": "../packs/underscore/underscore.min",
            "katex": "../packs/katex/katex.min",
            "flowchart": "../packs/flowchart/flowchart.min",
            "jqueryflowchart": "../packs/flowchart/jquery.flowchart.min",

            "daterangepicker": "../packs/daterangepicker/daterangepicker",
            "moment": "../packs/moment/moment.min",
            "tabulator": '../packs/tabulator/tabulator.min',
            "tabulator_editor": '../packs/tabulator/tabulator_editor',
            "qrcode": "../packs/qrcode/qrcode",
            "qiniu": "../packs/qiniu/qiniu.min",
            'jquery.plupload.queue': "../packs/jquery.plupload.queue/jquery.plupload.queue.min",
            "plupload": "../packs/plupload/plupload.full.min",
            'wx': ['../packs/weixin/jweixin-1.3.0'],
            'layui': '../packs/layui/layui',
            'semantic': '../packs/semantic/semantic.min',
            'swiper4': '../packs/swiper/swiper4.min',
            'clipboard': '../packs/clipboard/clipboard',
            'swiper': '../packs/swiper/swiper.jquery.min',
            'BMap': ['https://api.map.baidu.com/api?v=2.0&ak=F51571495f717ff1194de02366bb8da9&s=1'],
            "amap": ["https://webapi.amap.com/maps?v=1.4.15&key=d79534a0453212d35a48049d689a3471"],
            'html2canvas': ['../packs/html2canvas/html2canvas.min'],
            'Vue': ['../packs/vue/vue.min'],
            'vue_router': ['../packs/vue/vue-router.min'],
            'vuex': ['../packs/vue/vuex.min'],
            // 'Vue': ['../packs/vue/vue'],
            'jquery': '../packs/jquery/jquery.min',
            'jquery.jplayer': '../packs/jplayer/jquery.jplayer.min',
            'jquery.zclip': '../packs/zclip/jquery.zclip.min',
            'bootstrap': 'https://cdn.bootcss.com/bootstrap/3.3.7/js/bootstrap.min',
            'VueLazyload': '../packs/vue/vue-lazyload',
            "echarts": "../packs/echarts/echarts.min",
            "ebmap": "../packs/echarts/ebmap",
            "china": "../packs/echarts/china",
            "neditor.config": "../packs/neditor/neditor.config",
            "neditor": "../packs/neditor/neditor.all",
            "zeroclipboard": "../packs/ueditor/third-party/zeroclipboard/ZeroClipboard.min",

            "ueditor.config": "../packs/ueditor/ueditor.config",
            "ueditor": "../packs/ueditor/ueditor.all",
            "jquery.ztree": "../packs/ztree/js/jquery.ztree.all.min",
            "jstree": "../packs/jstree/jstree.min",
            "Sortable": "../packs/sortable/sortable.min",
            "jquery.sortable": "../packs/sortable/jquery.sortable",
            "exif": "../packs/exif/exif",
            "pixi": "https://cdn.jsdelivr.net/npm/pixi.js@5.3.6/dist/pixi.min",
            "pixi-particles": "https://cdn.jsdelivr.net/npm/pixi-particles-latest@3.2.0/dist/pixi-particles.min",
            "layer": "../packs/layer/layer",
            "flow" : "../packs/flowjs/flow"
        },
        shim: {

            'braui': {
                deps: ['jquery' , 'layer'],
                exports: "braui"
            },
            'layer' : {
                deps : ["css!../packs/layer/theme/default/layer.css"] ,
                exports: 'layer'
            } ,
            'tabulator' : {
                deps : ["css!../packs/tabulator/tabulator.min"]
            } ,
            'tabulator_editor': {
                deps: ['tabulator']
            },
            'pixi': {
                exports: 'PIXI'
            },
            'pixi-particles': {
                deps: ['pixi']
            },
            'BMap': {
                exports: 'BMap'
            },
            'ebmap': {
                deps: ['echarts', 'BMap', 'china']
            },
            'exif': {
                exports: 'EXIF'
            },
            'Sortable': {
                exports: 'Sortable'
            },
            'sequenceDiagram': {
                deps: ['Raphael']
            },
            'flowchart': {
                deps: ['Raphael']
            },
            'jqueryflowchart': {
                deps: ['flowchart']
            },
            'bra_form' : {
                deps: ['jquery']
            },
            'neditor': {
                deps: ['zeroclipboard', 'neditor.config'],
                exports: 'UE',
                init: function (ZeroClipboard) {
                    window.ZeroClipboard = ZeroClipboard;
                }
            },
            'jquery.sortable': {
                deps: ['jquery', 'Sortable']
            },
            'ueditor': {
                deps: ['zeroclipboard', 'ueditor.config'],
                exports: 'UE',
                init: function (ZeroClipboard) {
                    window.ZeroClipboard = ZeroClipboard;
                }
            },
            'qiniu': {
                deps: ['jquery', 'plupload', 'braui']
            },
            'jquery.plupload.queue': {
                deps: ['jquery', 'plupload']
            },
            'jquery.ztree': {
                deps: ['jquery'],
                exports: "ztree"
            },
            'plupload': {
                deps: ['jquery'],
                exports: "plupload"
            },

            'weui': {
                deps: ['jquery'],
                exports: "weui"
            },
            'codemirror' : {
                deps : ["css!../packs/markdown/codemirror/codemirror.min.css"]
            } ,
            'editormd': {
                deps: [
                    "jquery",
                    "../packs/editormd/editormd.amd",
                    "../packs/markdown/languages/en",
                    "../packs/markdown/plugins/link-dialog/link-dialog",
                    "../packs/markdown/plugins/reference-link-dialog/reference-link-dialog",
                    "../packs/markdown/plugins/image-dialog/image-dialog",
                    "../packs/markdown/plugins/code-block-dialog/code-block-dialog",
                    "../packs/markdown/plugins/table-dialog/table-dialog",
                    "../packs/markdown/plugins/emoji-dialog/emoji-dialog",
                    "../packs/markdown/plugins/goto-line-dialog/goto-line-dialog",
                    "../packs/markdown/plugins/help-dialog/help-dialog",
                    "../packs/markdown/plugins/html-entities-dialog/html-entities-dialog",
                    "../packs/markdown/plugins/preformatted-text-dialog/preformatted-text-dialog" ,
                    "css!../packs/editormd/editormd.min.css" ,
                    "css!../packs/markdown/codemirror/codemirror.min.css"
                ]
            },

            'mui': {
                deps: ['jquery', 'css!../packs/mui/mui.min'],
                exports: "mui"
            },
            'iziToast': {
                deps: ['jquery', 'css!../packs/izitoast/iziToast.min'],
                exports: "mui"
            },
            "SimpleMDE": {
                deps: ['css!../packs/simplemde/simplemde.min']
            },
            'mui.picker': {
                deps: ['mui', 'css!../packs/mui/mui.picker.min'],
                exports: "mui"
            },
            'bra_date_picker': {
                deps: ['mui.picker'],
                exports: "bra_date_picker"
            },
            'wx': {
                exports: "wx"
            },
            'VueLazyload': {
                deps: ['jquery'],
                exports: "VueLazyload"
            },
            "Vue": {"exports": "Vue"}
            ,
            daterangepicker: {
                deps: ["moment", "css!../packs/daterangepicker/daterangepicker.css"]
            },
            'swiper': {
                deps: ['jquery', 'css!../packs/swiper/swiper.min']
            },
            swiper4: {
                deps: ['css!../packs/swiper/swiper4.min']
            },
            'layui': {
                exports: "layui",
                deps: ['jquery', 'css!../packs/layui/css/layui.css'],
                init: function () {
                    return this.layui.config({dir: '/statics/packs/layui/'});
                }
            },
            'semantic': {
                deps: ['jquery', 'css!../packs/semantic/semantic.min']
            },
            'jquery.jplayer': {
                exports: "$",
                deps: ['jquery']
            },
            'bootstrap': {
                exports: "$",
                deps: ['jquery']
            },
            'qrcode': {
                exports: "$",
                deps: ['jquery']
            },
            'vue_router': {
                exports: "VueRouter"
            },
            'vuex': {
                exports: "Vuex"
            }
        },
        waitSeconds: 0
    };
    if (typeof urlArgs !== "undefined") {
        path_obj.urlArgs = urlArgs;
    }
    require.config(path_obj);
})(require, define);
