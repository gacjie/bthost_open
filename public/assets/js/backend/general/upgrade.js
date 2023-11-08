define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var server = {};
    //版本检测
    server.checkupdate = function (ignoreversion, tips) {
        Fast.api.ajax({
            url: 'ajax/update_check',
            type: 'post',
        }, function (data, ret) {
            if (ret.data && ignoreversion !== ret.data.newversion && ret.code == 1) {
                $('.btn-check').append('<span class="update_new">New</span>');
            } else {
                if (tips) {
                    Toastr.error(ret.msg);
                    return false;
                }
            }
        })
    };
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'general/upgrade/index',
                    down_url: 'general/upgrade/down',
                    check_url: 'general/upgrade/check',
                    update_url: 'general/upgrade/update',
                    add_url: 'general/upgrade/add',
                    edit_url: 'general/upgrade/edit',
                    del_url: 'general/upgrade/del',
                    multi_url: 'general/upgrade/multi',
                    import_url: 'general/upgrade/import',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'path',
                // sortName: 'path',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'path', title: __('更新文件')},
                        {field: 'type', title: __('更新方式'), formatter: function (value, row, index) { 
                                return value=='覆盖' ? '<span style="color:red">覆盖</span>' :value;
                        }},
                        {field: 'ltime', title: __('本地时间')},
                        {field: 'ctime', title: __('更新时间')},
                    ]
                ],
                pageSize:0,
                showToggle:false,
                showColumns:false,
                visible:false,
                showExport:false,
                commonSearch:false,
                operate:false,
                search:false,
            });

            $(document).on("click", ".btn-check", function () {
                // layer.load(2);
                $('#table').bootstrapTable('showLoading', true);
                $('.btn-down').removeClass('hide');
                $('.btn-update').addClass('hide');
                $('#table').bootstrapTable('refresh', {url:$.fn.bootstrapTable.defaults.extend.check_url});
                
                // layer.closeAll('loading');
            });
            $(document).on("click", ".btn-update", function () {
                list = Table.api.selectedids(table);
                var lists='';
                $(list).each(function(index1,element1){
                    console.log(index1,element1);
                    if(index1==0){
                        lists += element1;
                    }else{
                        lists += ','+element1;
                    }
                });
                Fast.api.ajax({
                    url: "general/upgrade/update", 
                    data: {list:lists}
                }, function(data, ret){
                    //成功的回调
                });
            });

            $(document).on("click",'.btn-down',function() {
                // loading
                //延迟执行、避免文件太多卡死问题
                list = Table.api.selectedids(table);
                var len = list.length;
                var exe=0;
                layer.load(2);
                $(list).each(function(index,element){
                    setTimeout(function () {
                        $.ajax({
                            type: "post",
                            url: $.fn.bootstrapTable.defaults.extend.down_url,
                            data:{list:element},
                            dataType: "json",
                            success: function (response) {
                                if(response.code==1){
                                    exe++;
                                    layer.msg(response.msg);
                                }else{
                                    layer.msg(response.msg, {icon: 5}); 
                                }
                                
                                if(exe==len){
                                    layer.open({
                                        type: 0,
                                        title:'下载更新',
                                        closeBtn:1,
                                        btn: ['立即更新', '稍后更新'],
                                        content: '所选文件全部下载完成!',
                                        yes: function(index, layero){
                                            layer.close(index);
                                            var lists='';
                                            $(list).each(function(index1,element1){
                                                console.log(index1,element1);
                                                if(index1==0){
                                                    lists += element1;
                                                }else{
                                                    lists += ','+element1;
                                                }
                                            });
                                            Fast.api.ajax({
                                                url: "general/upgrade/update", 
                                                data: {list:lists}
                                            }, function(data, ret){
                                                //成功的回调
                                            });
                                        },
                                        btn2: function(index, layero){
                                            layer.close(index);
                                            $('.btn-down').addClass('hide');
                                            $('.btn-update').removeClass('hide');
                                            $('#table').bootstrapTable('refresh', {url:$.fn.bootstrapTable.defaults.extend.index_url});
                                        }
                                    });
                                }
                                layer.closeAll('loading');
                            }
                        });
                    }, index*1000);
                });
            });

            var ignoreversion = localStorage.getItem("ignoreversion");
            if (Config.bty.checkupdate && ignoreversion !== "*") {
                server.checkupdate(ignoreversion, false);
            }

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});