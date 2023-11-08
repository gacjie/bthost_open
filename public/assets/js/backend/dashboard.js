define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'echarts', 'echarts-theme', 'template'], function ($, undefined, Backend, Datatable, Table, Echarts, undefined, Template) {
    var server = {};
    // 基于准备好的dom，初始化echarts实例
    var myChart = Echarts.init(document.getElementById('echart'), 'walden');
    server.toPercent = function(point){
        // 转百分数
    	if (point==0) {
    		return 0;
    	}
    	var str=Number(point*100).toFixed();
    	str+="%";
    	return str;
    }
    server.change = function(limit){
        //流量单位换算
    	var size = "";
    	if(limit < 0.1 * 1024){
    		size = limit.toFixed(2) + "B"
    	}else if(limit < 0.1 * 1024 * 1024){
    		size = (limit/1024).toFixed(2) + "KB"
    	}else if(limit < 0.1 * 1024 * 1024 * 1024){
    		size = (limit/(1024 * 1024)).toFixed(2) + "MB"
    	}else{
    		size = (limit/(1024 * 1024 * 1024)).toFixed(2) + "GB"
    	}

    	var sizeStr = size + "";
    	var index = sizeStr.indexOf(".")
    	var dou = sizeStr.substr(index + 1 ,2)
    	if(dou == "00"){              
    		return sizeStr.substring(0, index) + sizeStr.substr(index + 3, 2)
    	}
    	return size;
    }
    server.getfile = function(file){
        $.post('', {type: 'getfile',file:file}, function(data, textStatus, xhr) {
            if(data.code!='200'){
                Toastr.error(data.msg);
                return false;
            }
            layer.msg('获取成功');
            layer.open({
                area: [$(window).width() > 800 ? '800px' : '95%', $(window).height() > 600 ? '600px' : '95%'],
                title: '在线编辑',
                closeBtn:1,
                content: '<textarea class="form-control editor" id="fileBodys" cols="20" style="height:100%;">'+data.fileBody.data+'</textarea>'
                ,btn: ['保存', '取消', '预览（请先保存）']
                ,yes: function(index, layero){
                    //按钮【按钮一】的回调
                    layer.msg('提交中……');
                    server.saveFile(file,$('#fileBodys').val());
                }
                ,btn2: function(index, layero){
                    //按钮【按钮二】的回调
                    
                    //return false 开启该代码可禁止点击该按钮关闭
                }
                ,btn3: function(index, layero){
                    //按钮【按钮三】的回调
                    layer.open({
                        area: [$(window).width() > 800 ? '800px' : '95%', $(window).height() > 600 ? '600px' : '95%'],
                        skin: 'layui-layer-rim',
                        content: $('#fileBodys').val()
                    });
                    //return false 开启该代码可禁止点击该按钮关闭
                }
                ,cancel: function(){ 
                    //右上角关闭回调
                    
                    //return false 开启该代码可禁止点击该按钮关闭
                }
                // btn: ['确认', '取消', '预览（请先保存）'],
                // btn3: function(index){
                    
                // }
            });
        });
    }
    server.saveFile = function(file,va){
        Fast.api.ajax({
            url: "",
            data: {type: 'savefile',value:va,file:file}
            }, function(data, ret){
            Layer.closeAll();
        });
    }
    // 获取公告
    server.getnotice = function (tips) {
        Fast.api.ajax({
            url: 'ajax/getNotice',
            type: 'get',
        }, function (data, ret) {
            if (ret.data && ret.code == 1) {
                if (ret.data.notice) {
                    html = html2 = '';
                    html2 = '<ul class="products-list product-list-in-box">';
                    $.each(ret['data']['notice'], function (key, val) {
                        // html += '<li class="text-danger">' + ret['data']['notice'][key]['note'] + '</li>\
                        // <p class="small text-right text-info">-- ' + ret['data']['notice'][key]['time'] + '</p>';
                        html2 += '<li class="item"><div><a >' + ret['data']['notice'][key]['title'] + '</a><span class="product-description">' + ret['data']['notice'][key]['note'] + '</span></div></li>';
                    });
                    html2 += '</ul>';
                    console.log(html2);
                    $('#notices').html('');
                    // 公告渲染
                    $('#notices').html(html2);
                    if (html) {
                        // 弹出式
                        // var noticeIndex = Layer.open({
                        //     title: __('Bty官网公告'),
                        //     maxHeight: 400,
                        //     content: '<span class="label label-danger">' + __('公告内容') + '</span><br/><div>' + html.replace(/\n/g, "<br/>") + '</div>',
                        //     btn: [__('我已知晓')],
                        //     yes: function (layero, index) {
                        //         localStorage.setItem("ignorenotice", new Date().getTime() + 12 * 60 * 60 * 1000);
                        //         layer.closeAll('dialog');
                        //     }
                        // });
                    }
                }
            } else {
                if (tips) {
                    Toastr.error(__('error' + ret.msg));
                }
            }
        });
    }
    server.getnet = function(){
        $.get('ajax/getNet', {type: 'getGetNetWork'}, function(data, textStatus, xhr) {
            if(data&&data.system){
                $('#loadOne').html(server.toPercent(data.load.one));
                if(data.load.one<0.5){
                    $('#loadStatus').html('运行流畅');
                }else if(data.load.one<0.8){
                    $('#loadStatus').html('运行缓慢');
                }else if(data.load.one<1){
                    $('#loadStatus').html('运行堵塞');
                }
                $('#memBfb').html(server.toPercent(data.mem.memRealUsed/data.mem.memTotal));
                $('#netUp').html(data.up+'kb');
                $('#netDown').html(data.down+'kb');
                $('#downTotal').html(server.change(data.downTotal));
                $('#upTotal').html(server.change(data.upTotal));
                $('#mem').html(data.mem.memRealUsed+'/'+data.mem.memTotal);
                $('#cpu0').html(data.cpu['0']+'%');
                $('#cpu1').html(data.cpu['1']+'核心');
                Orderdata.column.push((new Date()).toLocaleTimeString().replace(/^\D*/, ''));// 时间
                var amount = data.up;// 下行
                Orderdata.createdata.push(amount);
                Orderdata.paydata.push(data.down);// 上行

                //按自己需求可以取消这个限制
                if (Orderdata.column.length >= 20) {
                    //移除最开始的一条数据
                    Orderdata.column.shift();
                    Orderdata.paydata.shift();
                    Orderdata.createdata.shift();
                }
                myChart.setOption({
                    xAxis: {
                        data: Orderdata.column
                    },
                    series: [{
                        name: __('Net up'),
                        data: Orderdata.paydata
                    },
                        {
                            name: __('Net down'),
                            data: Orderdata.createdata
                        }]
                });
            }else if(data.msg){
                layer.alert(data.msg);
            }else{
                layer.msg('请求错误');
            }
        });
    }
    var Controller = {
        index: function () {
            if(Config.site.auto_notice=='1'){
                server.getnotice();
            }

            // 指定图表的配置项和数据
            var option = {
                title: {
                    text: '',
                    subtext: ''
                },
                tooltip: {
                    trigger: 'axis'
                },
                legend: {
                    data: [__('Net up'), __('Net down')]
                },
                toolbox: {
                    show: false,
                    feature: {
                        magicType: {show: true, type: ['stack', 'tiled']},
                        saveAsImage: {show: true}
                    }
                },
                xAxis: {
                    type: 'category',
                    boundaryGap: false,
                    data: Orderdata.column
                },
                yAxis: {},
                grid: [{
                    left: 'left',
                    top: 'top',
                    right: '10',
                    bottom: 30
                }],
                series: [{
                    name: __('Net up'),
                    type: 'line',
                    smooth: true,
                    areaStyle: {
                        normal: {}
                    },
                    lineStyle: {
                        normal: {
                            width: 1.5
                        }
                    },
                    data: Orderdata.paydata
                },
                    {
                        name: __('Net down'),
                        type: 'line',
                        smooth: true,
                        areaStyle: {
                            normal: {}
                        },
                        lineStyle: {
                            normal: {
                                width: 1.5
                            }
                        },
                        data: Orderdata.createdata
                    }]
            };

            // 使用刚指定的配置项和数据显示图表。
            myChart.setOption(option);

            //动态添加数据，可以通过Ajax获取数据然后填充
            
            var auto_getnet = setInterval(function () {
                server.getnet();
                if(Config.site.auto_flow!='1'){
                    clearInterval(auto_getnet);
                }
            }, 2000);

            $(document).on('click','.btn-refresh-load',function(){
                $(this).addClass('fa-spin');
                server.getnet();
                setTimeout(function(){ $('.btn-refresh-load').removeClass('fa-spin'); }, 2000);
            });
            
            $(window).resize(function () {
                myChart.resize();
            });

            $(document).on("click", ".btn-refresh", function () {
                setTimeout(function () {
                    myChart.resize();
                }, 0);
            });
            
            // 版本检测
            $(document).on("click", ".btn-checkversion", function () {
                if ($(this).attr('disabled')) {
                    return false;
                } else {
                    $(this).attr('disabled', 'disabled');
                }
                top.window.$("[data-toggle=checkupdate]").trigger("click");
            });

            // 节点检测
            $(document).on("click", ".btn-testing", function () {
                Fast.api.ajax({
                    url: 'ajax/testing',
                    dataType: 'json',
                }, function (data, ret) {
                        curl = ret.data.curl ? '<span class="text-success">正常</span>' : '<span class="text-danger">异常</span>';
                        api_url1 = ret.data.api_url1 ? '<span class="text-success">正常 ' + ret.data.api_url1 + '</span>' : '<span class="text-danger">连接异常</span>';
                        api_url2 = ret.data.api_url2 ? '<span class="text-success">正常 ' + ret.data.api_url2 + '</span>' : '<span class="text-danger">连接异常</span>';
                        lan_url = ret.data.lan_url ? '<span class="text-success">正常 ' + ret.data.lan_url + '</span>' : '<span class="text-danger">连接异常</span>';
                        baidu = ret.data.baidu ? '<span class="text-success">正常 '+ret.data.baidu+'</span>' : '<span class="text-danger">异常</span>';
                    content = 'Curl：' + curl + '<br/>节点1：' + api_url1 + '<br/>节点2：' + api_url2 + '<br/>外网连接：' + baidu + '<br/>内网连接：' + lan_url;
                    Layer.alert(content);
                }, function (data, ret) {
                });
            });

            $(document).on("click", ".btn-initssss", function () {
                type = $(this).data('type');
                console.log(type);
                switch (type) {
                    case 'beian':
                        Fast.api.ajax({
                            url: "domainbeian/create_notbeian_site",
                            }, function(data, ret){
                        });
                        break;
                    case 'bt_update_on':
                        Fast.api.ajax({
                            url: "ajax/setAutoUpdate",
                            data:{is:'on'},
                            }, function(data, ret){
                        });
                        break;
                    case 'bt_update_off':
                        Fast.api.ajax({
                            url: "ajax/setAutoUpdate",
                            data:{is:'off'},
                            }, function(data, ret){
                        });
                        break;
                    default:
                        break;
                }
            });

            // 清空临时目录文件
            $(document).on("click", ".btn-clearlogs", function () {
                if ($(this).attr('disabled')) {
                    return false;
                } else { 
                    $(this).attr('disabled','disabled');
                }
                top.window.$("[data-type=logs]").trigger("click");
                return false;
            });

            $(document).on("click", "#default", function () {
                var strVar = "";
                    strVar += "<div class=\"text-center\" id=\"btAction\">\n";
                    strVar += "<button class=\"btn btn-default default_file\" data-val=\"default\" type=\"button\">\n";
                    strVar += "默认文档\n";
                    strVar += "<\/button>\n";
                    strVar += "<button class=\"btn btn-default default_file\" data-val=\"404\" type=\"button\">\n";
                    strVar += "404错误页\n";
                    strVar += "<\/button>\n";
                    strVar += "<button class=\"btn btn-default default_file\" data-val=\"nosite\" type=\"button\">\n";
                    strVar += "空白页\n";
                    strVar += "<\/button>\n";
                    strVar += "<button class=\"btn btn-default default_file\" data-val=\"stop\" type=\"button\">\n";
                    strVar += "默认站点停止页\n";
                    strVar += "<\/button>\n";
                    strVar += "<button class=\"btn btn-default default_file\" data-val=\"beian\" type=\"button\">\n";
                    strVar += "未备案引导页\n";
                    strVar += "<\/button>\n";
                    strVar += "<\/div>\n";
                    layer.open({
                    type: 1,
                    title: '修改默认页',
                    skin: 'layui-layer-rim',
                    content: strVar
                });
            });

            $(document).on('click','.default_file',function(){
                file = $(this).data('val');
                server.getfile(file);
            })

            $(document).on("click", "#Recycle", function () {
                layer.confirm('将清空回收站，且不能恢复',{
                    btn: ['删除','取消']
                }, function(){
                    Fast.api.ajax({
                        url: "",
                        data: {type: 'Close_Recycle_bin'}
                        }, function(data, ret){
                        Layer.closeAll();
                    });
                }, function(index){
                    layer.close(index);
                }); 
            });
            $(document).on("click", "#CloseLogs", function () {
                layer.confirm('将删除所有网站日志，且不能恢复',{
                    btn: ['删除','取消']
                }, function(){
                    Fast.api.ajax({
                        url: "",
                        data: {type: 'CloseLogs'}
                        }, function(data, ret){
                        Layer.closeAll();
                    });
                }, function(index){
                    layer.close(index);
                });
            });
            $(document).on("click", "#update", function () {
                Fast.api.ajax({
                    url: "",
                    data: {type: 'checkUp'}
                    }, function(data, ret){
                        if(data.code=='0'){
                        Toastr.error(data.msg);
                        return false;
                        }else{
                        layer.confirm('有新的面板版本更新，是否更新？',{
                        title:'有新的面板版本更新，是否更新？',
                        btn: ['更新','取消'],
                        content:data.msg
                        }, function(indexs){
                        layer.close(indexs);
            
                        Fast.api.ajax({
                        url: "",
                        data: {type: 'update'}
                        }, function(data, ret){
                        Layer.closeAll();
                        });
                        }, function(index){
                        layer.close(index);
                        });
                        }
                        Layer.closeAll();
                    });
            });
            $(document).on("click", "#re_panel", function () {
                layer.confirm('将尝试校验并修复面板程序，继续吗？',{
                    btn: ['确定','取消']
                }, function(){
                    Fast.api.ajax({
                        url: "",
                        data: {type: 're_panel'}
                        }, function(data, ret){
                        Layer.closeAll();
                    });
                }, function(index){
                    layer.close(index);
                });
            });
            $(document).on("click", "#reWeb", function () {
                layer.confirm('即将重启面板服务，继续吗？',{
                    btn: ['确定','取消']
                }, function(){
                    Fast.api.ajax({
                        url: "",
                        data: {type: 'reweb'}
                        }, function(data, ret){
                        Layer.closeAll();
                    });
                }, function(index){
                    layer.close(index);
                });
            });
            $(document).on("click", "#RestartServer", function () {
                layer.confirm('即将重启服务器，继续吗？',{
                    btn: ['确定','取消']
                }, function(){
                    Fast.api.ajax({
                        url: "",
                        data: {type: 'reboot'}
                        }, function(data, ret){
                        Layer.closeAll();
                    });
                }, function(index){
                    layer.close(index);
                });
            });
        }
    };

    return Controller;
});
