layui.define([], function (exports) {
    // 字符常量
    const MODULE_NAME = 'EasyLodop'
    const config = {
        // 打印机名称
        printer: ''
    }

    class EasyLodop {
        // 构造
        constructor(option) {
            this.options = $.extend(true, {}, config, option)
            this.initialize()
        }

        // 初始化
        initialize() {
            console.log(MODULE_NAME, 'init done')
        }

        // 获取CLodop
        getCLodop() {
            if (typeof LODOP == 'undefined') {
                alert("Web打印服务CLodop未安装启动, 访问http://localhost:8000进行查看 或 进入官网http://www.lodop.net安装程序")
                return null
            }
            LODOP.SET_LICENSES(
                '广州棒谷网络科技有限公司',
                '596A7A1B8EE17DF0656CC684A683CA31',
                '廣州棒谷網絡科技有限公司',
                '73D9B734BDB7BF7FDBD6A8330BB4F81B'
            )
            LODOP.SET_LICENSES(
                'THIRD LICENSE',
                '',
                'Guangzhou Banggood Network Co., Ltd',
                'E2D39615BD3B81E5C011AA7B9C031AD1'
            )
            // 指定默认打印机可改变   Microsoft XPS Document Writer     Fax
            LODOP.SET_PRINT_MODE('WINDOW_DEFPRINTER', this.options.printer)
            // 持续广播回调
            LODOP.On_Broadcast_Remain = this.options.onBroadcastRemain || true
            // 广播回调
            LODOP.On_Broadcast = this.options.onBroadcast || new Function()
            // 持续回调
            LODOP.On_Return_Remain = this.options.onReturnRemain || true
            // 回调函数
            LODOP.On_Return = this.options.onReturn || new Function()
            return LODOP
        }

        // 渲染
        render() {
            // 获取打印机
            this.lodop = this.getCLodop()
            return this
        }

        // 异步静态渲染
        static render(options) {
            // 渲染
            return (new this(options)).render()
        }
    }

    // 注入容器
    exports(MODULE_NAME, EasyLodop)
})