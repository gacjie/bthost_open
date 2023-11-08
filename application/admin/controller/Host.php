<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\library\Btaction;
use dnspod\Dnspod;
use fast\Random;
use think\Db;
use think\Cookie;
use think\Config;

/**
 * 主机管理
 *
 * @icon fa fa-circle-o
 */
class Host extends Backend
{

    /**
     * Host模型对象
     * @var \app\admin\model\Host
     */
    protected $model = null;

    protected $relationSearch = true;
    protected $searchFields = ['id', 'bt_name', 'user.username'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Host;
        $this->productModel = model('\app\common\model\Product');
        $this->view->assign("isVsftpdList", $this->model->getIsVsftpdList());
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign('plans_type', $this->model::plans_type());
    }

    public function import()
    {
        parent::import();
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->with('user')
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->with('user')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            foreach ($list as $k => $v) {
                // 关联分类
                $v->sort_id = $this->model->sort($v->sort_id);
                $v->hidden(['user.password', 'user.salt']);
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    // 创建主机
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            $plans_type = isset($params['plans_type']) ? $params['plans_type'] : 0;
            if ($plans_type == 0 && empty($params['plans'])) {
                $this->error('请选择资源');
            }
            try {
                // 读取资源组
                // 资源组信息转化
                if ($plans_type == 1) {
                    // 自定义配置
                    $params['pack']['domainpools_id'] = $params['domainpools_id'];
                    $params['pack']['ippools_id'] = $params['ippools_id'];
                    $params['pack']['preset_procedure'] = $params['preset_procedure'];
                    $params['pack']['phpver'] = $params['phpver'];
                    $plansInfo = model('Plans')->getPlanInfo('', $params['pack']);
                } else {
                    // 资源组配置
                    $plansInfo = model('Plans')->getPlanInfo($params['plans']);
                }


                if (!$plansInfo) {
                    throw new \think\Exception(model('Plans')->msg);
                }

                $bt = new Btaction();
                if (!$bt->test()) {
                    throw new \think\Exception($bt->_error);
                }


                $hostSetInfo = $bt->setInfo($params, $plansInfo);
                if (!$hostSetInfo) {
                    throw new \think\Exception('站点信息构建失败，请重试|' . json_encode($plansInfo));
                }
                // vsftpd创建
                if (isset($plansInfo['vsftpd']) && $plansInfo['vsftpd'] == 1
                ) {
                    // 调用vsftpd进行目录创建
                    $creatVsftpdPath = $bt->btPanel->AddVsftpdUser($hostSetInfo['username'], $hostSetInfo['password'], $hostSetInfo['path'], $plansInfo['site_max'], $plansInfo['limit_rate']);
                    if ($creatVsftpdPath && isset($creatVsftpdPath['status']) && $creatVsftpdPath['status'] != 'Success') {
                        throw new \think\Exception('主机创建失败->' . $creatVsftpdPath['msg'] . '|' . json_encode($hostSetInfo));
                    } elseif ($creatVsftpdPath && !isset($creatVsftpdPath['status'])) {
                        throw new \think\Exception('主机创建失败->vsftpd网站根目录创建失败|' . json_encode($hostSetInfo['path']));
                    } elseif (isset($creatVsftpdPath['msg'])) {
                        throw new \think\Exception('主机创建失败->' . $creatVsftpdPath['msg'] . '|' . json_encode($hostSetInfo));
                    }
                }

                // 连接宝塔进行站点开通
                $btInfo = $bt->btBuild($hostSetInfo);
                if (!$btInfo) {
                    throw new \think\Exception($bt->_error);
                }
                $bt->bt_id = $btId = $btInfo['siteId'];
                $btName = $hostSetInfo['bt_name'];

                Db::startTrans();


                // 修改到期时间
                $timeSet = $bt->btPanel->WebSetEdate($btId, $params['endtime']);
                if (!$timeSet['status']) {
                    throw new \think\Exception('开通时间设置失败|' . json_encode($params['endtime']));
                }

                // 预装程序
                if ($plansInfo['preset_procedure']) {
                    // 程序预装
                    $defaultPhp = $hostSetInfo['version'] && $hostSetInfo['version'] != '00' ? $hostSetInfo['version'] : '56';
                    $setUp = $bt->presetProcedure($plansInfo['preset_procedure'], $btName, $defaultPhp);
                    if (!$setUp) {
                        throw new \think\Exception($bt->_error);
                    }
                }

                if ($plansInfo['session']) {
                    // session隔离
                    $bt->btPanel->set_php_session_path($btId, 1);
                }


                // 并发、限速设置
                // 默认并发、网速限制
                if (isset($plansInfo['perserver']) && $plansInfo['perserver'] != 0 && isset($bt->serverConfig['webserver']) && $bt->serverConfig['webserver'] == 'nginx') {
                    $modify_status = $bt->setLimit($plansInfo);
                    if (!$modify_status) {
                        throw new \think\Exception($bt->_error);
                    }
                }

                $dnspod_record = $dnspod_record_id = $dnspod_domain_id = '';

                if ($plansInfo['dnspod']) {
                    // 如果域名属于dnspod智能解析
                    $record_type = Config::get('site.dnspod_analysis_type');
                    $analysis = Config::get('site.dnspod_analysis_url');

                    $sub_domain = $hostSetInfo['domain'];
                    $domain_jx = $this->model->doamin_analysis($plansInfo['domain'], $analysis, $sub_domain, $record_type);
                    if (!is_array($domain_jx)) {
                        throw new \think\Exception('域名解析失败|' . json_encode([$plansInfo['domain'], $analysis, $sub_domain, $domain_jx], JSON_UNESCAPED_UNICODE));
                    }
                    $dnspod_record = $sub_domain;
                    $dnspod_record_id = $domain_jx['id'];
                    $dnspod_domain_id = $domain_jx['domain_id'];
                }

                // 绑定多ip

                // 获取信息后存入数据库
                $host_data = [
                    'user_id'      => $params['user_id'],
                    'sort_id'      => $params['sort_id'],
                    'bt_id'        => $btId,
                    'bt_name'      => $btName,
                    'site_max'     => $plansInfo['site_max'],
                    'sql_max'      => $plansInfo['sql_max'],
                    'flow_max'     => $plansInfo['flow_max'],
                    'is_audit'     => $plansInfo['domain_audit'],
                    'is_vsftpd'    => $plansInfo['vsftpd'],
                    'domain_max'   => $plansInfo['domain_num'],
                    'web_back_num' => $plansInfo['web_back_num'],
                    'sql_back_num' => $plansInfo['sql_back_num'],
                    'ip_address'   => isset($plansInfo['ipArr']) ? $plansInfo['ipArr'] : '',
                    'endtime'      => $params['endtime'],
                    'perserver'    => $plansInfo['perserver'] ?? 0,
                    'limit_rate'   => $plansInfo['limit_rate'] ?? 0,
                    'sub_bind'     => $plansInfo['sub_bind'] ?? 0,
                ];
                $inc = model('Host')::create($host_data);

                $vhost_id = $inc->id;
                if (!$vhost_id) {
                    throw new \think\Exception('主机信息存储失败');
                }

                if ($btInfo['ftpStatus'] == true) {
                    // 存储ftp
                    $ftp = model('Ftp')::create(['vhost_id' => $vhost_id,
                                                 'username' => $btInfo['ftpUser'],
                                                 'password' => $btInfo['ftpPass'],
                    ]);
                }

                if ($btInfo['databaseStatus'] == true) {
                    // 存储sql
                    $sql = model('Sql')::create(['vhost_id' => $vhost_id,
                                                 'database' => $btInfo['databaseUser'],
                                                 'username' => $btInfo['databaseUser'],
                                                 'password' => $btInfo['databasePass'],
                    ]);
                }

                // 存入域名信息
                model('Domainlist')::create(['domain'           => $btName,
                                             'vhost_id'         => $vhost_id,
                                             'domain_id'        => $plansInfo['domainlist_id'],
                                             'dnspod_record'    => $dnspod_record,
                                             'dnspod_record_id' => $dnspod_record_id,
                                             'dnspod_domain_id' => $dnspod_domain_id,
                                             'dir'              => '/',
                ]);

                Db::commit();
            } catch (\Exception $ex) {
                return ['code' => 0, 'msg' => $ex->getMessage()];
            } catch (\Throwable $th) {
                return ['code' => 0, 'msg' => $th->getMessage()];
            }

            $this->success('添加成功');
        }
        $siteList = [];

        $groupList = $this->productModel::getGroupList();

        foreach ($groupList as $k => $v) {
            $siteList[$k]['name'] = $k;
            $siteList[$k]['title'] = $v;
            $siteList[$k]['list'] = [];
        }
        foreach ($this->productModel->all() as $k => $v) {
            $value = $v->toArray();
            $value['title'] = __($value['title']);
            if (in_array($value['type'], ['select', 'selects', 'checkbox', 'radio'])) {
                $value['value'] = explode(',', $value['value']);
            }
            $value['content'] = json_decode($value['content'], true);
            $value['tip'] = htmlspecialchars($value['tip']);
            $siteList[$v['group']]['list'][] = $value;
        }
        $index = 0;
        foreach ($siteList as $k => &$v) {
            $v['active'] = !$index ? true : false;
            $index++;
        }
        $this->view->assign('siteList', $siteList);
        return $this->view->fetch();
    }

