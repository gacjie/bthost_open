define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'clipboard.min'], function ($, undefined, Backend, Table, Form,Clipboard) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'general/queue/index',
                    // add_url: 'general/queue/add',
                    edit_url: 'general/queue/edit',
                    // del_url: 'general/queue/del',
                    multi_url: 'general/queue/multi',
                    detail_info:'general/queue/detail',
                    table: 'queue',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'weigh',
                showToggle: false,
                showColumns: false,
                showExport: false,
                commonSearch: false,
                searchFormVisible: false,
                search:false,
                columns: [
                    [
                        {field: 'state', checkbox: true,},
                        {field: 'id', title: 'ID'},
                        {
                            field: 'function', title: __('执行方法'), formatter: function (value) { 
                                return __(value);
                        } },
                        {
                            field: 'executetime', title: __('执行间隔'), formatter: function (value, row, index) { 
                                return value + 's';
                        }},
                        {field: 'runtime', title: __('最近运行时间'),formatter: Table.api.formatter.datetime},
                        {field: 'createtime', title: __('创建时间'),formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('更新时间'),formatter: Table.api.formatter.datetime},
                        {field: 'weigh', title: __('Weigh')},
                        {field: 'status', title: __('Status'), formatter: Table.api.formatter.status},
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                        }
                    ]
                ]
            });

            // var clipboard = new Clipboard('.btn-fuzhi');
            // clipboard.on('success', function(e) {
            //     Toastr.success('复制成功');
            //     console.log(e);
            // });

            // clipboard.on('error', function(e) {
            //     Toastr.error('复制失败');
            //     console.log(e);
            // });

            $('.btn-queue').click(function(){
                layer.open({
                    type: 2,
                    title: '计划任务监控',
                    shadeClose: true,
                    shade: 0.8,
                    area: [$(window).width() > 800 ? '500px' : '95%', $(window).height() > 600 ? '400px' : '95%'],
                    content: 'queue_url'
                }); 
            })

            // 计划任务监控帮助文章
            $('.btn-help').click(function () {
                layer.open({
                    type: 2,
                    title: '计划任务监控',
                    shadeClose: true,
                    shade: 0.8,
                    area: ['380px', '90%'],
                    content: 'https://bbs.btye.net/d/184'
                }); 
            });

            // $(document).on("click", ".btn-deployment", function () {
            //     Table.api.multi("general/queue/deployment", '', table, this);
            // });

            // 一键清空日志
            $('.btn-clear').click(function () { 
                layer.confirm('清空全部日志？', {
                    btn: ['确认','取消']
                }, function (index) {
                        layer.close(index);
                        layer.load(2);
                        $.ajax({
                        url: 'general/queue/quelogclear',
                        type: 'post',
                        dataType: 'json',
                        cache: false,
                            success: function (ret) {
                                layer.closeAll('loading');
                            if (ret.hasOwnProperty("code")) {
                                var msg = ret.hasOwnProperty("msg") && ret.msg != "" ? ret.msg : "";
                                if (ret.code === 1) {
                                    Toastr.success(msg ? msg : __('Wipe cache completed'));
                                } else {
                                    Toastr.error(msg ? msg : __('Wipe cache failed'));
                                }
                            } else {
                                Toastr.error(__('Unknown data format'));
                            }
                            }, error: function () {
                                layer.closeAll('loading');
                            Toastr.error(__('Network error'));
                        }
                    });
                    }, function(){
                    
                });
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        detail: function () { 
            console.log('daasdads');
            $(document).on("click", ".row_tr_click", function () {
                var _this = $(this);
                var c = $(this).attr('id');
                console.log(c);
                $('.' + c).toggle(200, 'linear');
            });
        },
        edit: function () {
            Controller.api.bindevent();
        },
        queue_url:function(){
            Controller.api.bindevent();
            $(document).on("click", "#sizing-addon-cron", function () {
                Backend.api.ajax({
                    url: "general/queue/deployment",
                    data:{type:'cron'}
                });
            });
            $(document).on("click", "#sizing-addon-url", function () {
                Backend.api.ajax({
                    url: "general/queue/deployment",
                    data:{type:'url'}
                });
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
                
            }
        }
    };
    return Controller;
});