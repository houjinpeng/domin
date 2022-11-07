var BASE_URL = document.scripts[document.scripts.length - 1].src.substring(0, document.scripts[document.scripts.length - 1].src.lastIndexOf("/") + 1);
window.BASE_URL = BASE_URL;
require.config({
    urlArgs: "v=" + CONFIG.VERSION,
    baseUrl: BASE_URL,
    paths: {
        "jquery": ["plugs/jquery-3.4.1/jquery-3.4.1.min"],
        // "html2canvas": ["plugs/jquery-3.4.1/html2canvas"],
        "jquery-particleground": ["plugs/jq-module/jquery.particleground.min"],
        "echarts": ["plugs/echarts/echarts.min"],
        "swiper": ["plugs/swiper-bundle.min"],
        "echarts-theme": ["plugs/echarts/echarts-theme"],
        "easy-admin": ["plugs/easy-admin/easy-admin"],
        "layuiall": ["plugs/layui-v2.5.6/layui.all"],
        "layui": ["plugs/layui-v2.5.6/layui"],
        "miniAdmin": ["plugs/lay-module/layuimini/miniAdmin"],
        "miniMenu": ["plugs/lay-module/layuimini/miniMenu"],
        "miniTab": ["plugs/lay-module/layuimini/miniTab"],
        "miniTheme": ["plugs/lay-module/layuimini/miniTheme"],
        "miniTongji": ["plugs/lay-module/layuimini/miniTongji"],
        "treetable": ["plugs/lay-module/treetable-lay/treetable"],
        "tableSelect": ["plugs/lay-module/tableSelect/tableSelect"],
        "iconPickerFa": ["plugs/lay-module/iconPicker/iconPickerFa"],
        "autocomplete": ["plugs/lay-module/autocomplete/autocomplete"],
        "vue": ["plugs/vue-2.6.10/vue.min"],
        "ckeditor": ["plugs/ckeditor4/ckeditor"],
        "step": ["plugs/step-lay/step"],
        "random_name": ['plugs/name'],
    },
    waitSeconds: 0
});

// 路径配置信息
var PATH_CONFIG = {
    iconLess: BASE_URL + "plugs/font-awesome-4.7.0/less/variables.less",
};
window.PATH_CONFIG = PATH_CONFIG;

// 自定义模块，这里只需要开放soulTable即可
layui.config({
    base: '/static/plugs/',   // 第三方模块所在目录
    version: 'v1.6.4', // 插件版本号
    skin: 'layui-layer-easy'
}).extend({
    soulTable: 'soulTable/soulTable',
    Lodop: 'easy-lodop/Lodop',
    EasyLodop: 'easy-lodop/EasyLodop',
    tableChild: 'soulTable/tableChild',
    tableMerge: 'soulTable/tableMerge',
    tableFilter: 'soulTable/tableFilter',
    excel: 'soulTable/excel',
    xmSelect: 'xm-select/xm-select'    //下拉框组件

});

// 初始化控制器对应的JS自动加载
if ("undefined" != typeof CONFIG.AUTOLOAD_JS && CONFIG.AUTOLOAD_JS) {
    // console.log(BASE_URL + CONFIG.CONTROLLER_JS_PATH)
    require([BASE_URL + CONFIG.CONTROLLER_JS_PATH], function (Controller) {
        if (eval('Controller.' + CONFIG.ACTION)) {
            eval('Controller.' + CONFIG.ACTION + '()');
        }
    });
}