    public function edit($ids = null)
    {
        return parent::edit($ids);
    }

    // 一键登录主机
    public function login($ids = null)
    {
        $hostInfo = $this->model::get($ids);
        if (!$hostInfo) {
            $this->error('没有找到当前主机');
        }
        // 登录用户
        $userAuth = new \app\common\library\Auth();
        if (!$userAuth->direct($hostInfo->user_id)) {
            $this->error($userAuth->getError(), null, ['token' => $this->request->token()]);
        }
        Cookie::set('uid', $userAuth->id);
        Cookie::set('token', $userAuth->getToken());
        // cookie切换到主机id
        Cookie::set('host_id_' . $hostInfo->user_id, $hostInfo->id);

        // 跳转首页控制台
        return $this->redirect('/');
    }

    // 添加主机
    public function add_local()
    {
        // 考虑使用拉下选择并查找
        if ($this->request->isPost()) {
            // 需要接收参数
            $params = $this->request->post('row/a');
            $sort_id = isset($params['sort_id']) ? $params['sort_id'] : 0;
            $bt_name = isset($params['bt_name']) ? $params['bt_name'] : '';
            $ftp_name = isset($params['ftp_name']) ? $params['ftp_name'] : '';
            $sql_name = isset($params['sql_name']) ? $params['sql_name'] : '';
            $user_id = isset($params['user_id']) ? $params['user_id'] : '';
            $endtime = isset($params['endtime']) ? $params['endtime'] : '';
            $notice = isset($params['notice']) ? $params['notice'] : '';
            $bt_id = isset($params['bt_id']) ? $params['bt_id'] : '';
            if (!$bt_name) {
                $this->error('必须填写或选择站点');
            }
            if (!$user_id) {
                $this->error('必须选择一个用户');
            }
            // 连接宝塔
            $bt = new Btaction();
            $bt->bt_id = $bt_id;
            $bt->bt_name = $bt_name;
            $bt->ftp_name = $ftp_name;
            $bt->sql_name = $sql_name;
            // 查找站点
            $hostInfo = $bt->getSiteInfo();
            if (!$hostInfo) {
                $this->error($bt->_error);
            }
            // 查找ftp
            if ($ftp_name) {
                $ftpInfo = $bt->getFtpInfo();
                if (!$ftpInfo) {
                    $this->error($bt->_error);
                }
            }
            if ($sql_name) {
                $sqlInfo = $bt->getSqlInfo();
                if (!$sqlInfo) {
                    $this->error($bt->_error);
                }
            }
            // 判断站点是否已存在
            $hostfind = model('Host')::get(['bt_name' => $bt_name]);
            if ($hostfind) {
                $this->error('站点已存在，请勿重复添加');
            }
            // 判断数据库
            $sqlfind = model('Sql')::get(['database' => $sql_name]);
            if ($sqlfind) {
                $this->error('数据库已存在，请勿重复添加');
            }
            // 判断ftp
            $sqlfind = model('Ftp')::get(['username' => $ftp_name]);
            if ($sqlfind) {
                $this->error('数据库已存在，请勿重复添加');
            }
            if ($endtime) {
                $bt->setEndtime($endtime);
            }
            // var_dump($hostInfo,$ftpInfo,$sqlInfo);exit;
            // 都查找完毕后存入数据库
            $hostInc = model('Host')::create(['user_id'      => $user_id,
                                              'sort_id'      => $sort_id,
                                              'bt_id'        => $hostInfo['id'],
                                              'bt_name'      => $hostInfo['name'],
                                              'domain_max'   => 0,
                                              'web_back_num' => 0,
                                              'sql_back_num' => 0,
                                              'notice'       => $notice,
                                              'endtime'      => $endtime ? $endtime : $hostInfo['edate'],
            ]);
            $host_id = $hostInc->id;
            if ($ftp_name) {
                model('Ftp')::create(['vhost_id' => $host_id,
                                      'username' => $ftpInfo['name'],
                                      'password' => $ftpInfo['password'],
                ]);
            }
            if ($sql_name) {
                model('Sql')::create(['vhost_id' => $host_id,
                                      'username' => $sqlInfo['username'],
                                      'database' => $sqlInfo['name'],
                                      'password' => $sqlInfo['password'],
                ]);
            }
            // 都入库了就成功了
            $this->success('添加成功');
        }
        return $this->view->fetch('add2');
    }

