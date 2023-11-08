define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'domain/index' + location.search,
                    add_url: 'domain/add',
                    edit_url: 'domain/edit',
                    del_url: 'domain/del',
                    multi_url: 'domain/multi',
                    import_url: 'domain/import',
                    table: 'domain',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'domain', title: __('Domain')},
                        {field: 'domainpools.name', title: __('Domainpools_id')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'dnspod', title: __('Dnspod'), searchList: {"0":__('Dnspod 0'),"1":__('Dnspod 1')}, formatter: Table.api.formatter.normal},
                        {field: 'status', title: __('Status'), searchList: {"normal":__('Normal'),"hidden":__('Hidden'),"locked":__('Locked')}, formatter: Table.api.formatter.status},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons: [
                            {
                                name: 'detail',
                                title: __('域名信息'),
                                text: __('域名信息'),
                                classname: 'btn btn-xs btn-primary btn-dialog',
                                icon: 'fa fa-list',
                                url: 'domain/detail?info=1',
                                callback: function (data) {
                                    Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                },
                                visible: function (row) {
                                    // console.log(row);
                                    //返回true时按钮显示,返回false隐藏
                                    return row.dnspod=='1'?true:false;
                                }
                            },
                            {
                                name: 'detail',
                                title: __('域名日志'),
                                text: __('域名日志'),
                                classname: 'btn btn-xs btn-info btn-dialog',
                                icon: 'fa fa-book',
                                url: 'domain/detail?log=1',
                                callback: function (data) {
                                    Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                },
                                visible: function (row) {
                                    console.log(row.dnspod);
                                    //返回true时按钮显示,返回false隐藏
                                    return row.dnspod=='1'?true:false;
                                }
                            }],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            $(document).on("click", ".btn-config", function () {
                top.Fast.api.open("domain/config", "dnspod配置"); 
                // Fast.api.close()
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        recyclebin: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    'dragsort_url': ''
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: 'domain/recyclebin' + location.search,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'domain_info', title: __('Domain')},
                        {field: 'dnspod', title: __('dnspod'), searchList: {"0":__('Dnspod 0'),"1":__('Dnspod 1')}, formatter: Table.api.formatter.normal},
                        {
                            field: 'deletetime',
                            title: __('Deletetime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'operate',
                            width: '130px',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'Restore',
                                    text: __('Restore'),
                                    classname: 'btn btn-xs btn-info btn-ajax btn-restoreit',
                                    icon: 'fa fa-rotate-left',
                                    url: 'domain/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'domain/destroy',
                                    refresh: true
                                }
                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        detail: function () {
            Controller.api.bindevent();
        },
        config: function () {
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