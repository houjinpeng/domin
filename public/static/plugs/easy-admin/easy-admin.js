define(["jquery", "tableSelect", "ckeditor"], function ($, tableSelect, undefined) {
    var form = layui.form,
        layer = layui.layer,
        table = layui.table,
        laydate = layui.laydate,
        upload = layui.upload,
        element = layui.element,
        laytpl = layui.laytpl,
        exportSearchValue = false,
        exportSearchType = false,
        batch_xmSelect = null,
        group_xmSelect = null
        tableSelect = layui.tableSelect;
    layer.config({
        skin: 'layui-layer-easy'
    });
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        upload_url: 'ajax/upload',
        upload_exts: 'doc|gif|ico|icon|jpg|mp3|mp4|p12|pem|png|rar',
    };

    var admin = {

        //获取日期
        GetDateStr:function (AddDayCount) {
            var dd = new Date();
            dd.setDate(dd.getDate() + AddDayCount);//获取AddDayCount天后的日期
            var y = dd.getFullYear();
            var m = dd.getMonth() + 1;//获取当前月份的日期
            var d = dd.getDate();
            if ( m < 10){
                m = '0'+m
            }
            if ( d < 10){
                d = '0'+d
            }

            return y + "-" + m + "-" + d;
        },
        config: {
            shade: [0.02, '#000'],
        },
        url: function (url) {
            return '/' + CONFIG.ADMIN + '/' + url;
        },
        checkAuth: function (node, elem) {
            if (CONFIG.IS_SUPER_ADMIN) {
                return true;
            }
            if ($(elem).attr('data-auth-' + node) === '1') {
                return true;
            } else {
                return false;
            }
        },

        timestampToTime: function () {
            var date = new Date();//时间戳为10位需*1000，时间戳为13位的话不需乘1000
            var Y = date.getFullYear() + '-';
            var M = (date.getMonth()+1 < 10 ? '0'+(date.getMonth()+1):date.getMonth()+1) + '-';
            var D = (date.getDate()< 10 ? '0'+date.getDate():date.getDate())+ ' ';
            var h = (date.getHours() < 10 ? '0'+date.getHours():date.getHours())+ ':';
            var m = (date.getMinutes() < 10 ? '0'+date.getMinutes():date.getMinutes()) + ':';
            var s = date.getSeconds() < 10 ? '0'+date.getSeconds():date.getSeconds();
            return Y+M+D+h+m+s;
        },

        copyText:function (text) {
            var oInput = document.createElement('textarea');
            oInput.value = text;
            document.body.appendChild(oInput);
            oInput.select();
            document.execCommand("Copy");
            oInput.className = 'oInput';
            oInput.style.display = 'none';
        },

        parame: function (param, defaultParam) {
            return param !== undefined ? param : defaultParam;
        },
        request: {
            post: function (option, ok, no, ex) {
                return admin.request.ajax('post', option, ok, no, ex);
            },
            get: function (option, ok, no, ex) {
                return admin.request.ajax('get', option, ok, no, ex);
            },
            ajax: function (type, option, ok, no, ex) {


                type = type || 'get';
                option.url = option.url || '';
                option.data = option.data || {};
                option.prefix = option.prefix || false;
                option.statusName = option.statusName || 'code';
                option.statusCode = option.statusCode || 1;
                option.show_loading = option.show_loading == false ? false : true;

                ok = ok || function (res) {
                };
                no = no || function (res) {
                    var msg = res.msg == undefined ? '返回数据格式有误' : res.msg;
                    admin.msg.error(msg);
                    return false;
                };
                ex = ex || function (res) {
                };
                if (option.url == '') {
                    admin.msg.error('请求地址不能为空');
                    return false;
                }
                if (option.prefix == true) {
                    option.url = admin.url(option.url);
                }
                if (option.show_loading == true){
                    var index = admin.msg.loading('加载中');
                }
                $.ajax({
                    url: option.url,
                    type: type,
                    contentType: "application/x-www-form-urlencoded; charset=UTF-8",
                    dataType: "json",
                    data: option.data,
                    timeout: 120*1000,

                    success: function (res) {
                        if (option.show_loading == true){
                            admin.msg.close(index);
                        }
                        if (eval('res.' + option.statusName) == option.statusCode) {
                            return ok(res);
                        } else {
                            return no(res);
                        }
                    },
                    error: function (xhr, textstatus, thrown) {
                        admin.msg.error('Status:' + xhr.status + '，' + xhr.statusText + '，请稍后再试！', function () {
                            ex(this);
                        });
                        return false;
                    }
                });
            }
        },
        common: {
            parseNodeStr: function (node) {
                var array = node.split('/');
                $.each(array, function (key, val) {
                    if (key === 0) {
                        val = val.split('.');
                        $.each(val, function (i, v) {
                            val[i] = admin.common.humpToLine(v.replace(v[0], v[0].toLowerCase()));
                        });
                        val = val.join(".");
                        array[key] = val;
                    }
                });
                node = array.join("/");
                return node;
            },
            lineToHump: function (name) {
                return name.replace(/\_(\w)/g, function (all, letter) {
                    return letter.toUpperCase();
                });
            },
            humpToLine: function (name) {
                return name.replace(/([A-Z])/g, "_$1").toLowerCase();
            },
        },
        msg: {
            // 成功消息
            success: function (msg, callback) {
                if (callback === undefined) {
                    callback = function () {
                    }
                }
                var index = layer.msg(msg, { icon: 1, shade: admin.config.shade, scrollbar: false, time: 1000, shadeClose: true }, callback);
                return index;
            },
            // 失败消息
            error: function (msg, callback) {
                if (callback === undefined) {
                    callback = function () {
                    }
                }
                var index = layer.msg(msg, { icon: 2, shade: admin.config.shade, scrollbar: false, time: 3000, shadeClose: true }, callback);
                return index;
            },
            // 警告消息框
            alert: function (msg, callback) {
                var index = layer.alert(msg, { end: callback, scrollbar: false });
                return index;
            },
            // 对话框
            confirm: function (msg, ok, no) {
                var index = layer.confirm(msg, { title: '操作确认', btn: ['确认', '取消'] }, function () {
                    typeof ok === 'function' && ok.call(this);
                }, function () {
                    typeof no === 'function' && no.call(this);
                    self.close(index);
                });
                return index;
            },
            // 消息提示
            tips: function (msg, time, callback) {
                var index = layer.msg(msg, { time: (time || 3) * 1000, shade: this.shade, end: callback, shadeClose: true });
                return index;
            },
            // 加载中提示
            loading: function (msg, callback) {
                var index = msg ? layer.msg(msg, { icon: 16, scrollbar: false, shade: this.shade, time: 0, end: callback }) : layer.load(2, { time: 0, scrollbar: false, shade: this.shade, end: callback });
                return index;
            },
            // 关闭消息框
            close: function (index) {
                return layer.close(index);
            },
            //返回进度条
            //admin.msg.progress()
            progress: function(total,fill,allTotal,allFill,id){
                let progress = 0
                if(fill > 0 && total > 0){
                    progress = (fill / total * 100).toFixed(2)
                }
                let progressStyle = "layui-bg-orange"
                if(fill === total){
                    progressStyle = "layui-bg-green"
                }
                return '<div data-id="'+id+'" class="layui-progress layui-progress-big" lay-showpercent="true" title="产品数:' + fill + '/' + total + '\n变体数'+ allFill + '/' + allTotal+ '">' +
                    '<div class="layui-progress-bar ' + progressStyle + '" lay-percent="' + progress + '%" style="width: ' + progress + '%;">' +
                    '<span class="layui-progress-text">' + progress + '%</span>' +
                    '</div>' +
                    '</div>'
            }
        },
        table: {
            render: function (options) {
                options.init = options.init || init;
                options.modifyReload = admin.parame(options.modifyReload, true);
                options.elem = options.elem || options.init.table_elem;
                options.id = options.id || options.init.table_render_id;
                options.layFilter = options.id + '_LayFilter';
                options.url = options.url || admin.url(options.init.index_url);
                options.page = admin.parame(options.page, true);
                options.search = admin.parame(options.search, true);
                options.skin = options.skin || 'line';
                options.limit = options.limit || 15;
                options.limits = options.limits || [10, 15, 20, 25, 50, 100];
                options.cols = options.cols || [];
                // 判断是否是自定义
                if ((options.customDefaultToolbar == false || options.customDefaultToolbar == undefined)) {
                    options.defaultToolbar = (options.defaultToolbar === undefined && !options.search) ? ['filter', 'print', 'exports'] : ['filter', 'print', 'exports', {
                        title: '搜索',
                        layEvent: 'TABLE_SEARCH',
                        icon: 'layui-icon-search',
                        extend: 'data-table-id="' + options.id + '"'
                    }];
                    // 判断是否为移动端
                    if (admin.checkMobile()) {
                        options.defaultToolbar = !options.search ? ['filter'] : ['filter', {
                            title: '搜索',
                            layEvent: 'TABLE_SEARCH',
                            icon: 'layui-icon-search',
                            extend: 'data-table-id="' + options.id + '"'
                        }];
                    }
                }else {
                    options.defaultToolbar = options.customDefaultToolbar
                }

                // 判断元素对象是否有嵌套的
                options.cols = admin.table.formatCols(options.cols, options.init);

                // 初始化表格lay-filter
                $(options.elem).attr('lay-filter', options.layFilter);

                // 初始化表格搜索
                if (options.search === true) {
                    admin.table.renderSearch(options.cols, options.elem, options.id);
                }
                // 初始化表格左上方工具栏
                if (options.costomToolbar !== true) {
                    options.toolbar = options.toolbar || ['refresh', 'add', 'delete', 'export'];
                    options.toolbar = admin.table.renderToolbar(options.toolbar, options.elem, options.id, options.init);
                }

                // 判断是否有操作列表权限
                options.cols = admin.table.renderOperat(options.cols, options.elem);
                // 初始化表格
                var newTable = table.render(options);

                // 监听表格搜索开关显示
                admin.table.listenToolbar(options.layFilter, options.id, options.defaultToolbar);

                // 监听表格开关切换
                admin.table.renderSwitch(options.cols, options.init, options.id, options.modifyReload);

                // 监听表格开关切换
                admin.table.listenEdit(options.init, options.layFilter, options.id, options.modifyReload);
                // 监听排序
                admin.table.listenSort(options.init, options.layFilter, options.id, options.modifyReload);
                return newTable;
            },
            renderToolbar: function (data, elem, tableId, init) {
                data = data || [];
                var toolbarHtml = '';
                $.each(data, function (i, v) {
                    if (v === 'refresh') {
                        toolbarHtml += ' <button class="layui-btn layui-btn-sm layuimini-btn-primary" data-table-refresh="' + tableId + '"><i class="fa fa-refresh"></i> </button>\n';
                    } else if (v === 'add') {
                        if (admin.checkAuth('add', elem)) {
                            toolbarHtml += '<button class="layui-btn layui-btn-normal layui-btn-sm" data-open="' + init.add_url + '" data-title="添加"><i class="fa fa-plus"></i> 添加</button>\n';
                        }
                    } else if (v === 'delete') {
                        if (admin.checkAuth('delete', elem)) {
                            toolbarHtml += '<button class="layui-btn layui-btn-sm layui-btn-danger" data-url="' + init.delete_url + '" data-table-delete="' + tableId + '"><i class="fa fa-trash-o"></i> 删除</button>\n';
                        }
                    } else if (v === 'export') {
                        if (admin.checkAuth('export', elem)) {
                            toolbarHtml += '<button class="layui-btn layui-btn-sm layui-btn-success" data-url="' + init.export_url + '" data-table-export="' + tableId + '"><i class="fa fa-file-excel-o"></i> 导出</button>\n';
                        }
                    }
                    else if (typeof v === "object") {

                        $.each(v, function (ii, vv) {
                            vv.class = vv.class || '';
                            vv.icon = vv.icon || '';
                            vv.auth = vv.auth || '';
                            vv.url = vv.url || '';
                            vv.method = vv.method || 'open';
                            vv.title = vv.title || vv.text;
                            vv.text = vv.text || vv.title;
                            vv.extend = vv.extend || '';
                            vv.event = vv.event || '';
                            vv.checkbox = vv.checkbox || false;
                            if (admin.checkAuth(vv.auth, elem)) {
                                toolbarHtml += admin.table.buildToolbarHtml(vv, tableId);
                            }

                        });

                    }

                    else if(v === 'openweb') {  //打开网址
                        toolbarHtml += '<button class="layui-btn layui-btn-sm layui-btn-success" data-url="' + init.openweb_url + '" data-table-openweb="' + tableId + '"><i class="fa fa-file-excel-o"></i> 打开网址</button>\n';

                    }else if(v === 'batch_export') {  //批量导出
                        toolbarHtml += '<button class="layui-btn layui-btn-sm layui-btn-success" data-url="' + init.batch_export_url + '" data-table-batch_export="' + tableId + '"><i class="fa fa-file-excel-o"></i>批量导出</button>\n';

                    }else if(v === 'batch_connect') {  //批量对接
                        toolbarHtml += '<button class="layui-btn layui-btn-sm layui-btn-success" data-url="' + init.batch_connect_url + '" data-table-batch_connect="' + tableId + '"><i class="fa fa-plus"></i>确定对接</button>\n';

                    }else if(v === 'seo_finish') {  //批量完成
                        toolbarHtml += '<button class="layui-btn layui-btn-sm layui-btn-success" data-url="' + init.seo_finish_url + '" data-table-seo_finish="' + tableId + '"><i class="fa fa-plus"></i>确定完成</button>\n';

                    }
                });
                return '<div>' + toolbarHtml + '</div>';
            },
            renderSearch: function (cols, elem, tableId) {
                // TODO 只初始化第一个table搜索字段，如果存在多个(绝少数需求)，得自己去扩展
                cols = cols[0] || {};
                var newCols = [];
                var formHtml = '';
                $.each(cols, function (i, d) {
                    d.field = d.field || false;
                    d.fieldAlias = admin.parame(d.fieldAlias, d.field);
                    d.title = d.title || d.field || '';
                    d.selectList = d.selectList || {};
                    d.search = admin.parame(d.search, true);
                    d.searchTip = d.searchTip || '请输入' + d.title || '';
                    d.searchValue = d.searchValue || '';
                    d.searchOp = d.searchOp || '%*%';
                    d.timeType = d.timeType || 'datetime';
                    if (d.field !== false && d.search !== false) {
                        switch (d.search) {
                            case true:
                                formHtml += '\t<div class="layui-form-item layui-inline">\n' +
                                    '<label class="layui-form-label">' + d.title + '</label>\n' +
                                    '<div class="layui-input-inline">\n' +
                                    '<input id="c-' + d.fieldAlias + '" name="' + d.fieldAlias + '" data-search-op="' + d.searchOp + '" value="' + d.searchValue + '" placeholder="' + d.searchTip + '" class="layui-input">\n' +
                                    '</div>\n' +
                                    '</div>';
                                break;
                            case 'eq':
                                d.searchOp = '=';
                                formHtml += '\t<div class="layui-form-item layui-inline">\n' +
                                    '<label class="layui-form-label">' + d.title + '</label>\n' +
                                    '<div class="layui-input-inline">\n' +
                                    '<input id="c-' + d.fieldAlias + '" name="' + d.fieldAlias + '" data-search-op="' + d.searchOp + '" value="' + d.searchValue + '" placeholder="' + d.searchTip + '" class="layui-input">\n' +
                                    '</div>\n' +
                                    '</div>';
                                break;
                            case 'select':
                                d.searchOp = '=';
                                var selectHtml = '';
                                $.each(d.selectList, function (sI, sV) {
                                    var selected = '';
                                    if (sI === d.searchValue) {
                                        selected = 'selected=""';
                                    }
                                    selectHtml += '<option value="' + sI + '" ' + selected + '>' + sV + '</option>/n';
                                });
                                formHtml += '\t<div class="layui-form-item layui-inline">\n' +
                                    '<label class="layui-form-label">' + d.title + '</label>\n' +
                                    '<div class="layui-input-inline">\n' +
                                    '<select lay-search class="layui-select" id="c-' + d.fieldAlias + '" name="' + d.fieldAlias + '"  data-search-op="' + d.searchOp + '" ' + d.selectAttribute + ' >\n' +
                                    '<option value="">- 全部 -</option> \n' +
                                    selectHtml +
                                    '</select>\n' +
                                    '</div>\n' +
                                    '</div>';
                                break;
                            case 'range':
                                d.searchOp = 'range';
                                formHtml += '\t<div class="layui-form-item layui-inline">\n' +
                                    '<label class="layui-form-label">' + d.title + '</label>\n' +
                                    '<div class="layui-input-inline">\n' +
                                    '<input id="c-' + d.fieldAlias + '" name="' + d.fieldAlias + '"  data-search-op="' + d.searchOp + '"  value="' + d.searchValue + '" placeholder="' + d.searchTip + '" class="layui-input" autocomplete="off">\n' +
                                    '</div>\n' +
                                    '</div>';
                                break;
                            case 'time':
                                d.searchOp = '=';
                                formHtml += '\t<div class="layui-form-item layui-inline">\n' +
                                    '<label class="layui-form-label">' + d.title + '</label>\n' +
                                    '<div class="layui-input-inline">\n' +
                                    '<input id="c-' + d.fieldAlias + '" name="' + d.fieldAlias + '"  data-search-op="' + d.searchOp + '"  value="' + d.searchValue + '" placeholder="' + d.searchTip + '" class="layui-input" autocomplete="off">\n' +
                                    '</div>\n' +
                                    '</div>';
                                break;
                            case 'batch':
                                d.searchOp = 'IN';
                                formHtml += '\t<div class="layui-form-item layui-inline">\n' +
                                    '<label class="layui-form-label" style="margin-top: 4px">' + d.title + '</label>\n' +
                                    '<div class="layui-input-block" style="margin-top: 4px">\n' +
                                    '<textarea  style="padding: 6px; overflow-y: hidden; height: 32px; width: 200px;" cols="30" id="c-' + d.fieldAlias + '" name="' + d.fieldAlias + '"  data-search-op="' + d.searchOp + '"  value="' + d.searchValue + '" placeholder="' + d.searchTip + '" class="layui-input"></textarea>\n' +
                                    '</div>\n' +
                                    '</div>';
                                break;
                            // 需要自己拓展  区间搜索
                            case 'section':
                                d.searchOp = '=';
                                formHtml += '\t<div class="layui-form-item layui-inline" style="margin-top:6px">' +
                                    '<label class="layui-form-label">'+ d.title +'</label>' +
                                    '<div class="layui-input-inline" style="width: 90px">' +
                                    '<input id="c-' + d.fieldAlias +'_min" type="text" lay-verify="number" name="'+ d.fieldAlias +'_min" placeholder="最小值" data-search-op="' + d.searchOp + '"  value="' + d.searchValue + '" placeholder="' + d.searchTip + '"  class="layui-input" autocomplete="off">' +
                                    '</div>\n' +
                                    '<div class="layui-form-mid">-</div><div class="layui-input-inline" style="width: 90px">' +
                                    '<input id="c-' + d.fieldAlias +'_max" type="text" lay-verify="number" name="'+ d.fieldAlias +'_max" placeholder="最大值"  data-search-op="' + d.searchOp + '"  value="' + d.searchValue + '" placeholder="' + d.searchTip + '" class="layui-input" autocomplete="off"></div>' +
                            '</div>\n';
                                break;
                            //xmselect 搜索框
                            case 'xmSelect':
                                d.searchOp = '=';
                                formHtml += '\t<div class="layui-form-item layui-inline">\n' +
                                    '<label class="layui-form-label" style="margin-top: 4px">' + d.title + '</label>\n' +
                                    '<div class="layui-input-block" style="margin-top: 4px;width: 200px" id="'+ d.field +'">\n' +
                                    '</div>\n' +
                                    '</div>';
                                break;

                        }
                        newCols.push(d);
                    }
                });
                if (formHtml !== '') {

                    $(elem).before('<fieldset id="searchFieldset_' + tableId + '" class="table-search-fieldset layui-hide">\n' +
                        '<legend>条件搜索</legend>\n' +
                        '<form class="layui-form layui-form-pane form-search">\n' +
                        formHtml +
                        '<div class="layui-form-item layui-inline" style="margin-left: 115px">\n' +
                        '<button type="submit" class="layui-btn layui-btn-normal" data-type="tableSearch" data-table="' + tableId + '" lay-submit lay-filter="' + tableId + '_filter"> 搜 索</button>\n' +
                        '<button type="reset" class="layui-btn layui-btn-primary" data-table-reset="' + tableId + '"> 重 置 </button>\n' +
                        ' </div>' +
                        '</form>' +
                        '</fieldset>');

                    admin.table.listenTableSearch(tableId);

                    // 初始化form表单
                    form.render();
                    $.each(newCols, function (ncI, ncV) {
                        if (ncV.search === 'range') {
                            laydate.render({ range: true, type: ncV.timeType, elem: '[name="' + ncV.field + '"]' });
                        }
                        if (ncV.search === 'time') {
                            laydate.render({ type: ncV.timeType, elem: '[name="' + ncV.field + '"]' });
                        }if (ncV.search === 'xmSelect'){
                            if (ncV.xmSelectType === 'tree'){

                                var xm_use = ncV.xmUse || [];

                                var xm_data = []
                                var init_data = admin.getGroupTree()

                                for(var i in init_data){
                                    if (xm_use.indexOf(init_data[i]['name']) !== -1){
                                        xm_data.push(init_data[i])
                                    }
                                }

                                group_xmSelect = xmSelect.render({
                                    el: '#'+ncV.field ,
                                    name:ncV.field,
                                    filterable: true,
                                    tree: {
                                        show: true,
                                    },
                                    style:{
                                        'min-height':31
                                    },
                                    toolbar: {
                                        show: true,
                                        list: ['ALL', 'REVERSE', 'CLEAR']
                                    },
                                    height: 'auto',
                                    data: xm_data,
                                })

                            }
                            else{
                                batch_xmSelect =xmSelect.render({
                                    name:ncV.field,
                                    style:{
                                        'min-height':31
                                    },
                                    el: '#'+ncV.field ,
                                    size: 'small',
                                    toolbar: {
                                        show: true,
                                    },
                                    filterable: true,
                                    height: '500px',
                                    data: ncV.xm_data
                                })
                            }
                        }

                    });
                }
            },
            renderSwitch: function (cols, tableInit, tableId, modifyReload) {
                tableInit.modify_url = tableInit.modify_url || false;
                cols = cols[0] || {};
                tableId = tableId || init.table_render_id;
                if (cols.length > 0) {
                    $.each(cols, function (i, v) {
                        v.filter = v.filter || false;
                        if (v.filter !== false && tableInit.modify_url !== false) {
                            admin.table.listenSwitch({ filter: v.filter, url: tableInit.modify_url, tableId: tableId, modifyReload: modifyReload });
                        }
                    });
                }
            },
            renderOperat(data, elem) {
                for (dk in data) {
                    var col = data[dk];
                    var operat = col[col.length - 1].operat;
                    if (operat !== undefined) {
                        var check = false;
                        for (key in operat) {
                            var item = operat[key];
                            if (typeof item === 'string') {
                                if (admin.checkAuth(item, elem)) {
                                    check = true;
                                    break;
                                }
                            } else {
                                for (k in item) {
                                    var v = item[k];
                                    if (v.auth !== undefined && admin.checkAuth(v.auth, elem)) {
                                        check = true;
                                        break;
                                    }
                                }
                            }
                        }
                        if (!check) {
                            data[dk].pop()
                        }
                    }
                }
                return data;
            },
            buildToolbarHtml: function (toolbar, tableId) {
                var html = '';
                toolbar.class = toolbar.class || '';
                toolbar.event = toolbar.event || '';
                toolbar.icon = toolbar.icon || '';
                toolbar.auth = toolbar.auth || '';
                toolbar.url = toolbar.url || '';
                toolbar.extend = toolbar.extend || '';
                toolbar.method = toolbar.method || 'open';
                toolbar.field = toolbar.field || 'id';
                toolbar.title = toolbar.title || toolbar.text;
                toolbar.text = toolbar.text || toolbar.title;
                toolbar.checkbox = toolbar.checkbox || false;
                var formatToolbar = toolbar;
                formatToolbar.icon = formatToolbar.icon !== '' ? '<i class="' + formatToolbar.icon + '"></i> ' : '';
                formatToolbar.class = formatToolbar.class !== '' ? 'class="' + formatToolbar.class + '" ' : '';
                if (toolbar.method === 'open') {
                    formatToolbar.method = formatToolbar.method !== '' ? 'data-open="' + formatToolbar.url + '" data-title="' + formatToolbar.title + '" ' : '';
                }
                //增加tags标签
                else if (toolbar.method === 'tags') {
                    formatToolbar.method = formatToolbar.method !== '' ? 'data-tags="' + formatToolbar.url + '" data-title="' + formatToolbar.title + '" ' : '';
                }
                else if (toolbar.method === 'request') {
                    formatToolbar.method = formatToolbar.method !== '' ? 'data-request="' + formatToolbar.url + '" data-title="' + formatToolbar.title + '" ' : '';
                }
                formatToolbar.checkbox = toolbar.checkbox ? ' data-checkbox="true" ' : '';
                formatToolbar.tableId = tableId !== undefined ? ' data-table="' + tableId + '" data-table-' + toolbar.auth + '="' + tableId + '" ' : '';
                if (toolbar.event){
                    html = '<button lay-event="'+toolbar.event+'" ' + formatToolbar.class + formatToolbar.method + formatToolbar.extend + formatToolbar.checkbox + formatToolbar.tableId + '>' + formatToolbar.icon + formatToolbar.text + '</button>';
                }else{
                    html = '<button ' + formatToolbar.class + formatToolbar.method + formatToolbar.extend + formatToolbar.checkbox + formatToolbar.tableId + '>' + formatToolbar.icon + formatToolbar.text + '</button>';

                }
                // html = '<button ' + formatToolbar.class + formatToolbar.method + formatToolbar.extend + formatToolbar.checkbox + formatToolbar.tableId + '>' + formatToolbar.icon + formatToolbar.text + '</button>';

                return html;
            },
            buildOperatHtml: function (operat) {
                var html = '';
                operat.class = operat.class || '';
                operat.icon = operat.icon || '';
                operat.auth = operat.auth || '';
                operat.url = operat.url || '';
                operat.extend = operat.extend || '';
                operat.method = operat.method || 'open';
                operat.field = operat.field || 'id';
                operat.title = operat.title || operat.text;
                operat.text = operat.text || operat.title;
                operat.tableId = operat.tableId || '';

                var formatOperat = operat;
                formatOperat.icon = formatOperat.icon !== '' ? '<i class="' + formatOperat.icon + '"></i> ' : '';
                formatOperat.class = formatOperat.class !== '' ? 'class="' + formatOperat.class + '" ' : '';
                if (operat.method === 'open') {
                    formatOperat.method = formatOperat.method !== '' ? 'data-open="' + formatOperat.url + '" data-title="' + formatOperat.title + '" data-table="' + operat.tableId + '" ' : '';
                }else if(operat.method === 'ajax'){
                    formatOperat.method = formatOperat.method !== '' ? 'data-ajax="' + formatOperat.url + '" data-title="' + formatOperat.title + '" data-action="'+ formatOperat.action +'" '  : '';
                }else if(operat.method === 'down_load_page'){
                    formatOperat.method = formatOperat.method !== '' ? 'down_load_page="' + formatOperat.url + '" data-title="' + formatOperat.title + '" data-action="'+ formatOperat.action +'" '  : '';
                }
                else {
                    formatOperat.method = formatOperat.method !== '' ? 'data-request="' + formatOperat.url + '" data-title="' + formatOperat.title + '" data-table="' + operat.tableId + '" ' : '';
                }
                html = '<a ' + formatOperat.class + formatOperat.method + formatOperat.extend + '>' + formatOperat.icon + formatOperat.text + '</a>';

                return html;
            },
            toolSpliceUrl(url, field, data) {
                url = url.indexOf("?") !== -1 ? url + '&' + field + '=' + data[field] : url + '?' + field + '=' + data[field];
                return url;
            },
            formatCols: function (cols, init) {
                for (i in cols) {
                    var col = cols[i];
                    for (index in col) {
                        var val = col[index];

                        // 判断是否包含初始化数据
                        if (val.init === undefined) {
                            cols[i][index]['init'] = init;
                        }

                        // 格式化列操作栏
                        if (val.templet === admin.table.tool && val.operat === undefined) {
                            cols[i][index]['operat'] = ['edit', 'delete'];
                        }

                        // 判断是否包含开关组件
                        if (val.templet === admin.table.switch && val.filter === undefined) {
                            cols[i][index]['filter'] = val.field;
                        }

                        // 判断是否含有搜索下拉列表
                        if (val.selectList !== undefined && val.search === undefined) {
                            cols[i][index]['search'] = 'select';
                        }

                        // 判断是否初始化对齐方式
                        if (val.align === undefined) {
                            cols[i][index]['align'] = 'center';
                        }

                        // 部分字段开启排序
                        var sortDefaultFields = ['id', 'sort'];
                        if (val.sort === undefined && sortDefaultFields.indexOf(val.field) >= 0) {
                            cols[i][index]['sort'] = true;
                        }

                        // 初始化图片高度
                        if (val.templet === admin.table.image && val.imageHeight === undefined) {
                            cols[i][index]['imageHeight'] = 40;
                        }

                        // 判断是否多层对象
                        if (val.field !== undefined && val.field.split(".").length > 1) {
                            if (val.templet === undefined) {
                                cols[i][index]['templet'] = admin.table.value;
                            }
                        }

                        // 判断是否列表数据转换
                        if (val.selectList !== undefined && val.templet === undefined) {
                            cols[i][index]['templet'] = admin.table.list;
                        }

                    }
                }
                return cols;
            },
            tool: function (data, option) {
                option.operat = option.operat || ['edit', 'delete'];
                var elem = option.init.table_elem || init.table_elem;
                var html = '';
                $.each(option.operat, function (i, item) {
                    if (typeof item === 'string') {
                        switch (item) {
                            case 'edit':
                                var operat = {
                                    class: 'layui-btn layui-btn-success layui-btn-xs',
                                    method: 'open',
                                    field: 'id',
                                    icon: '',
                                    text: '编辑',
                                    title: '编辑信息',
                                    auth: 'edit',
                                    url: option.init.edit_url,
                                    extend: "",
                                    tableId: option.init.table_render_id
                                };
                                operat.url = admin.table.toolSpliceUrl(operat.url, operat.field, data);
                                if (admin.checkAuth(operat.auth, elem)) {
                                    html += admin.table.buildOperatHtml(operat);
                                }
                                break;
                            case 'delete':
                                var operat = {
                                    class: 'layui-btn layui-btn-danger layui-btn-xs',
                                    method: 'get',
                                    field: 'id',
                                    icon: '',
                                    text: '删除',
                                    title: '确定删除？',
                                    auth: 'delete',
                                    url: option.init.delete_url,
                                    extend: "",
                                    tableId: option.init.table_render_id
                                };
                                operat.url = admin.table.toolSpliceUrl(operat.url, operat.field, data);
                                if (admin.checkAuth(operat.auth, elem)) {
                                    html += admin.table.buildOperatHtml(operat);
                                }
                                break;
                        }

                    } else if (typeof item === 'object') {
                        $.each(item, function (i, operat) {
                            operat.class = operat.class || '';
                            operat.icon = operat.icon || '';
                            operat.auth = operat.auth || '';
                            operat.url = operat.url || '';
                            operat.method = operat.method || 'open';
                            operat.field = operat.field || 'id';
                            operat.title = operat.title || operat.text;
                            operat.text = operat.text || operat.title;
                            operat.extend = operat.extend || '';
                            operat.tableId = operat.tableId || '';
                            operat.url = admin.table.toolSpliceUrl(operat.url, operat.field, data);
                            if (admin.checkAuth(operat.auth, elem)) {
                                html += admin.table.buildOperatHtml(operat);
                            }
                        });
                    }
                });
                return html;
            },
            list: function (data, option) {
                option.selectList = option.selectList || {};
                var field = option.field;
                try {
                    var value = eval("data." + field);
                } catch (e) {
                    var value = undefined;
                }
                if (option.selectList[value] === undefined || option.selectList[value] === '' || option.selectList[value] === null) {
                    return value;
                } else {
                    return option.selectList[value];
                }
            },
            image: function (data, option) {
                option.imageWidth = option.imageWidth || 200;
                option.imageHeight = option.imageHeight || 40;
                option.imageSplit = option.imageSplit || '|';
                option.imageJoin = option.imageJoin || '<br>';
                option.title = option.title || option.field;
                var field = option.field,
                    title = data[option.title];
                try {
                    var value = eval("data." + field);
                } catch (e) {
                    var value = undefined;
                }
                if (value === undefined || value === null) {
                    return '<img style="max-width: ' + option.imageWidth + 'px; max-height: ' + option.imageHeight + 'px;" src="' + value + '" data-image="' + title + '">';
                } else {
                    var values = value.split(option.imageSplit),
                        valuesHtml = [];
                    values.forEach((value, index) => {
                        valuesHtml.push('<img style="max-width: ' + option.imageWidth + 'px; max-height: ' + option.imageHeight + 'px;" src="' + value + '" data-image="' + title + '">');
                    });
                    return valuesHtml.join(option.imageJoin);
                }
            },
            url: function (data, option) {
                var field = option.field;
                try {
                    var value = eval("data." + field);
                } catch (e) {
                    var value = undefined;
                }
                return '<a class="layuimini-table-url" href="' + value + '" target="_blank" class="label bg-green">' + value + '</a>';
            },
            switch: function (data, option) {
                var field = option.field;
                option.filter = option.filter || option.field || null;
                option.checked = option.checked || 1;
                option.tips = option.tips || '开|关';
                try {
                    var value = eval("data." + field);
                } catch (e) {
                    var value = undefined;
                }
                var checked = value === option.checked ? 'checked' : '';
                return laytpl('<input type="checkbox" name="' + option.field + '" value="' + data.id + '" lay-skin="switch" lay-text="' + option.tips + '" lay-filter="' + option.filter + '" ' + checked + ' >').render(data);
            },
            price: function (data, option) {
                var field = option.field;
                try {
                    var value = eval("data." + field);
                } catch (e) {
                    var value = undefined;
                }
                return '<span>￥' + value + '</span>';
            },
            percent: function (data, option) {
                var field = option.field;
                try {
                    var value = eval("data." + field);
                } catch (e) {
                    var value = undefined;
                }
                return '<span>' + value + '%</span>';
            },
            icon: function (data, option) {
                var field = option.field;
                try {
                    var value = eval("data." + field);
                } catch (e) {
                    var value = undefined;
                }
                return '<i class="' + value + '"></i>';
            },
            text: function (data, option) {
                var field = option.field;
                try {
                    var value = eval("data." + field);
                } catch (e) {
                    var value = undefined;
                }
                return '<span class="line-limit-length">' + value + '</span>';
            },
            value: function (data, option) {
                var field = option.field;
                try {
                    var value = eval("data." + field);
                } catch (e) {
                    var value = undefined;
                }
                return '<span>' + value + '</span>';
            },
            listenTableSearch: function (tableId) {
                form.on('submit(' + tableId + '_filter)', function (data) {
                    var dataField = data.field;
                    var formatFilter = {},
                        formatOp = {};
                    $.each(dataField, function (key, val) {
                        if (val !== '') {
                            formatFilter[key] = val;
                            var op = $('#c-' + key).attr('data-search-op');
                            op = op || '%*%';
                            formatOp[key] = op;
                        }
                    });
                    exportSearchValue = JSON.stringify(formatFilter);
                    exportSearchType = JSON.stringify(formatOp);
                    table.reload(tableId, {
                        page: {
                            curr: 1
                        }
                        , where: {
                            filter: JSON.stringify(formatFilter),
                            op: JSON.stringify(formatOp)
                        }
                    }, 'data');
                    return false;
                });
            },
            listenSwitch: function (option, ok) {
                option.filter = option.filter || '';
                option.url = option.url || '';
                option.field = option.field || option.filter || '';
                option.tableId = option.tableId || init.table_render_id;
                option.modifyReload = option.modifyReload || false;
                form.on('switch(' + option.filter + ')', function (obj) {
                    var checked = obj.elem.checked ? 1 : 0;
                    if (typeof ok === 'function') {
                        return ok({
                            id: obj.value,
                            checked: checked,
                        });
                    } else {
                        var data = {
                            id: obj.value,
                            field: option.field,
                            value: checked,
                        };
                        admin.request.post({
                            url: option.url,
                            prefix: true,
                            data: data,
                        }, function (res) {
                            if (option.modifyReload) {
                                table.reload(option.tableId);
                            }
                        }, function (res) {
                            admin.msg.error(res.msg, function () {
                                table.reload(option.tableId);
                            });
                        }, function () {
                            table.reload(option.tableId);
                        });
                    }
                });
            },
            listenToolbar: function (layFilter, tableId, contents) {
                table.on('toolbar(' + layFilter + ')', function (obj) {
                    // 搜索表单的显示
                    switch (obj.event) {
                        case 'TABLE_SEARCH':
                            var searchFieldsetId = 'searchFieldset_' + tableId;
                            var _that = $("#" + searchFieldsetId);
                            if (_that.hasClass("layui-hide")) {
                                _that.removeClass('layui-hide');
                            } else {
                                _that.addClass('layui-hide');
                            }
                            break;

                        case 'random_name':
                            var checkStatus = table.checkStatus(tableId),
                                all_data = checkStatus.data;
                            if (all_data.length ==0){
                                layer.msg('请勾选需要修改的数据',{icon:2})
                                return
                            }
                            layer.open({
                                skin: 'demo-class',
                                title:'随机姓名',
                                content: '生成哪种性别的名字？'
                                ,btn: ['男', '女', '取消']
                                ,yes: function(index, layero){
                                    //获取table所有name数据

                                    // var all_data = layui.table.cache[tableId]


                                    var update_array = []
                                    for(let i in all_data){
                                        let nan_name  = get_nan_name()
                                        update_array.push({'id':all_data[i]['id'],'comment_user':nan_name})
                                        all_data[i]['comment_user'] = nan_name
                                    }
                                    admin.request.post({
                                        data:{
                                            'data':JSON.stringify(update_array),
                                            'type':'name',
                                        },
                                        url:'/admin/comment_center.show_comment/batch_edit_arrt'
                                    },function (res) {
                                        layui.table.reload(tableId)
                                    })

                                }
                                ,btn2: function(index, layero){

                                    var update_array = []
                                    for(let i in all_data){
                                        let name  = get_nv_name()
                                        update_array.push({'id':all_data[i]['id'],'comment_user':name})
                                        all_data[i]['comment_user'] = name
                                    }
                                    admin.request.post({
                                        data:{
                                            'data':JSON.stringify(update_array),
                                            'type':'name',
                                        },
                                        url:'/admin/comment_center.show_comment/batch_edit_arrt'
                                    },function (res) {
                                        layui.table.reload(tableId)
                                    })

                                },btn3:function (index,layero) {


                                }

                            });
                            break;

                        //同义替换
                        case 'change_word':
                            var checkStatus = table.checkStatus(tableId),
                                all_data = checkStatus.data;
                            if (all_data.length ==0){
                                layer.msg('请勾选需要修改的数据',{icon:2})
                                return
                            }
                            var update_array = []
                            var success_array = []
                            var cont = ''
                            var index = admin.msg.loading('加载中');
                            for(let i in all_data){
                                cont = all_data[i]['content']
                                admin.request.post({
                                    show_loading:false,
                                    url:'https://translation.maiyuan.online/translation',
                                    data:{"word": cont, "from": "英语","to":"中文","is_title":"0"}
                                },function (res){
                                    let data = res.data.translateResult
                                    admin.request.post({
                                        show_loading:false,
                                        url:'https://translation.maiyuan.online/translation',
                                        data:{"word": data, "from": "中文","to":"英语","is_title":"0"}
                                    },function (resp) {
                                        success_array.push(resp)
                                        console.log(resp)
                                        let new_cont = resp.data.translateResult
                                        update_array.push({'id':all_data[i]['id'],'content':new_cont,'old_content':cont})
                                        //如果全部请求完毕  修改所有内容
                                        if (all_data.length == success_array.length){
                                            admin.request.post({
                                                show_loading:false,
                                                data:{
                                                    'data':JSON.stringify(update_array),
                                                    'type':'change_word',
                                                },
                                                url:'/admin/comment_center.show_comment/batch_edit_arrt'
                                            },function (res) {
                                                layui.table.reload(tableId)
                                                admin.msg.close(index);
                                            })
                                        }
                                    })
                                })

                            }
                            break;


                        case 'batch_edit':
                            layer.open({
                                skin: 'demo-class',
                                title:'批量修改',
                                area: ['600px', '400px'],
                                btn: ['提交', '取消'],
                                content:'<textarea rows="10" id="batch_detail" name="detail" placeholder="LazyShop ID,交接日期,批次号\n如：07ab5dba-f05b-4573-8264-fd53d5c3d26c,2022-08-23,你的批次(多个请换行)" class="layui-textarea"></textarea>',
                                yes:function (index,layero) {
                                    var batch_detail = $('#batch_detail').val()
                                    admin.request.post({
                                        url:'batch_edit',
                                        data:{
                                            data:batch_detail,
                                        }
                                    },function (resp) {
                                        console.log(resp)
                                        var success_str = '成功：';
                                        var error_str = '失败：';
                                        if (resp.data.success.length != 0){
                                            for (let i in resp.data.success){
                                                success_str += resp.data.success[i]+'<br>'
                                            }


                                        }
                                        success_str += '<br>'
                                        if (resp.data.error.length != 0){
                                            for (let i in resp.data.error){
                                                error_str += resp.data.error[i]+'<br>'
                                            }
                                        }
                                        layer.open({
                                            skin: 'demo-class',
                                            title:'返回内容',
                                            content: success_str+error_str
                                        })

                                    })
                                    return false

                                },btn2: function(index, layero){
                                    //按钮【按钮二】的回调

                                    //return false 开启该代码可禁止点击该按钮关闭
                                }
                                
                            })

                        default:
                            var text = '';
                            contents.forEach((item, index) => {
                                if (item.layEvent == obj.event) {
                                    text = item.content;
                                    layer.open({
                                        type: 0
                                        , title: '信息'
                                        , area: ['300px']
                                        , anim: 0
                                        , id: 'layerDemoauto' //防止重复弹出
                                        , content: text
                                        , btn: '关闭'
                                        , btnAlign: 'c' //按钮居中
                                        , yes: function () {
                                            layer.closeAll();
                                        }
                                    });
                                }
                            })
                            break;
                    }
                });
            },
            listenEdit: function (tableInit, layFilter, tableId, modifyReload) {
                tableInit.modify_url = tableInit.modify_url || false;
                tableId = tableId || init.table_render_id;
                if (tableInit.modify_url !== false) {
                    table.on('edit(' + layFilter + ')', function (obj) {
                        var value = obj.value,
                            data = obj.data,
                            id = data.id,
                            field = obj.field;
                        var _data = {
                            id: id,
                            field: field,
                            value: value,
                        };
                        admin.request.post({
                            url: tableInit.modify_url,
                            prefix: true,
                            data: _data,
                        }, function (res) {
                            if (modifyReload) {
                                table.reload(tableId);
                            }
                        }, function (res) {
                            admin.msg.error(res.msg, function () {
                                table.reload(tableId);
                            });
                        }, function () {
                            table.reload(tableId);
                        });
                    });
                }
            },
            listenSort: function (tableInit, layFilter, tableId, modifyReload) {
                tableId = tableId || init.table_render_id;
                table.on('sort('+layFilter+')', function(obj){ //注：tool是工具条事件名，test是table原始容器的属性 lay-filter="对应的值"
                    table.reload(tableId, {
                        initSort: obj //记录初始排序，如果不设的话，将无法标记表头的排序状态。 layui 2.1.1 新增参数
                        ,where: { //请求参数（注意：这里面的参数可任意定义，并非下面固定的格式）
                            field: obj.field //排序字段   在接口作为参数字段  field order
                            ,order: obj.type //排序方式   在接口作为参数字段  field order
                        }
                    });
                });
            },
        },
        checkMobile: function () {
            var userAgentInfo = navigator.userAgent;
            var mobileAgents = ["Android", "iPhone", "SymbianOS", "Windows Phone", "iPad", "iPod"];
            var mobile_flag = false;
            //根据userAgent判断是否是手机
            for (var v = 0; v < mobileAgents.length; v++) {
                if (userAgentInfo.indexOf(mobileAgents[v]) > 0) {
                    mobile_flag = true;
                    break;
                }
            }
            var screen_width = window.screen.width;
            var screen_height = window.screen.height;
            //根据屏幕分辨率判断是否是手机
            if (screen_width < 600 && screen_height < 800) {
                mobile_flag = true;
            }
            return mobile_flag;
        },
        open: function (title, url, width, height, isResize) {
            isResize = isResize === undefined ? true : isResize;
            var index = layer.open({
                title: title,
                type: 2,
                area: [width, height],
                content: url,
                maxmin: true,
                moveOut: true,
                success: function (layero, index) {
                    var body = layer.getChildFrame('body', index);
                    if (body.length > 0) {
                        $.each(body, function (i, v) {

                            // todo 优化弹出层背景色修改
                            $(v).before('<style>\n' +
                                'html, body {\n' +
                                '    background: #ffffff;\n' +
                                '}\n' +
                                '</style>');
                        });
                    }
                }
            });
            if (admin.checkMobile() || width === undefined || height === undefined) {
                layer.full(index);
            }
            if (isResize) {
                $(window).on("resize", function () {
                    layer.full(index);
                })
            }
        },

        //标签
        tags: function (title, url, width, height, isResize) {
            // console.log(2)
            // var tableId = $(this).attr('data-table-export')
            // console.log(tableId)
            return
            // isResize = isResize === undefined ? true : isResize;
            // var index = layer.open({
            //     title: title,
            //     type: 2,
            //     area: [width, height],
            //     content: url,
            //     maxmin: true,
            //     moveOut: true,
            //     success: function (layero, index) {
            //         var body = layer.getChildFrame('body', index);
            //         if (body.length > 0) {
            //             $.each(body, function (i, v) {
            //
            //                 // todo 优化弹出层背景色修改
            //                 $(v).before('<style>\n' +
            //                     'html, body {\n' +
            //                     '    background: #ffffff;\n' +
            //                     '}\n' +
            //                     '</style>');
            //             });
            //         }
            //     }
            // });
            // if (admin.checkMobile() || width === undefined || height === undefined) {
            //     layer.full(index);
            // }
            // if (isResize) {
            //     $(window).on("resize", function () {
            //         layer.full(index);
            //     })
            // }
        },

        getSelectList: function (table, field = 'name') { // 搜索框条件监听-shine
            var mydata;
            $.ajax({
                url: '/admin/Ajax/source',
                data: { 'table': table, 'field': field },
                type: 'post',
                contentType: "application/x-www-form-urlencoded; charset=UTF-8",
                async: false,
                dataType: 'json',
                success: function (result) {
                    mydata = result;
                }
            });
            return mydata;
        },

        getUserSelectList: function () {
            var mydata;
            $.ajax({
                url: '/admin/Ajax/userList',
                type: 'get',
                contentType: "application/x-www-form-urlencoded; charset=UTF-8",
                async: false,
                dataType: 'json',
                success: function (result) {
                    mydata = result;
                }
            });
            return mydata;
        },

        //获取分组树结构数据
        getGroupTree: function () {
            var mydata;
            $.ajax({
                url: '/admin/Ajax/getGroupTree',
                type: 'get',
                contentType: "application/x-www-form-urlencoded; charset=UTF-8",
                async: false,
                dataType: 'json',
                success: function (result) {
                    mydata = result;
                }
            });
            return mydata;
        },


        //获取评论筛选select    type 为 关键词 店铺 集合 产品
        getCommentSelectList: function (type) {
            var mydata;
            $.ajax({
                url: '/admin/Ajax/getCommentSelectList',
                type: 'post',
                contentType: "application/x-www-form-urlencoded; charset=UTF-8",
                async: false,
                data: { 'type': type },

                dataType: 'json',
                success: function (result) {
                    mydata = result;
                }
            });
            return mydata;
        },


        getUserSelectListAll: function () {
            var mydata;
            $.ajax({
                url: '/admin/Ajax/getUserSelectListAll',
                type: 'get',
                contentType: "application/x-www-form-urlencoded; charset=UTF-8",
                async: false,
                dataType: 'json',
                success: function (result) {
                    mydata = result;
                }
            });
            return mydata;
        },
        //获取店铺内部分类 筛选用
        getShopCategorySelect: function (id,type='keyword',is_add) {
            var mydata;
            if (type=='keyword'){
                var url = '/admin/Ajax/getShopCategorySelect?id='+id+'&type='+type+'&is_add='+is_add
            }else{
                var url = '/admin/Ajax/getShopCategorySelect?id='+id+'&is_add='+is_add
            }

            $.ajax({
                url: url,
                type: 'get',
                contentType: "application/x-www-form-urlencoded; charset=UTF-8",
                async: false,
                dataType: 'json',
                success: function (result) {
                    mydata = result;
                }
            });
            return mydata;
        },
        listen: function (preposeCallback, ok, no, ex) {

            // 监听表单是否为必填项
            admin.api.formRequired();

            // 监听表单提交事件
            admin.api.formSubmit(preposeCallback, ok, no, ex);

            // 初始化图片显示以及监听上传事件
            admin.api.upload();

            // 监听富文本初始化
            admin.api.editor();

            // 监听下拉选择生成
            admin.api.select();

            // 监听时间控件生成
            admin.api.date();

            // 初始化layui表单
            form.render();

            // 表格修改
            $("body").on("mouseenter", ".table-edit-tips", function () {
                var openTips = layer.tips('点击行内容可以进行修改', $(this), { tips: [2, '#fc7b6e'], time: 4000 });
            });

            // 监听弹出层的打开
            $('body').on('click', '[data-open]', function () {
                var clienWidth = $(this).attr('data-width'),
                    clientHeight = $(this).attr('data-height'),
                    dataFull = $(this).attr('data-full'),
                    checkbox = $(this).attr('data-checkbox'),
                    url = $(this).attr('data-open'),
                    tableId = $(this).attr('data-table');
                if (checkbox === 'true') {
                    tableId = tableId || init.table_render_id;
                    var checkStatus = table.checkStatus(tableId),
                        data = checkStatus.data;
                    if (data.length <= 0) {
                        admin.msg.error('请勾选需要操作的数据');
                        return false;
                    }
                    var ids = [];
                    $.each(data, function (i, v) {
                        ids.push(v.id);
                    });
                    if (url.indexOf("?") === -1) {
                        url += '?id=' + ids.join(',');
                    } else {
                        url += '&id=' + ids.join(',');
                    }
                }
                if (clienWidth === undefined || clientHeight === undefined) {
                    var width = document.body.clientWidth,
                        height = document.body.clientHeight;
                    if (width >= 800 && height >= 600) {
                        clienWidth = '800px';
                        clientHeight = '600px';
                    } else {
                        clienWidth = '100%';
                        clientHeight = '100%';
                    }
                }
                if (dataFull === 'true') {
                    clienWidth = '100%';
                    clientHeight = '100%';
                }

                admin.open(
                    $(this).attr('data-title'),
                    admin.url(url),
                    clienWidth,
                    clientHeight,
                );
            });

            // 放大图片
            $('body').on('click', '[data-image]', function () {
                var title = $(this).attr('data-image'),
                    src = $(this).attr('src'),
                    alt = $(this).attr('alt');
                var photos = {
                    "title": title,
                    "id": Math.random(),
                    "data": [
                        {
                            "alt": alt,
                            "pid": Math.random(),
                            "src": src,
                            "thumb": src
                        }
                    ]
                };
                layer.photos({
                    photos: photos,
                    anim: 5
                });
                return false;
            });

            // 放大一组图片
            $('body').on('click', '[data-images]', function () {
                var title = $(this).attr('data-images'),
                    // 从当前元素向上找layuimini-upload-show找到第一个后停止, 再找其所有子元素li
                    doms = $(this).closest(".layuimini-upload-show").children("li"),
                    // 被点击的图片地址
                    now_src = $(this).attr('src'),
                    alt = $(this).attr('alt'),
                    data = [];
                $.each(doms, function (key, value) {
                    var src = $(value).find('img').attr('src');
                    if (src != now_src) {
                        // 压入其他图片地址
                        data.push({
                            "alt": alt,
                            "pid": Math.random(),
                            "src": src,
                            "thumb": src
                        });
                    } else {
                        // 把当前图片插入到头部
                        data.unshift({
                            "alt": alt,
                            "pid": Math.random(),
                            "src": now_src,
                            "thumb": now_src
                        });
                    }
                });
                var photos = {
                    "title": title,
                    "id": Math.random(),
                    "data": data,
                };
                layer.photos({
                    photos: photos,
                    anim: 5
                });
                return false;
            });


            // 监听动态表格刷新
            $('body').on('click', '[data-table-refresh]', function () {
                var tableId = $(this).attr('data-table-refresh');
                if (tableId === undefined || tableId === '' || tableId == null) {
                    tableId = init.table_render_id;
                }
                table.reload(tableId);
            });

            // 监听搜索表格重置
            $('body').on('click', '[data-table-reset]', function () {
                var tableId = $(this).attr('data-table-reset');
                console.log(tableId,'重置表单')
                batch_xmSelect ? batch_xmSelect.setValue([ ]):0
                group_xmSelect? group_xmSelect.setValue([ ]):0
                if (tableId === undefined || tableId === '' || tableId == null) {
                    tableId = init.table_render_id;
                }
                table.reload(tableId, {
                    page: {
                        curr: 1
                    }
                    , where: {
                        filter: '{}',
                        op: '{}'
                    }
                }, 'data');
            });

            //跳转下载页面
            $('body').on('click', '[down_load_page]', function (){
                // 跳转下载页面
                var url = $(this).attr('down_load_page')
                var title = $(this).attr('data-title')

                layer.open({
                    type: 1,
                    skin: 'demo-class',
                    title:title,
                    btnAlign: 'c',
                    btn: ['确认', '取消'],
                    yes: function(index, layero){
                        window.open(url)
                        layer.close(index)
                    }
                });
                return false
            })

            // 监听请求
            $('body').on('click', '[data-request]', function () {
                var title = $(this).attr('data-title'),
                    url = $(this).attr('data-request'),
                    tableId = $(this).attr('data-table'),
                    addons = $(this).attr('data-addons'),
                    checkbox = $(this).attr('data-checkbox'),
                    direct = $(this).attr('data-direct'),
                    field = $(this).attr('data-field') || 'id';

                title = title || '确定进行该操作？';

                if (direct === 'true') {
                    admin.msg.confirm(title, function () {
                        window.location.href = url;
                    });
                    return false;
                }

                var postData = {};
                if (checkbox === 'true') {
                    tableId = tableId || init.table_render_id;
                    var checkStatus = table.checkStatus(tableId),
                        data = checkStatus.data;
                    if (data.length <= 0) {
                        admin.msg.error('请勾选需要操作的数据');
                        return false;
                    }
                    var ids = [];
                    $.each(data, function (i, v) {
                        ids.push(v[field]);
                    });
                    postData[field] = ids;
                }

                if (addons !== true && addons !== 'true') {
                    url = admin.url(url);
                }
                tableId = tableId || init.table_render_id;
                admin.msg.confirm(title, function () {
                    admin.request.post({
                        url: url,
                        data: postData,
                    }, function (res) {
                        admin.msg.success(res.msg, function () {
                            table.reload(tableId);
                        });
                    })
                });
                return false;
            });

            // excel导出（原方法做出修改-shine）
            $('body').on('click', '[data-table-export]', function () {
                var tableId = $(this).attr('data-table-export'),
                    url = $(this).attr('data-url');
                // 修改后-shine
                var index = admin.msg.confirm('根据查询进行导出，确定导出？', function () {
                    tableId = tableId || init.table_render_id;
                    var checkStatus = table.checkStatus(tableId),
                        data = checkStatus.data;
                    var ids = [];
                    $.each(data, function (i, v) {
                        ids.push(v.id);
                    });
                    if ((url.indexOf("?") != -1)) {
                        window.location = admin.url(url + '&id=' + JSON.stringify(ids) + '&exportSearchValue=' + exportSearchValue + '&exportSearchType=' + exportSearchType);
                    } else {
                        window.location = admin.url(url + '?id=' + JSON.stringify(ids) + '&exportSearchValue=' + exportSearchValue + '&exportSearchType=' + exportSearchType);
                    }
                    layer.close(index);
                });

                // 修改前-shine
                // var index = admin.msg.confirm('根据查询进行导出，确定导出？', function () {
                //     tableId = tableId || init.table_render_id;
                //     var checkStatus = table.checkStatus(tableId),
                //         data = checkStatus.data;
                //     window.location = admin.url(url);
                //     layer.close(index);
                // });
            });

            // 数据表格多删除
            $('body').on('click', '[data-table-delete]', function () {
                var tableId = $(this).attr('data-table-delete'),
                    url = $(this).attr('data-url');
                tableId = tableId || init.table_render_id;
                url = url !== undefined ? admin.url(url) : window.location.href;
                var checkStatus = table.checkStatus(tableId),
                    data = checkStatus.data;
                if (data.length <= 0) {
                    admin.msg.error('请勾选需要删除的数据');
                    return false;
                }
                var ids = [];
                $.each(data, function (i, v) {
                    ids.push(v.id);
                });
                admin.msg.confirm('确定删除？', function () {
                    admin.request.post({
                        url: url,
                        data: {
                            id: ids
                        },
                    }, function (res) {
                        admin.msg.success(res.msg, function () {
                            table.reload(tableId);
                        });
                    });
                });
                return false;
            });

            //打开网页
            $('body').on('click', '[data-table-openweb]', function ()  {
                var tableId = $(this).attr('data-table-openweb'),
                    url = $(this).attr('data-url');
                tableId = tableId || init.table_render_id;
                // return
                var checkStatus = table.checkStatus(tableId),
                    data = checkStatus.data;
                if (data.length <= 0) {
                    admin.msg.error('请勾选需要开打的网页');
                    return false;
                }
                var urls = [];
                var ids = [];
                $.each(data, function (i, v) {
                    urls.push(v.url);
                    ids.push(v.id);

                });
                // return
                $.each(urls, function (i, url) {
                    var frame = window.open("about:blank", "_blank");
                    frame.location = url;
                });
                // admin.request.post({
                //     url: url,
                //     data: {
                //         id: ids
                //     },
                // }, function (res) {
                //
                // });

            });

            //批量导出
            $('body').on('click', '[data-table-batch_export]', function ()  {
                var tableId = $(this).attr('data-table-batch_export'),
                    url = $(this).attr('data-url');
                tableId = tableId || init.table_render_id;
                url = url !== undefined ? admin.url(url) : window.location.href;
                var checkStatus = table.checkStatus(tableId),
                    data = checkStatus.data;
                if (data.length <= 0) {
                    admin.msg.error('请勾选需要操作的数据');
                    return false;
                }
                var ids = [];

                $.each(data, function (i, v) {
                    ids.push(v.id)
                });
                if (ids.length == 0){
                    return
                }
                layer.open({
                    type: 1,
                    skin: 'demo-class',
                    title:'是否导出所选择的选项？',
                    btnAlign: 'c',
                    btn: ['确认', '取消'],
                    yes: function(index, layero){
                        window.open(url+'?id='+JSON.stringify(ids))
                        layer.close(index)
                    }
                });
                return false

            });

            //批量对接
            $('body').on('click', '[data-table-batch_connect]', function ()  {
                var tableId = $(this).attr('data-table-batch_connect'),
                    url = $(this).attr('data-url');
                tableId = tableId || init.table_render_id;
                url = url !== undefined ? admin.url(url) : window.location.href;
                var checkStatus = table.checkStatus(tableId),
                    data = checkStatus.data;
                if (data.length <= 0) {
                    admin.msg.error('请勾选需要操作的数据');
                    return false;
                }
                var ids = [];
                $.each(data, function (i, v) {
                    ids.push(v.id);
                });

                layer.confirm('请确认该选项内的集合是否需要SEO审核？（谨慎操作）', {
                    btn : ['需要SEO审核','不需要SEO审核','取消'],

                    btn1:function(){
                        admin.request.post({
                            url: url,
                            data: {
                                id: ids,
                                is_connect:1
                            },
                        }, function (res) {
                            admin.msg.success(res.msg, function () {
                                table.reload(tableId);
                            });
                        });

                        // alert('yes');
                        layer.closeAll('dialog')
                        // return
                    },
                    btn2:function(){
                        admin.request.post({
                            url: url,
                            data: {
                                id: ids,
                                is_connect:0
                            },
                        }, function (res) {
                            admin.msg.success(res.msg, function () {
                                table.reload(tableId);
                            });
                        });
                    },
                    btn3:function(){
                        return;
                    }
                });

            });

            //SEO 批量完成
            $('body').on('click', '[data-table-seo_finish]', function () {
                var tableId = $(this).attr('data-table-seo_finish'),
                    url = $(this).attr('data-url');
                var checkStatus = table.checkStatus(tableId),
                    data = checkStatus.data;
                if (data.length <= 0) {
                    admin.msg.error('请勾选需要确定完成的选项');
                    return false;
                }
                var ids = [];
                $.each(data, function (i, v) {
                    ids.push(v.id);

                });
                admin.msg.confirm('请确定该选项内所有审核工作已完成！一旦确认无法更改，请谨慎操作！', function () {
                    admin.request.post({
                        url: url,
                        data: {
                            id: ids
                        },
                    }, function (res) {
                        admin.msg.success(res.msg, function () {
                            table.reload(tableId);
                        });
                    });
                });
                return false;

            });



        },
        api: {
            form: function (url, data, ok, no, ex, refreshTable) {
                if (refreshTable === undefined) {
                    refreshTable = true;
                }
                ok = ok || function (res) {
                    res.msg = res.msg || '';
                    admin.msg.success(res.msg, function () {
                        admin.api.closeCurrentOpen({
                            refreshTable: refreshTable
                        });
                    });
                    return false;
                };
                admin.request.post({
                    url: url,
                    data: data,
                }, ok, no, ex);
                return false;
            },
            closeCurrentOpen: function (option) {
                option = option || {};
                option.refreshTable = option.refreshTable || false;
                option.refreshFrame = option.refreshFrame || false;
                if (option.refreshTable === true) {
                    option.refreshTable = init.table_render_id;
                }
                var index = parent.layer.getFrameIndex(window.name);
                parent.layer.close(index);
                if (option.refreshTable !== false) {
                    var refreshTableList = option.refreshTable.split('|')
                    for (t of refreshTableList){
                        parent.layui.table.reload(t);
                    }

                    // parent.layui.table.reload(option.refreshTable);
                }
                if (option.refreshFrame) {
                    parent.location.reload();
                }
                return false;
            },
            refreshFrame: function () {
                parent.location.reload();
                return false;
            },
            refreshTable: function (tableName) {
                tableName = tableName || 'currentTable';
                table.reload(tableName);
            },
            formRequired: function () {
                var verifyList = document.querySelectorAll("[lay-verify]");
                if (verifyList.length > 0) {
                    $.each(verifyList, function (i, v) {
                        var verify = $(this).attr('lay-verify');

                        // todo 必填项处理
                        if (verify === 'required') {
                            var label = $(this).parent().prev();
                            if (label.is('label') && !label.hasClass('required')) {
                                label.addClass('required');
                            }
                            if ($(this).attr('lay-reqtext') === undefined && $(this).attr('placeholder') !== undefined) {
                                $(this).attr('lay-reqtext', $(this).attr('placeholder'));
                            }
                            if ($(this).attr('placeholder') === undefined && $(this).attr('lay-reqtext') !== undefined) {
                                $(this).attr('placeholder', $(this).attr('lay-reqtext'));
                            }
                        }

                    });
                }
            },
            formSubmit: function (preposeCallback, ok, no, ex) {
                var formList = document.querySelectorAll("[lay-submit]");
                // 表单提交自动处理
                if (formList.length > 0) {
                    $.each(formList, function (i, v) {
                        var filter = $(this).attr('lay-filter'),
                            type = $(this).attr('data-type'),
                            refresh = $(this).attr('data-refresh'),
                            url = $(this).attr('lay-submit');
                        if ($(this).attr('lay-table-render-id') !== undefined) {
                            init.table_render_id = $(this).attr('lay-table-render-id');
                        }

                        // 表格搜索不做自动提交
                        if (type === 'tableSearch') {
                            return false;
                        }
                        // 判断是否需要刷新表格
                        if (refresh === 'false') {
                            refresh = false;
                        } else {
                            refresh = true;
                        }
                        // 自动添加layui事件过滤器
                        if (filter === undefined || filter === '') {
                            filter = 'save_form_' + (i + 1);
                            $(this).attr('lay-filter', filter)
                        }
                        if (url === undefined || url === '' || url === null) {
                            url = window.location.href;
                        } else {
                            url = admin.url(url);
                        }
                        form.on('submit(' + filter + ')', function (data) {
                            var dataField = data.field;

                            // 富文本数据处理
                            var editorList = document.querySelectorAll(".editor");
                            if (editorList.length > 0) {
                                $.each(editorList, function (i, v) {
                                    var name = $(this).attr("name");
                                    dataField[name] = CKEDITOR.instances[name].getData();
                                });
                            }

                            if (typeof preposeCallback === 'function') {
                                dataField = preposeCallback(dataField);
                            }
                            admin.api.form(url, dataField, ok, no, ex, refresh);

                            return false;
                        });
                    });
                }

            },
            upload: function () {
                var uploadList = document.querySelectorAll("[data-upload]");
                var uploadSelectList = document.querySelectorAll("[data-upload-select]");

                if (uploadList.length > 0) {
                    $.each(uploadList, function (i, v) {
                        var exts = $(this).attr('data-upload-exts'),
                            uploadName = $(this).attr('data-upload'),
                            uploadNumber = $(this).attr('data-upload-number'),
                            uploadSign = $(this).attr('data-upload-sign');
                        exts = exts || init.upload_exts;
                        uploadNumber = uploadNumber || 'one';
                        uploadSign = uploadSign || '|';
                        var elem = "input[name='" + uploadName + "']",
                            uploadElem = this;

                        // 监听上传事件
                        upload.render({
                            elem: this,
                            url: admin.url(init.upload_url),
                            accept: 'file',
                            exts: exts,
                            // 让多图上传模式下支持多选操作
                            multiple: (uploadNumber !== 'one') ? true : false,
                            done: function (res) {
                                if (res.code === 1) {
                                    var url = res.data.url;
                                    if (uploadNumber !== 'one') {
                                        var oldUrl = $(elem).val();
                                        if (oldUrl !== '') {
                                            url = oldUrl + uploadSign + url;
                                        }
                                    }
                                    $(elem).val(url);
                                    $(elem).trigger("input");
                                    admin.msg.success(res.msg);
                                } else {
                                    admin.msg.error(res.msg);
                                }
                                return false;
                            }
                        });

                        // 监听上传input值变化
                        $(elem).bind("input propertychange", function (event) {
                            var urlString = $(this).val(),
                                urlArray = urlString.split(uploadSign),
                                uploadIcon = $(uploadElem).attr('data-upload-icon');
                            uploadIcon = uploadIcon || "file";

                            $('#bing-' + uploadName).remove();
                            if (urlString.length > 0) {
                                var parant = $(this).parent('div');
                                var liHtml = '';
                                $.each(urlArray, function (i, v) {
                                    liHtml += '<li><a><img src="' + v + '" data-image  onerror="this.src=\'' + BASE_URL + 'admin/images/upload-icons/' + uploadIcon + '.png\';this.onerror=null"></a><small class="uploads-delete-tip bg-red badge" data-upload-delete="' + uploadName + '" data-upload-url="' + v + '" data-upload-sign="' + uploadSign + '">×</small></li>\n';
                                });
                                parant.after('<ul id="bing-' + uploadName + '" class="layui-input-block layuimini-upload-show">\n' + liHtml + '</ul>');
                            }

                        });

                        // 非空初始化图片显示
                        if ($(elem).val() !== '') {
                            $(elem).trigger("input");
                        }
                    });

                    // 监听上传文件的删除事件
                    $('body').on('click', '[data-upload-delete]', function () {
                        var uploadName = $(this).attr('data-upload-delete'),
                            deleteUrl = $(this).attr('data-upload-url'),
                            sign = $(this).attr('data-upload-sign');
                        var confirm = admin.msg.confirm('确定删除？', function () {
                            var elem = "input[name='" + uploadName + "']";
                            var currentUrl = $(elem).val();
                            var url = '';
                            if (currentUrl !== deleteUrl) {
                                url = currentUrl.search(deleteUrl) === 0 ? currentUrl.replace(deleteUrl + sign, '') : currentUrl.replace(sign + deleteUrl, '');
                                $(elem).val(url);
                                $(elem).trigger("input");
                            } else {
                                $(elem).val(url);
                                $('#bing-' + uploadName).remove();
                            }
                            admin.msg.close(confirm);
                        });
                        return false;
                    });
                }

                if (uploadSelectList.length > 0) {
                    $.each(uploadSelectList, function (i, v) {
                        var exts = $(this).attr('data-upload-exts'),
                            uploadName = $(this).attr('data-upload-select'),
                            uploadNumber = $(this).attr('data-upload-number'),
                            uploadSign = $(this).attr('data-upload-sign');
                        exts = exts || init.upload_exts;
                        uploadNumber = uploadNumber || 'one';
                        uploadSign = uploadSign || '|';
                        var selectCheck = uploadNumber === 'one' ? 'radio' : 'checkbox';
                        var elem = "input[name='" + uploadName + "']",
                            uploadElem = $(this).attr('id');

                        tableSelect.render({
                            elem: "#" + uploadElem,
                            checkedKey: 'id',
                            searchType: 'more',
                            searchList: [
                                { searchKey: 'title', searchPlaceholder: '请输入文件名' },
                            ],
                            table: {
                                url: admin.url('ajax/getUploadFiles'),
                                cols: [[
                                    { type: selectCheck },
                                    { field: 'id', title: 'ID' },
                                    { field: 'url', minWidth: 80, search: false, title: '图片信息', imageHeight: 40, align: "center", templet: admin.table.image },
                                    { field: 'original_name', width: 150, title: '文件原名', align: "center" },
                                    { field: 'mime_type', width: 120, title: 'mime类型', align: "center" },
                                    { field: 'create_time', width: 200, title: '创建时间', align: "center", search: 'range' },
                                ]]
                            },
                            done: function (e, data) {
                                var urlArray = [];
                                $.each(data.data, function (index, val) {
                                    urlArray.push(val.url)
                                });
                                var url = urlArray.join(uploadSign);
                                admin.msg.success('选择成功', function () {
                                    $(elem).val(url);
                                    $(elem).trigger("input");
                                });
                            }
                        })

                    });

                }
            },
            editor: function () {
                var editorList = document.querySelectorAll(".editor");
                if (editorList.length > 0) {
                    $.each(editorList, function (i, v) {
                        CKEDITOR.replace(
                            $(this).attr("name"),
                            {
                                height: $(this).height(),
                                filebrowserImageUploadUrl: admin.url('ajax/uploadEditor'),
                            });
                    });
                }
            },
            select: function () {
                var selectList = document.querySelectorAll("[data-select]");
                $.each(selectList, function (i, v) {
                    var url = $(this).attr('data-select'),
                        selectFields = $(this).attr('data-fields'),
                        value = $(this).attr('data-value'),
                        that = this,
                        html = '<option value=""></option>';
                    var fields = selectFields.replace(/\s/g, "").split(',');
                    if (fields.length !== 2) {
                        return admin.msg.error('下拉选择字段有误');
                    }
                    admin.request.get(
                        {
                            url: url,
                            data: {
                                selectFields: selectFields
                            },
                        }, function (res) {
                            var list = res.data;
                            list.forEach(val => {
                                var key = val[fields[0]];
                                if (value !== undefined && key.toString() === value) {
                                    html += '<option value="' + key + '" selected="">' + val[fields[1]] + '</option>';
                                } else {
                                    html += '<option value="' + key + '">' + val[fields[1]] + '</option>';
                                }
                            });
                            $(that).html(html);
                            form.render();
                        }
                    );
                });
            },
            date: function () {
                var dateList = document.querySelectorAll("[data-date]");
                if (dateList.length > 0) {
                    $.each(dateList, function (i, v) {
                        var format = $(this).attr('data-date'),
                            type = $(this).attr('data-date-type'),
                            range = $(this).attr('data-date-range');
                        if (type === undefined || type === '' || type === null) {
                            type = 'datetime';
                        }
                        var options = {
                            elem: this,
                            type: type,
                        };
                        if (format !== undefined && format !== '' && format !== null) {
                            options['format'] = format;
                        }
                        if (range !== undefined) {
                            if (range === null || range === '') {
                                range = '-';
                            }
                            options['range'] = range;
                        }
                        laydate.render(options);
                    });
                }
            },
        },
        file: { // 新增excel表格上传处理-shine
            upload: function (options) {
                if (options.id == undefined) {
                    options.id = 'upload-file';
                    options.url = options.init.import_url;
                }
                var index;
                    //上传头像
                upload.render({
                    elem: '#' + options.id, //绑定元素
                    url: admin.url(options.url), //上传接口
                    data: { type: options.type ? options.type : 0 },
                    exts: 'xlsx|xls|csv',
                    before: function (obj) { //obj参数包含的信息，跟 choose回调完全一致，可参见上文。
                        // obj.preview(function(index, file, result){
                        //    $('.images').attr('src', result);
                        // });
                        index = admin.msg.loading('文件上传中，请勿关闭该页面！');
                    },
                    done: function (res) {
                        admin.msg.close(index);
                        if (res.status == 'SUCCESS') {
                            layer.msg(res.msg, { icon: 6, time: 1000 });
                        } else {
                            layer.msg(res.msg, { time: 20000, btn: ['知道了'] });
                        }
                    },
                    error: function (res) {
                        layer.msg('当前网络不佳,文件上传失败!', { icon: 5, time: 1000 });
                    }
                });
            },
        },
    };
    return admin;
});
