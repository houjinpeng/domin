layui.define([
    'EasyLodop'
], function (exports) {
    // 字符常量
    const MODULE_NAME = 'Lodop'
    const $ = layui.$
    const EasyLodop = layui.EasyLodop

    const config = {
        // 模板
        template: '',
        // 数据
        data: {},
        // 打印机名称
        // printer: 'ZDesigner GK888t (EPL)'
        printer: 'ZDesigner ZD888-203dpi ZPL'
    }

    // 模板
    const template = {
        /**
         // * data.title  标题  黑五网一
         // * data.num    编号   10010
         // * data.department  部门 信息技术部
         // * data.name   姓名   侯金朋
         * data.img_data   图片base64
         */
        print_quiz_number(data) {
            // let bdhtml=window.document.body.innerHTML;//获取当前页的html代码
            // LODOP.SET_SHOW_MODE("BKIMG_PRINT",1);//打印包含背景图
            // var strURL="http://spider.test/upload/20221021/%E6%9C%AA%E5%91%BD%E5%90%8D1_%E5%8A%A0%E6%B0%B4%E5%8D%B0.pdf";
            // this.lodop.ADD_PRINT_PDF(0,"134", "106.76mm","2800mm",strURL);
            // this.lodop.ADD_PRINT_HTM(0,"134", "106.76mm", "2800.mm",bdhtml)
            //打印图片 上边距  左边距  图片长 高
            this.lodop.ADD_PRINT_IMAGE(0,90,"148.01mm","700mm",data.img_data);
            //等比缩放
            this.lodop.SET_PRINT_STYLEA(0,"Stretch",2);
            // this.lodop.PRINT_DESIGN()
            this.lodop.PREVIEW();
            // this.lodop.PRINT()


        },

    }

    class Lodop {
        // 构造
        constructor(option) {
            this.options = $.extend(true, {}, config, option)
            // 初始化核心对象
            this.lodop = {}
            this.initialize()
        }

        // 初始化
        initialize() {
            console.log(MODULE_NAME, 'init done')
        }

        // 渲染
        render() {
            this.lodop = EasyLodop.render(this.options).lodop
            // 获取模板方法
            const fun = template[this.options.template]
            if (!$.isFunction(fun)) {
                console.error(`${this.options.template} 模板不正确`)
            }
            console.warn(this.options.data)
            // 调用
            fun.bind(this)(this.options.data)
            return this
        }


        // 异步静态渲染
        static render(options) {
            // 渲染
            return (new this(options)).render()
        }
    }

    // 注入容器
    exports(MODULE_NAME, Lodop)
});
