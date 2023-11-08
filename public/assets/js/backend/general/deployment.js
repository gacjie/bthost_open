define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'clipboard.min'], function ($, undefined, Backend, Table, Form,Clipboard) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'general/deployment/index',
                    // add_url: 'general/deployment/add',
                    edit_url: 'general/deployment/edit',
                    // del_url: 'general/deployment/del',
                    multi_url: 'general/deployment/multi',
                    detail_info:'general/deployment/detail',
                    table: 'deployment',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'weigh',
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

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        upload: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
                
            }
        }
    };
    return Controller;
});