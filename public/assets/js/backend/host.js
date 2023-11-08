define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'selectpage'], function ($, undefined, Backend, Table, Form, selectPage) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'host/index' + location.search,
                    add_url: 'host/add',
                    edit_url: 'host/edit',
                    del_url: 'host/del',
                    multi_url: 'host/multi',
                    import_url: 'host/import',
                    table: 'host',
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
                        {field: 'user.username', title: __('User_id'), operate: 'LIKE'},
                        {
                            field: 'sort_id',
                            title: __('Sort_id'),
                        },
                        // {field: 'bt_id', title: __('Bt_id')},
                        {field: 'bt_name', title: __('Bt_name'), operate: 'LIKE'},
                        {field: 'site_size', title: __('站点/流量/数据库'), formatter: function (value, row, index) { 
                            str = '';
                            str += row.site_max == 0 ? '无限制<br/>' : '<progress value="' + row.site_size + '" max="' + row.site_max + '" title="' + row.site_size + '/' + row.site_max + '"></progress><br/>';
                            str += row.flow_max == 0 ? '无限制<br/>' : '<progress value="' + row.flow_size + '" max="' + row.flow_max + '" title="' + row.flow_size + '/' + row.flow_max + '"></progress><br/>';
                            str += row.sql_max == 0 ? '无限制<br/>' : '<progress value="' + row.sql_size + '" max="' + row.sql_max + '" title="' + row.sql_size + '/' + row.sql_max + '"></progress><br/>';
                            return  str;
                        }},
                        // {field: 'ip_address', title: __('Ip_address')},
                        // {field: 'domain_max', title: __('Domain_max')},
                        // {field: 'default_analysis', title: __('Default_analysis')},
                        {field: 'is_audit', title: __('Is_audit'), searchList: {"0":__('Audit 0'),"1":__('Audit 1')}, formatter: Table.api.formatter.normal},
                        // {field: 'analysis_type', title: __('Analysis_type')},
                        // {field: 'web_back_num', title: __('Web_back_num')},
                        // {field: 'sql_back_num', title: __('Sql_back_num')},
                        {field: 'check_time', title: __('Check_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'createtime',
                            title: __('Createtime'),
                            sortable: true,
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        }, {
                            field: 'updatetime',
                            title: __('Updatetime'),
                            sortable: true,
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        }, {
                            field: 'endtime',
                            title: __('Endtime'),
                            sortable: true,
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {field: 'is_vsftpd', title: __('Is_vsftpd'), searchList: {"0":__('Is_vsftpd 0'),"1":__('Is_vsftpd 1')},custom:{1: 'success', 0:'danger'}, formatter: Table.api.formatter.label},
                        {field: 'is_api', title: __('Api'), searchList: {"1":__('Yes'),"0":__('No')},custom:{1: 'success', 0:'danger'}, formatter: Table.api.formatter.label},
                        {field: 'status', title: __('Status'), searchList: {"normal":__('Status normal'),"stop":__('Status stop'),"locked":__('Status locked'),"expired":__('Status expired'),"excess":__('Status excess'),"error":__('Status error')}, formatter: Table.api.formatter.status},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'check',
                                    title: __('同步'),
                                        text: __('同步'),
                                    classname: 'btn btn-xs btn-info btn-ajax',
                                    icon: 'fa fa-exchange',
                                    url: 'host/repair',
                                        confirm: '此操作将会检查宝塔面板数据同步状态！',
                                    success: function (data) {
                                        console.log(data);
                                        if (data['btid']['0'] !== data['btid']['1']) {
                                            console.log(data)
                                            layer.msg('本地宝塔ID稽核不平，是否修改本地宝塔ID？<br/>云端'+data['btid']['1']+'->本地'+data['btid']['0'], {
                                                time: 0
                                                ,btn: ['同步', '取消']
                                                ,yes: function(index){
                                                    layer.close(index);
                                                    layer.load('2');
                                                    $.post(data.collback_url, { btid: '1'}, function(data, textStatus, xhr) {
                                                        layer.closeAll('loading');
                                                        if(data.code==1){
                                                            layer.msg(data.msg);
                                                        }else{
                                                            layer.msg('error:'+data.msg);
                                                        }
                                                    });
                                                }
                                            });
                                        }else if(data['edate']['0']!=data['edate']['1']){
                                            layer.msg('云端主机到期时间不平，是否修改云端主机到期时间？<br/>本地'+data['edate']['0']+'->云端'+data['edate']['1'], {
                                                time: 0
                                                ,btn: ['同步', '取消']
                                                ,yes: function(index){
                                                    layer.close(index);
                                                    layer.load('2');
                                                    $.post(data.collback_url, {edate:'1'}, function(data, textStatus, xhr) {
                                                        layer.closeAll('loading');
                                                        if(data.code==1){
                                                            layer.msg(data.msg);
                                                        }else{
                                                            layer.msg('error:'+data.msg);
                                                        }
                                                    });
                                                }
                                            });
                                        }else if(data['status']['0']!=data['status']['1']){
                                            layer.msg('本地主机状态不正确，是否更新本地主机状态？<br/>本地' + data['status']['0'] + '->云端' + data['status']['1'], {
                                                time: 0
                                                ,btn: ['同步', '取消']
                                                ,yes: function(index){
                                                    layer.close(index);
                                                    layer.load('2');
                                                    $.post(data.collback_url, {status:'1'}, function(data, textStatus, xhr) {
                                                        layer.closeAll('loading');
                                                        if(data.code==1){
                                                            layer.msg(data.msg);
                                                        }else{
                                                            layer.msg('error:'+data.msg);
                                                        }
                                                    });
                                                }
                                            });
                                        }else{
                                            layer.msg('当前站点与云端一致');
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'check',
                                    title: __('稽核'),
                                    text: __('稽核'),
                                    classname: 'btn btn-xs btn-primary btn-ajax',
                                    icon: 'fa fa-exchange',
                                    url: 'host/repair?websize=1',
                                    confirm: '该操作将获取该主机在云端使用的主机大小、数据库大小、流量信息（月）',
                                    success: function (data) {
                                        table.bootstrapTable('refresh');
                                        console.log(data);
                                    },
                                },
                                {
                                    name: 'addtabs',
                                    text: __('登录'),
                                    title: __('登录'),
                                    classname: 'btn btn-xs btn-warning',
                                    icon: 'fa fa-gears',
                                    url: 'host/login',
                                    extend:' target="_blank"'
                                }
                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            $(document).on("click", ".btn-add2", function () {
                top.Fast.api.open("host/add_local", "添加主机"); 
                // Fast.api.close()
            });

            // 批量同步、稽核
            $(document).on("click", ".btn-check,.btn-audit", function () {
                //在table外不可以使用添加.btn-change的方法
                //只能自己调用Table.api.multi实现
                //如果操作全部则ids可以置为空
                var ids = Table.api.selectedids(table);
                console.log(ids);
                Toastr.info('开始检查……');
                for (j = 0, len = ids.length; j < len; j++) {
                    // console.log(j, ids[j]);
                    Table.api.multi("host/repair", ids[j], table, this);
                }
                // Table.api.multi("vhostbt/repair", ids.join(","), table, this);
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
                url: 'host/recyclebin' + location.search,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'user.username', title: __('User_id')},
                        {field: 'bt_name', title: __('Bt_name')},
                        {field: 'notice', title: __('Notice')},
                        {field: 'site_size', title: __('Resource size'), formatter: function (value, row, index) { 
                            str = '';
                            str+=row.site_max==0?'无限制<br/>':'<progress value="'+row.site_size+'" max="'+row.site_max+'" title="'+row.site_size+'/'+row.site_max+'"></progress><br/>';
                            str+=row.flow_max==0?'无限制<br/>':'<progress value="'+row.flow_size+'" max="'+row.flow_max+'" title="'+row.flow_size+'/'+row.flow_max+'"></progress><br/>';
                            str+=row.sql_max==0?'无限制<br/>':'<progress value="'+row.sql_size+'" max="'+row.sql_max+'" title="'+row.sql_size+'/'+row.sql_max+'"></progress><br/>';
                            return  str;
                        }},
                        {field: 'endtime', title: __('Endtime'),formatter: Table.api.formatter.datetime},
                        {field: 'is_vsftpd', title: __('Is_vsftpd'), searchList: {"0":__('Is_vsftpd 0'),"1":__('Is_vsftpd 1')},custom:{1: 'success', 0:'danger'}, formatter: Table.api.formatter.label},
                        {field: 'is_api', title: __('Api'), searchList: {"1":__('Yes'),"0":__('No')},custom:{1: 'success', 0:'danger'}, formatter: Table.api.formatter.label},
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
                                    url: 'host/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'host/destroy',
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
            $(document).on("change", "select[name='row[plans_type]']", function () {
                if ($(this).val() == 1) {
                    $('.plans_custom').show();
                    $('.plans_list').hide();
                } else {
                    $('.plans_custom').hide();
                    $('.plans_list').show();
                }
            });
            Controller.api.bindevent();
        },
        add_local: function () {
            // $('#c-bt_name').change(function(v,k){
            //     console.log(v,k);
            // });
            // selectPage回调方法
            $('#c-bt_name').selectPage({
                eAjaxSuccess: function(data){
                    data.list = typeof data.rows !== 'undefined' ? data.rows : (typeof data.list !== 'undefined' ? data.list : []);
                    data.totalRow = typeof data.total !== 'undefined' ? data.total : (typeof data.totalRow !== 'undefined' ? data.totalRow : data.list.length);
                    return data;
                },
                eSelect:function(data){
                    if(data.edate!='0000-00-00'){
                        $('#c-endtime').val(data.edate);
                    }
                    if(data.ps){
                        $('#c-notice').val(data.ps);
                    }
                    if (data.id) {
                        $('#c-bt_id').val(data.id);
                    }
                    console.log(data);
                }
            });
            Controller.api.bindevent();
        },
        edit: function () {
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