    // 站点列表
    public function weblist()
    {
        config('default_return_type', 'json');
        $pageNumber = $this->request->post('pageNumber', 1);
        $pageSize = $this->request->post('pageSize', 15);
        $serch = $this->request->post('name');
        // 搜索重置分页
        $pageNumber = $serch ? 1 : $pageNumber;
        $bt = new Btaction();
        $list = $bt->getSiteList($serch, $pageNumber, $pageSize);
        if ($list && isset($list['data'])) {
            $row = $list['data'];
            $total = $bt->sqlCount($serch);
            return json(['list' => $row, 'total' => $total]);
        } else {
            return [];
        }
    }

    // ftp列表
    public function ftplist()
    {
        config('default_return_type', 'json');
        $pageNumber = $this->request->post('pageNumber', 1);
        $pageSize = $this->request->post('pageSize', 15);
        $serch = $this->request->post('name');
        // 搜索重置分页
        $pageNumber = $serch ? 1 : $pageNumber;
        $bt = new Btaction();
        $list = $bt->getFtpList($serch, $pageNumber, $pageSize);
        if ($list && isset($list['data'])) {
            $row = $list['data'];
            $total = $bt->ftpCount($serch);
            return json(['list' => $row, 'total' => $total]);
        } else {
            return [];
        }
    }

    // 数据库列表
    public function sqllist()
    {
        config('default_return_type', 'json');
        $pageNumber = $this->request->post('pageNumber', 1);
        $pageSize = $this->request->post('pageSize', 15);
        $serch = $this->request->post('name');
        // 搜索重置分页
        $pageNumber = $serch ? 1 : $pageNumber;
        $bt = new Btaction();
        $list = $bt->getSqlList($serch, $pageNumber, $pageSize);
        if ($list && isset($list['data'])) {
            $row = $list['data'];
            $total = $bt->siteCount($serch);
            return json(['list' => $row, 'total' => $total]);
        } else {
            return [];
        }
    }

    /**
     * 批量操作
     *
     * @param [type] $ids   vhost_id
     * @return void
     */
    public function repair($ids = null)
    {
        $arr = explode(',', $ids);
        $params = $this->request->param('params');

        foreach ($arr as $key => $value) {
            $ids = $value;
            $hostInfo = model('Host')::get($ids);
            if (!$hostInfo) {
                $this->error('主机不存在');
            }
            $ftpInfo = model('Ftp')::get(['vhost_id' => $hostInfo->id, 'status' => 'normal']);
            $sqlInfo = model('Sql')::get(['vhost_id' => $hostInfo->id, 'status' => 'normal']);
            $hostInfo_new = $hostInfo;
            $hostInfo_new->ftp = $ftpInfo ? $ftpInfo : '';
            $hostInfo_new->sql = $sqlInfo ? $sqlInfo : '';
            $bt = new Btaction();
            $bt->sql_name = isset($hostInfo_new->sql->username) ? $hostInfo_new->sql->username : '';
            $bt->ftp_name = isset($hostInfo_new->ftp->username) ? $hostInfo_new->ftp->username : '';
            $bt->bt_name = $hostInfo->bt_name;

            // 测试连接
            if (!$bt->test()) {
                $this->error($bt->getError());
            }
            $Websites = $bt->getSiteInfo();
            if (!$Websites) {
                $this->error('Site：' . $hostInfo->bt_name . '<br/>' . $bt->getError());
            }
            $btid = $Websites['id'];
            $edate = $Websites['edate'];
            $status = $Websites['status'];

            // 使用获取到的btid使用
            $bt->bt_id = $btid;

            if (input('param.sync') || $params == 'sync') {
                // 强制同步
                $emsg = 'Site：' . $hostInfo->bt_name . '<br/>';
                // 同步云端宝塔ID到本地
                $hostInfo->bt_id = $btid;
                $btidUp = $hostInfo->allowField(true)->save();
                if ($btidUp) {
                    $emsg .= "ID：$btid->$hostInfo->bt_id 成功<br/>";
                } else {
                    $emsg .= "ID：$btid->$hostInfo->bt_id 失败<br/>";
                }

                // 同步本地到期时间到云端
                $localDate = date('Y-m-d', $hostInfo->endtime);
                $timeSet = $bt->setEndtime($localDate);
                if ($timeSet) {
                    $emsg .= "Time：$localDate->$edate 成功<br/>";
                } else {
                    $emsg .= "Time：$localDate->$edate 失败<br/>";
                }

                // 同步云端主机状态到本地
                // 由于本地状态多样性，导致云端和本地不可能完全同步，所以考虑是否由本地同步到云端，实现主机启停

                if ($hostInfo->status == 'normal' && $status != 1) {
                    $statusUp = $bt->webstart();
                } elseif ($hostInfo->status != 'normal' && $status == 1
                ) {
                    $statusUp = $bt->webstop();
                } else {
                    $statusUp = 1;
                }

                if ($statusUp && $timeSet) {
                    $this->success($emsg);
                } else {
                    $this->error($emsg);
                }
                $this->success($emsg);
            } elseif (input('param.btid')) {
                // 同步云端宝塔ID到本地
                $hostInfo->bt_id = $btid;
                $btidUp = $hostInfo->allowField(true)->save();
                if ($btidUp) {
                    $this->success('同步成功');
                } else {
                    $this->error('同步失败');
                }
            } elseif (input('param.edate')) {
                // 同步本地到期时间到云端
                $localDate = date('Y-m-d', $hostInfo->endtime);
                $timeSet = $bt->setEndtime($localDate);
                if ($timeSet) {
                    $this->success('同步成功' . date('Y-m-d', $hostInfo->getData('endtime')));
                } else {
                    $this->error('同步失败');
                }
            } elseif (input('param.status')) {
                // 同步云端主机状态到本地
                if ($hostInfo->status == 'normal' && $status != 1) {
                    $statusUp = $bt->webstart();
                } else {
                    $statusUp = $bt->webstop();
                }

                if ($statusUp) {
                    $this->success('同步成功');
                } else {
                    $this->error('同步失败');
                }
            } elseif (input('param.speedget')) {
                // 获取限速
                $speedInfo = $bt->btPanel->GetLimitNet($btid);
                // 区分linux和windows
                if (isset($speedInfo['limit_rate']) && isset($speedInfo['perip']) && isset($speedInfo['perserver'])) {
                    return [
                        'code'       => 1,
                        'msg'        => '获取成功',
                        'perserver'  => $speedInfo['perserver'],
                        'perip'      => $speedInfo['perip'],
                        'limit_rate' => $speedInfo['limit_rate'],
                    ];
                } else {
                    $this->error('获取失败');
                }
            } elseif (input('param.speed')) {
                // 设置限速
                $perserver = input('param.perserver/d') ? input('param.perserver/d') : 0;
                $perip = input('param.perip/d') ? input('param.perip/d') : 0;
                $limit_rate = input('param.limit_rate/d') ? input('param.limit_rate/d') : 0;
                // 区分linux和windows
                $modify_status = $bt->btPanel->SetLimitNet($btid, $perserver, $perip, $limit_rate);
                if (isset($modify_status) && $modify_status['status'] == 'true') {
                    $this->success($modify_status['msg']);
                } else {
                    $this->error('设置失败：' . $modify_status['msg']);
                }
            } elseif (input('param.speedoff')) {
                // 关闭限速
                $modify_status = $bt->closeLimit();
                if (!$modify_status) {
                    $this->error($bt->_error);
                }
                $this->success('成功');
            } elseif (input('param.websize') || $params == 'websize') {
                // 资源稽核
                $msg = $overflow = '';
                $msg .= 'Site：' . $hostInfo->bt_name . '<br/>';

                $resource = $bt->getResourceSize();

                $getSqlSizes = $resource['sqlsize'];
                $getWebSizes = $resource['websize'];
                $total_size = $resource['total_size'];

                Db::startTrans();

                if ($sqlInfo) {
                    $hostInfo->sql_size = $getSqlSizes;
                    if ($hostInfo->sql_max != 0 && $hostInfo->sql_size > $hostInfo->sql_max) {
                        $overflow = 1;
                    }
                }

                $hostInfo->site_size = $getWebSizes;
                $hostInfo->flow_size = $total_size;
                $hostInfo->check_time = time();

                // 对比资源，检查是否超出
                if ($hostInfo->site_max != 0 && $hostInfo->site_size > $hostInfo->site_max) {
                    $overflow = 1;
                }
                if ($hostInfo->flow_max != 0 && $hostInfo->flow_size > $hostInfo->flow_max) {
                    $overflow = 1;
                }

                if ($overflow) {
                    $hostInfo->status = 'excess';
                } else {
                    // 判断既没有过期，也处于没有超量状态，就恢复主机
                    if ($hostInfo->endtime > time() && $hostInfo->status == 'excess') {
                        $hostInfo->status = 'normal';
                    }
                }
                $hostInfo->check_time = time();
                $save2 = $hostInfo->allowField(true)->save();

                if (!$save2) {
                    Db::rollback();
                    $this->error($msg . '写入失败');
                }
                Db::commit();
                $this->success($msg . '检查完成');
            } else {
                $vhostStatus = $hostInfo->status == 'normal' ? 1 : 0;
                return $this->success('请求成功', '', [
                    'btid'         => [
                        $hostInfo->bt_id,
                        $btid,
                    ],
                    'edate'        => [
                        date("Y-m-d", $hostInfo->getData('endtime')),
                        $Websites['edate'],
                    ],
                    'status'       => [
                        $vhostStatus, //判断状态，非normal都为0
                        $status, // 只有0和1
                    ],
                    'collback_url' => $this->request->url(true),
                    'ids'          => $ids,
                ]);
            }
        }
    }
}
