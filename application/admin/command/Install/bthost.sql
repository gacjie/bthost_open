/*
 btHost Install SQL
 Date: 2020-09-14 20:21:54
*/

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for bth_admin
-- ----------------------------
DROP TABLE IF EXISTS `bth_admin`;
CREATE TABLE `bth_admin` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `username` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户名',
  `nickname` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '昵称',
  `password` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密码',
  `salt` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密码盐',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '头像',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '电子邮箱',
  `loginfailure` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '失败次数',
  `logintime` int(10) DEFAULT NULL COMMENT '登录时间',
  `loginip` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '登录IP',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `token` varchar(59) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'Session标识',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员表';

-- ----------------------------
-- Records of bth_admin
-- ----------------------------
INSERT INTO `bth_admin` VALUES ('1', 'admin', 'Admin', 'b64399441f7476f9b9f524d3bb72ecd2', 'ab70e8', '/assets/img/avatar.png', 'admin@admin.com', '0', '1600092348', '127.0.0.1', '1492186163', '1600092348', '40493cde-cf7a-4055-bd52-1cdd47fab530', 'normal');

-- ----------------------------
-- Table structure for bth_admin_log
-- ----------------------------
DROP TABLE IF EXISTS `bth_admin_log`;
CREATE TABLE `bth_admin_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `username` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '管理员名字',
  `url` varchar(1500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作页面',
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '日志标题',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '内容',
  `ip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'IP',
  `useragent` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'User-Agent',
  `createtime` int(10) DEFAULT NULL COMMENT '操作时间',
  PRIMARY KEY (`id`),
  KEY `name` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员日志表';

-- ----------------------------
-- Records of bth_admin_log
-- ----------------------------

-- ----------------------------
-- Table structure for bth_attachment
-- ----------------------------
DROP TABLE IF EXISTS `bth_attachment`;
CREATE TABLE `bth_attachment` (
  `id` int(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '物理路径',
  `imagewidth` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '宽度',
  `imageheight` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '高度',
  `imagetype` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '图片类型',
  `imageframes` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '图片帧数',
  `filename` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '文件名称',
  `filesize` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '文件大小',
  `mimetype` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'mime类型',
  `extparam` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '透传数据',
  `createtime` int(10) DEFAULT NULL COMMENT '创建日期',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `uploadtime` int(10) DEFAULT NULL COMMENT '上传时间',
  `storage` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'local' COMMENT '存储位置',
  `sha1` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件 sha1编码',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='附件表';

-- ----------------------------
-- Records of bth_attachment
-- ----------------------------
INSERT INTO `bth_attachment` VALUES ('1', '1', '1', '/logo.png', '440', '106', 'png', '0', 'logo.png', '23443', 'image/png', '', '1600096248', '1600096248', '1600096248', 'local', '1db5bc5ac92406375b9069292c59911fef99432a');
INSERT INTO `bth_attachment` VALUES ('2', '1', '1', '/static/images/login-bg1.jpg', '1920', '1200', 'jpg', '0', 'login-bg1.jpg', '880262', 'image/jpeg', '', '1600096137', '1600096137', '1600096137', 'local', '01f7d45e8936f63a199feb395ff384ba4652657c');
INSERT INTO `bth_attachment` VALUES ('3', '1', '1', '/static/images/login-bg2.jpg', '1440', '900', 'jpg', '0', 'login-bg2.jpg', '444492', 'image/jpeg', '', '1600096140', '1600096140', '1600096140', 'local', '47a3f3e94368e0550c2c0767609461442a9e1fa7');

-- ----------------------------
-- Table structure for bth_auth_group
-- ----------------------------
DROP TABLE IF EXISTS `bth_auth_group`;
CREATE TABLE `bth_auth_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父组别',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '组名',
  `rules` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '规则ID',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='分组表';

-- ----------------------------
-- Records of bth_auth_group
-- ----------------------------
INSERT INTO `bth_auth_group` VALUES ('1', '0', 'Admin group', '*', '1490883540', '149088354', 'normal');
INSERT INTO `bth_auth_group` VALUES ('2', '1', '二级管理组', '1,2,4,6,7,8,9,10,11,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,40,41,42,43,44,45,46,47,48,49,50,55,56,57,58,59,60,63,64,65,146,147,148,149,150,151,152,153,154,155,156,157,158,159,160,161,162,163,164,165,166,167,168,169,170,171,172,173,174,175,176,177,178,179,180,181,182,183,184,185,196,197,198,199,200,201,202,203,204,205,216,217,218,219,220,221,222,223,224,225,226,227,228,229,230,231,232,233,234,235,236,237,238,239,240,241,242,243,244,245,246,247,248,249,250,251,252,253,254,255,275,276,277,278,279,280,281,282,283,284,285,286,287,288,289,290,291,292,293,294,295,296,297,298,299,300,301,302,303,304,305,306,307,308,309,310,311,312,313,314,5,315,316,317,318', '1490883540', '1600061668', 'normal');
INSERT INTO `bth_auth_group` VALUES ('3', '2', 'Third group', '1,4,9,10,11,13,14,15,16,17,40,41,42,43,44,45,46,47,48,49,50,55,56,57,58,59,60,63,64,65,5', '1490883540', '1600061668', 'normal');
INSERT INTO `bth_auth_group` VALUES ('4', '1', 'Second group 2', '1,4,13,14,15,16,17,55,56,57,58,59,60,61,62,63,64,65', '1490883540', '1502205350', 'normal');
INSERT INTO `bth_auth_group` VALUES ('5', '2', 'Third group 2', '1,2,6,7,8,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34', '1490883540', '1600061668', 'normal');

-- ----------------------------
-- Table structure for bth_auth_group_access
-- ----------------------------
DROP TABLE IF EXISTS `bth_auth_group_access`;
CREATE TABLE `bth_auth_group_access` (
  `uid` int(10) unsigned NOT NULL COMMENT '会员ID',
  `group_id` int(10) unsigned NOT NULL COMMENT '级别ID',
  UNIQUE KEY `uid_group_id` (`uid`,`group_id`),
  KEY `uid` (`uid`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='权限分组表';

-- ----------------------------
-- Records of bth_auth_group_access
-- ----------------------------
INSERT INTO `bth_auth_group_access` VALUES ('1', '1');

-- ----------------------------
-- Table structure for bth_auth_rule
-- ----------------------------
DROP TABLE IF EXISTS `bth_auth_rule`;
CREATE TABLE `bth_auth_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('menu','file') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'file' COMMENT 'menu为菜单,file为权限节点',
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父ID',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '规则名称',
  `title` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '规则名称',
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '图标',
  `condition` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '条件',
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `ismenu` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否为菜单',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`) USING BTREE,
  KEY `pid` (`pid`),
  KEY `weigh` (`weigh`)
) ENGINE=InnoDB AUTO_INCREMENT=315 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='节点表';

-- ----------------------------
-- Records of bth_auth_rule
-- ----------------------------
INSERT INTO `bth_auth_rule` VALUES ('1', 'file', '0', 'dashboard', 'Dashboard', 'fa fa-dashboard', '', 'Dashboard tips', '1', '1497429920', '1497429920', '143', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('2', 'file', '0', 'general', 'General', 'fa fa-cogs', '', '', '1', '1497429920', '1497430169', '137', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('4', 'file', '0', 'addon', '插件管理', 'fa fa-rocket', '', 'Addon tips', '1', '1502035509', '1599800153', '0', 'hidden');
INSERT INTO `bth_auth_rule` VALUES ('5', 'file', '0', 'auth', 'Auth', 'fa fa-group', '', '', '1', '1497429920', '1497430092', '99', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('6', 'file', '2', 'general/config', 'Config', 'fa fa-cog', '', 'Config tips', '1', '1497429920', '1497430683', '60', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('7', 'file', '2', 'general/attachment', '附件管理', 'fa fa-file-image-o', '', 'Attachment tips', '1', '1497429920', '1598583197', '53', 'hidden');
INSERT INTO `bth_auth_rule` VALUES ('8', 'file', '2', 'general/profile', 'Profile', 'fa fa-user', '', '', '1', '1497429920', '1497429920', '34', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('9', 'file', '5', 'auth/admin', 'Admin', 'fa fa-user', '', 'Admin tips', '1', '1497429920', '1497430320', '118', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('10', 'file', '5', 'auth/adminlog', 'Admin log', 'fa fa-list-alt', '', 'Admin log tips', '1', '1497429920', '1497430307', '113', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('11', 'file', '5', 'auth/group', 'Group', 'fa fa-group', '', 'Group tips', '1', '1497429920', '1497429920', '109', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('12', 'file', '5', 'auth/rule', 'Rule', 'fa fa-bars', '', 'Rule tips', '1', '1497429920', '1497430581', '104', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('13', 'file', '1', 'dashboard/index', 'View', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '136', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('14', 'file', '1', 'dashboard/add', 'Add', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '135', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('15', 'file', '1', 'dashboard/del', 'Delete', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '133', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('16', 'file', '1', 'dashboard/edit', 'Edit', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '134', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('17', 'file', '1', 'dashboard/multi', 'Multi', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '132', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('18', 'file', '6', 'general/config/index', 'View', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '52', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('19', 'file', '6', 'general/config/add', 'Add', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '51', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('20', 'file', '6', 'general/config/edit', 'Edit', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '50', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('21', 'file', '6', 'general/config/del', 'Delete', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '49', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('22', 'file', '6', 'general/config/multi', 'Multi', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '48', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('23', 'file', '7', 'general/attachment/index', 'View', 'fa fa-circle-o', '', 'Attachment tips', '0', '1497429920', '1497429920', '59', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('24', 'file', '7', 'general/attachment/select', 'Select attachment', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '58', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('25', 'file', '7', 'general/attachment/add', 'Add', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '57', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('26', 'file', '7', 'general/attachment/edit', 'Edit', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '56', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('27', 'file', '7', 'general/attachment/del', 'Delete', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '55', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('28', 'file', '7', 'general/attachment/multi', 'Multi', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '54', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('29', 'file', '8', 'general/profile/index', 'View', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '33', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('30', 'file', '8', 'general/profile/update', 'Update profile', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '32', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('31', 'file', '8', 'general/profile/add', 'Add', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '31', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('32', 'file', '8', 'general/profile/edit', 'Edit', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '30', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('33', 'file', '8', 'general/profile/del', 'Delete', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '29', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('34', 'file', '8', 'general/profile/multi', 'Multi', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '28', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('40', 'file', '9', 'auth/admin/index', 'View', 'fa fa-circle-o', '', 'Admin tips', '0', '1497429920', '1497429920', '117', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('41', 'file', '9', 'auth/admin/add', 'Add', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '116', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('42', 'file', '9', 'auth/admin/edit', 'Edit', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '115', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('43', 'file', '9', 'auth/admin/del', 'Delete', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '114', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('44', 'file', '10', 'auth/adminlog/index', 'View', 'fa fa-circle-o', '', 'Admin log tips', '0', '1497429920', '1497429920', '112', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('45', 'file', '10', 'auth/adminlog/detail', 'Detail', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '111', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('46', 'file', '10', 'auth/adminlog/del', 'Delete', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '110', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('47', 'file', '11', 'auth/group/index', 'View', 'fa fa-circle-o', '', 'Group tips', '0', '1497429920', '1497429920', '108', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('48', 'file', '11', 'auth/group/add', 'Add', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '107', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('49', 'file', '11', 'auth/group/edit', 'Edit', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '106', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('50', 'file', '11', 'auth/group/del', 'Delete', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '105', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('51', 'file', '12', 'auth/rule/index', 'View', 'fa fa-circle-o', '', 'Rule tips', '0', '1497429920', '1497429920', '103', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('52', 'file', '12', 'auth/rule/add', 'Add', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '102', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('53', 'file', '12', 'auth/rule/edit', 'Edit', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '101', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('54', 'file', '12', 'auth/rule/del', 'Delete', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '100', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('55', 'file', '4', 'addon/index', 'View', 'fa fa-circle-o', '', 'Addon tips', '0', '1502035509', '1502035509', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('56', 'file', '4', 'addon/add', 'Add', 'fa fa-circle-o', '', '', '0', '1502035509', '1502035509', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('57', 'file', '4', 'addon/edit', 'Edit', 'fa fa-circle-o', '', '', '0', '1502035509', '1502035509', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('58', 'file', '4', 'addon/del', 'Delete', 'fa fa-circle-o', '', '', '0', '1502035509', '1502035509', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('59', 'file', '4', 'addon/downloaded', 'Local addon', 'fa fa-circle-o', '', '', '0', '1502035509', '1502035509', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('60', 'file', '4', 'addon/state', 'Update state', 'fa fa-circle-o', '', '', '0', '1502035509', '1502035509', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('63', 'file', '4', 'addon/config', 'Setting', 'fa fa-circle-o', '', '', '0', '1502035509', '1502035509', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('64', 'file', '4', 'addon/refresh', 'Refresh', 'fa fa-circle-o', '', '', '0', '1502035509', '1502035509', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('65', 'file', '4', 'addon/multi', 'Multi', 'fa fa-circle-o', '', '', '0', '1502035509', '1502035509', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('146', 'file', '298', 'ippools', 'IP池', 'fa fa-circle-o', '', '', '1', '1597303818', '1600062013', '2', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('147', 'file', '146', 'ippools/import', 'Import', 'fa fa-circle-o', '', '', '0', '1597303818', '1597303818', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('148', 'file', '146', 'ippools/index', '查看', 'fa fa-circle-o', '', '', '0', '1597303818', '1597303818', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('149', 'file', '146', 'ippools/recyclebin', '回收站', 'fa fa-circle-o', '', '', '0', '1597303818', '1597303818', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('150', 'file', '146', 'ippools/add', '添加', 'fa fa-circle-o', '', '', '0', '1597303818', '1597303818', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('151', 'file', '146', 'ippools/edit', '编辑', 'fa fa-circle-o', '', '', '0', '1597303818', '1597303818', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('152', 'file', '146', 'ippools/del', '删除', 'fa fa-circle-o', '', '', '0', '1597303818', '1597303818', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('153', 'file', '146', 'ippools/destroy', '真实删除', 'fa fa-circle-o', '', '', '0', '1597303818', '1597303818', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('154', 'file', '146', 'ippools/restore', '还原', 'fa fa-circle-o', '', '', '0', '1597303818', '1597303818', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('155', 'file', '146', 'ippools/multi', '批量更新', 'fa fa-circle-o', '', '', '0', '1597303818', '1597303818', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('156', 'file', '298', 'ipaddress', 'IP地址', 'fa fa-circle-o', '', '', '1', '1597303837', '1600062009', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('157', 'file', '156', 'ipaddress/import', 'Import', 'fa fa-circle-o', '', '', '0', '1597303837', '1597303837', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('158', 'file', '156', 'ipaddress/index', '查看', 'fa fa-circle-o', '', '', '0', '1597303837', '1597303837', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('159', 'file', '156', 'ipaddress/recyclebin', '回收站', 'fa fa-circle-o', '', '', '0', '1597303837', '1597303837', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('160', 'file', '156', 'ipaddress/add', '添加', 'fa fa-circle-o', '', '', '0', '1597303837', '1597303837', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('161', 'file', '156', 'ipaddress/edit', '编辑', 'fa fa-circle-o', '', '', '0', '1597303837', '1597303837', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('162', 'file', '156', 'ipaddress/del', '删除', 'fa fa-circle-o', '', '', '0', '1597303837', '1597303837', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('163', 'file', '156', 'ipaddress/destroy', '真实删除', 'fa fa-circle-o', '', '', '0', '1597303837', '1597303837', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('164', 'file', '156', 'ipaddress/restore', '还原', 'fa fa-circle-o', '', '', '0', '1597303837', '1597303837', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('165', 'file', '156', 'ipaddress/multi', '批量更新', 'fa fa-circle-o', '', '', '0', '1597303837', '1597303837', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('166', 'file', '216', 'domainpools', '域名池', 'fa fa-circle-o', '', '', '1', '1597304085', '1600062174', '6', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('167', 'file', '166', 'domainpools/import', 'Import', 'fa fa-circle-o', '', '', '0', '1597304085', '1597304085', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('168', 'file', '166', 'domainpools/index', '查看', 'fa fa-circle-o', '', '', '0', '1597304085', '1597304085', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('169', 'file', '166', 'domainpools/recyclebin', '回收站', 'fa fa-circle-o', '', '', '0', '1597304085', '1597304085', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('170', 'file', '166', 'domainpools/add', '添加', 'fa fa-circle-o', '', '', '0', '1597304085', '1597304085', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('171', 'file', '166', 'domainpools/edit', '编辑', 'fa fa-circle-o', '', '', '0', '1597304085', '1597304085', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('172', 'file', '166', 'domainpools/del', '删除', 'fa fa-circle-o', '', '', '0', '1597304085', '1597304085', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('173', 'file', '166', 'domainpools/destroy', '真实删除', 'fa fa-circle-o', '', '', '0', '1597304085', '1597304085', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('174', 'file', '166', 'domainpools/restore', '还原', 'fa fa-circle-o', '', '', '0', '1597304085', '1597304085', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('175', 'file', '166', 'domainpools/multi', '批量更新', 'fa fa-circle-o', '', '', '0', '1597304085', '1597304085', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('176', 'file', '216', 'domainlist', '域名列表', 'fa fa-circle-o', '', '', '1', '1597304154', '1600062172', '7', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('177', 'file', '176', 'domainlist/import', 'Import', 'fa fa-circle-o', '', '', '0', '1597304154', '1597305931', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('178', 'file', '176', 'domainlist/index', '查看', 'fa fa-circle-o', '', '', '0', '1597304154', '1597305931', '11', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('179', 'file', '176', 'domainlist/recyclebin', '回收站', 'fa fa-circle-o', '', '', '0', '1597304154', '1597305931', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('180', 'file', '176', 'domainlist/add', '添加', 'fa fa-circle-o', '', '', '0', '1597304154', '1597305931', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('181', 'file', '176', 'domainlist/edit', '编辑', 'fa fa-circle-o', '', '', '0', '1597304154', '1597305931', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('182', 'file', '176', 'domainlist/del', '删除', 'fa fa-circle-o', '', '', '0', '1597304154', '1597305931', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('183', 'file', '176', 'domainlist/destroy', '真实删除', 'fa fa-circle-o', '', '', '0', '1597304154', '1597305931', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('184', 'file', '176', 'domainlist/restore', '还原', 'fa fa-circle-o', '', '', '0', '1597304154', '1597305931', '11', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('185', 'file', '176', 'domainlist/multi', '批量更新', 'fa fa-circle-o', '', '', '0', '1597304154', '1597305931', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('196', 'file', '300', 'plans', '资源套餐', 'fa fa-circle-o', '', '', '1', '1597365753', '1600062035', '15', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('197', 'file', '196', 'plans/import', 'Import', 'fa fa-circle-o', '', '', '0', '1597365753', '1597365753', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('198', 'file', '196', 'plans/index', '查看', 'fa fa-circle-o', '', '', '0', '1597365753', '1597365753', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('199', 'file', '196', 'plans/recyclebin', '回收站', 'fa fa-circle-o', '', '', '0', '1597365753', '1597365753', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('200', 'file', '196', 'plans/add', '添加', 'fa fa-circle-o', '', '', '0', '1597365753', '1597365753', '11', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('201', 'file', '196', 'plans/edit', '编辑', 'fa fa-circle-o', '', '', '0', '1597365753', '1597365753', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('202', 'file', '196', 'plans/del', '删除', 'fa fa-circle-o', '', '', '0', '1597365753', '1597365753', '11', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('203', 'file', '196', 'plans/destroy', '真实删除', 'fa fa-circle-o', '', '', '0', '1597365753', '1597365753', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('204', 'file', '196', 'plans/restore', '还原', 'fa fa-circle-o', '', '', '0', '1597365753', '1597365753', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('205', 'file', '196', 'plans/multi', '批量更新', 'fa fa-circle-o', '', '', '0', '1597365753', '1597365753', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('216', 'file', '0', 'domains', '域名管理', 'fa fa-internet-explorer', '', '', '1', '1597379212', '1600062072', '10', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('217', 'file', '216', 'domain/import', 'Import', 'fa fa-circle-o', '', '', '0', '1597379212', '1597379681', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('218', 'file', '216', 'domain/index', '查看', 'fa fa-circle-o', '', '', '0', '1597379212', '1597379681', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('219', 'file', '216', 'domain/recyclebin', '回收站', 'fa fa-circle-o', '', '', '0', '1597379212', '1597379681', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('220', 'file', '216', 'domain/add', '添加', 'fa fa-circle-o', '', '', '0', '1597379212', '1597379681', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('221', 'file', '216', 'domain/edit', '编辑', 'fa fa-circle-o', '', '', '0', '1597379212', '1597379681', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('222', 'file', '216', 'domain/del', '删除', 'fa fa-circle-o', '', '', '0', '1597379212', '1597379681', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('223', 'file', '216', 'domain/destroy', '真实删除', 'fa fa-circle-o', '', '', '0', '1597379212', '1597379681', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('224', 'file', '216', 'domain/restore', '还原', 'fa fa-circle-o', '', '', '0', '1597379212', '1597379681', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('225', 'file', '216', 'domain/multi', '批量更新', 'fa fa-circle-o', '', '', '0', '1597379212', '1597379681', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('226', 'file', '300', 'host', '主机列表', 'fa fa-circle-o', '', '', '1', '1597380531', '1600062132', '12', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('227', 'file', '226', 'host/import', 'Import', 'fa fa-circle-o', '', '', '0', '1597380531', '1597380765', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('228', 'file', '226', 'host/index', '查看', 'fa fa-circle-o', '', '', '0', '1597380531', '1597380765', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('229', 'file', '226', 'host/recyclebin', '回收站', 'fa fa-circle-o', '', '', '0', '1597380531', '1597380765', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('230', 'file', '226', 'host/add', '新建主机', 'fa fa-circle-o', '', '', '0', '1597380531', '1600061718', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('231', 'file', '226', 'host/edit', '编辑', 'fa fa-circle-o', '', '', '0', '1597380531', '1597380765', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('232', 'file', '226', 'host/del', '删除', 'fa fa-circle-o', '', '', '0', '1597380531', '1597380765', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('233', 'file', '226', 'host/destroy', '真实删除', 'fa fa-circle-o', '', '', '0', '1597380531', '1597380765', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('234', 'file', '226', 'host/restore', '还原', 'fa fa-circle-o', '', '', '0', '1597380531', '1597380765', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('235', 'file', '226', 'host/multi', '批量更新', 'fa fa-circle-o', '', '', '0', '1597380531', '1597380765', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('236', 'file', '300', 'ftp', 'FTP', 'fa fa-circle-o', '', '', '1', '1597542665', '1600062042', '2', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('237', 'file', '236', 'ftp/import', 'Import', 'fa fa-circle-o', '', '', '0', '1597542665', '1597543306', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('238', 'file', '236', 'ftp/index', '查看', 'fa fa-circle-o', '', '', '0', '1597542665', '1597543306', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('239', 'file', '236', 'ftp/recyclebin', '回收站', 'fa fa-circle-o', '', '', '0', '1597542665', '1597543306', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('240', 'file', '236', 'ftp/add', '添加', 'fa fa-circle-o', '', '', '0', '1597542665', '1597543306', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('241', 'file', '236', 'ftp/edit', '编辑', 'fa fa-circle-o', '', '', '0', '1597542665', '1597543306', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('242', 'file', '236', 'ftp/del', '删除', 'fa fa-circle-o', '', '', '0', '1597542665', '1597543306', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('243', 'file', '236', 'ftp/destroy', '真实删除', 'fa fa-circle-o', '', '', '0', '1597542665', '1597543306', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('244', 'file', '236', 'ftp/restore', '还原', 'fa fa-circle-o', '', '', '0', '1597542665', '1597543306', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('245', 'file', '236', 'ftp/multi', '批量更新', 'fa fa-circle-o', '', '', '0', '1597542665', '1597543306', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('246', 'file', '300', 'sql', '数据库', 'fa fa-circle-o', '', '', '1', '1597542670', '1600061989', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('247', 'file', '246', 'sql/import', 'Import', 'fa fa-circle-o', '', '', '0', '1597542670', '1597543275', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('248', 'file', '246', 'sql/index', '查看', 'fa fa-circle-o', '', '', '0', '1597542670', '1597543275', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('249', 'file', '246', 'sql/recyclebin', '回收站', 'fa fa-circle-o', '', '', '0', '1597542670', '1597543275', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('250', 'file', '246', 'sql/add', '添加', 'fa fa-circle-o', '', '', '0', '1597542670', '1597543275', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('251', 'file', '246', 'sql/edit', '编辑', 'fa fa-circle-o', '', '', '0', '1597542670', '1597543275', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('252', 'file', '246', 'sql/del', '删除', 'fa fa-circle-o', '', '', '0', '1597542670', '1597543275', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('253', 'file', '246', 'sql/destroy', '真实删除', 'fa fa-circle-o', '', '', '0', '1597542670', '1597543275', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('254', 'file', '246', 'sql/restore', '还原', 'fa fa-circle-o', '', '', '0', '1597542670', '1597543275', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('255', 'file', '246', 'sql/multi', '批量更新', 'fa fa-circle-o', '', '', '0', '1597542670', '1597543275', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('275', 'file', '0', 'user', '用户管理', 'fa fa-list', '', '', '1', '1597552121', '1600062100', '100', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('276', 'file', '275', 'user/user', '用户列表', 'fa fa-user', '', '', '1', '1597552121', '1600062200', '20', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('277', 'file', '276', 'user/user/import', 'Import', 'fa fa-circle-o', '', '', '0', '1597552121', '1597552121', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('278', 'file', '276', 'user/user/index', '查看', 'fa fa-circle-o', '', '', '0', '1597552121', '1597552121', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('279', 'file', '276', 'user/user/recyclebin', '回收站', 'fa fa-circle-o', '', '', '0', '1597552121', '1597552121', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('280', 'file', '276', 'user/user/add', '添加', 'fa fa-circle-o', '', '', '0', '1597552121', '1597552121', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('281', 'file', '276', 'user/user/edit', '编辑', 'fa fa-circle-o', '', '', '0', '1597552121', '1597552121', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('282', 'file', '276', 'user/user/del', '删除', 'fa fa-circle-o', '', '', '0', '1597552121', '1597552121', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('283', 'file', '276', 'user/user/destroy', '真实删除', 'fa fa-circle-o', '', '', '0', '1597552121', '1597552121', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('284', 'file', '276', 'user/user/restore', '还原', 'fa fa-circle-o', '', '', '0', '1597552121', '1597552121', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('285', 'file', '276', 'user/user/multi', '批量更新', 'fa fa-circle-o', '', '', '0', '1597552121', '1597552121', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('286', 'file', '275', 'user/group', '用户分组', 'fa fa-users', '', '', '1', '1597552563', '1597555238', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('287', 'file', '286', 'user/group/add', 'Add', 'fa fa-circle-o', '', '', '0', '1597552602', '1597552602', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('288', 'file', '286', 'user/group/edit', 'Edit', 'fa fa-circle-o', '', '', '0', '1597552617', '1597552617', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('289', 'file', '286', 'user/group/index', 'View', 'fa fa-circle-o', '', '', '0', '1597552629', '1597552629', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('290', 'file', '286', 'user/group/del', 'Del', 'fa fa-circle-o', '', '', '0', '1597552642', '1597552642', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('291', 'file', '286', 'user/group/multi', 'Multi', 'fa fa-circle-o', '', '', '0', '1597552656', '1597552656', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('292', 'file', '275', 'user/rule', '用户规则', 'fa fa-circle-o', '', '', '1', '1597552690', '1597555232', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('293', 'file', '292', 'user/rule/index', 'View', 'fa fa-circle-o', '', '', '0', '1597552703', '1597552703', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('294', 'file', '292', 'user/rule/del', 'Del', 'fa fa-circle-o', '', '', '0', '1597552754', '1597552754', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('295', 'file', '292', 'user/rule/add', 'Add', 'fa fa-circle-o', '', '', '0', '1597552767', '1597552767', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('296', 'file', '292', 'user/rule/edit', 'Edit', 'fa fa-circle-o', '', '', '0', '1597552780', '1597552780', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('297', 'file', '292', 'user/rule/multi', 'Multi', 'fa fa-circle-o', '', '', '0', '1597552790', '1597552790', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('298', 'file', '0', 'ip', 'IP管理', 'fa fa-gg', '', '', '1', '1597553422', '1600062088', '14', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('299', 'file', '216', 'domain', '域名管理', 'fa fa-circle-o', '', '', '1', '1597553513', '1600062170', '5', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('300', 'file', '0', 'vhost', '主机管理', 'fa fa-tasks', '', '', '1', '1597553881', '1600062121', '80', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('301', 'file', '2', 'general/queue/index', '计划任务', 'fa fa-globe fa-fw', '', '', '1', '1599106319', '1599792441', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('302', 'file', '301', 'general/queue/edit', '编辑', 'fa fa-circle-o', '', '', '0', '1599792801', '1599792801', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('303', 'file', '301', 'general/queue/detail', '日志', 'fa fa-circle-o', '', '', '0', '1599792882', '1599792882', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('304', 'file', '301', 'general/queue/quelogclear', '清空日志', 'fa fa-circle-o', '', '', '0', '1599796092', '1599796092', '11', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('305', 'file', '301', 'general/queue/deployment', '一键监控', 'fa fa-circle-o', '', '', '0', '1600061043', '1600064565', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('306', 'file', '276', 'user/user/info', '用户信息', 'fa fa-circle-o', '', '', '0', '1600061127', '1600061127', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('307', 'file', '276', 'user/user/login', '一键登录', 'fa fa-circle-o', '', '', '0', '1600061145', '1600061145', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('308', 'file', '299', 'domain/config', 'dnspod配置', 'fa fa-circle-o', '', '', '0', '1600061261', '1600061261', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('309', 'file', '299', 'domain/detail', '域名详情', 'fa fa-circle-o', '', '', '0', '1600061277', '1600061277', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('310', 'file', '176', 'domainlist/audit', '域名审核', 'fa fa-circle-o', '', '', '0', '1600061318', '1600061318', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('311', 'file', '226', 'host/login', '一键登录', 'fa fa-circle-o', '', '', '0', '1600061353', '1600061353', '0', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('312', 'file', '226', 'host/add_local', '添加主机', 'fa fa-circle-o', '', '', '0', '1600061382', '1600061382', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('313', 'file', '226', 'host/repair', '批量操作', 'fa fa-circle-o', '', '', '0', '1600061424', '1600061424', '1', 'normal');
INSERT INTO `bth_auth_rule` VALUES ('314', 'file', '196', 'plans/copy', '复制套餐', 'fa fa-circle-o', '', '', '0', '1600061475', '1600061475', '1', 'normal');

-- ----------------------------
-- Table structure for bth_config
-- ----------------------------
DROP TABLE IF EXISTS `bth_config`;
CREATE TABLE `bth_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '变量名',
  `group` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分组',
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '变量标题',
  `tip` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '变量描述',
  `type` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '类型:string,text,int,bool,array,datetime,date,file',
  `value` text COLLATE utf8mb4_unicode_ci COMMENT '变量值',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '变量字典数据',
  `rule` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '验证规则',
  `extend` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '扩展属性',
  `setting` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '配置',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置';

-- ----------------------------
-- Records of bth_config
-- ----------------------------
INSERT INTO `bth_config` VALUES ('1', 'name', 'basic', 'Site name', '请填写站点名称', 'string', 'btHost', '', 'required', '', null);
INSERT INTO `bth_config` VALUES ('2', 'description', 'basic', 'description', '', 'string', '虚拟主机管理系统，完美的解决您的需求，多种多样的使用场景', '', '', '', null);
INSERT INTO `bth_config` VALUES ('3', 'logo', 'basic', 'logo', '', 'image', '/logo.png', '', '', '', null);
INSERT INTO `bth_config` VALUES ('4', 'beian', 'basic', 'Beian', '粤ICP备15000000号-1', 'string', '', '', '', '', null);
INSERT INTO `bth_config` VALUES ('5', 'timezone', 'basic', 'Timezone', '', 'string', 'Asia/Shanghai', '', 'required', '', null);
INSERT INTO `bth_config` VALUES ('6', 'cdnurl', 'basic', 'Cdn url', '如果静态资源使用第三方云储存请配置该值', 'string', '', '', '', '', null);
INSERT INTO `bth_config` VALUES ('7', 'version', 'basic', 'Version', '如果静态资源有变动请重新配置该值', 'string', '1.0.1', '', 'required', '', null);
INSERT INTO `bth_config` VALUES ('8', 'languages', 'basic', 'Languages', '', 'array', '{\"backend\":\"zh-cn\",\"frontend\":\"zh-cn\"}', '', 'required', '', null);
INSERT INTO `bth_config` VALUES ('9', 'forbiddenip', 'basic', 'Forbidden ip', '一行一条记录', 'text', '', '', '', '', null);
INSERT INTO `bth_config` VALUES ('10', 'configgroup', 'dictionary', 'Config group', '', 'array', '{\"basic\":\"Basic\",\"email\":\"Email\",\"server\":\"Server\",\"config\":\"Config\",\"secret\":\"Secret\",\"domain\":\"Domain\",\"notice\":\"Notice\",\"dictionary\":\"Dictionary\",\"personalization\":\"Personalization\"}', '', '', '', '');
INSERT INTO `bth_config` VALUES ('11', 'mail_type', 'email', 'Mail type', '选择邮件发送方式', 'select', '1', '[\"请选择\",\"SMTP\",\"Mail\"]', '', '', '');
INSERT INTO `bth_config` VALUES ('12', 'mail_smtp_host', 'email', 'Mail smtp host', '错误的配置发送邮件会导致服务器超时', 'string', 'smtp.qq.com', '', '', '', '');
INSERT INTO `bth_config` VALUES ('13', 'mail_smtp_port', 'email', 'Mail smtp port', '(不加密默认25,SSL默认465,TLS默认587)', 'string', '465', '', '', '', '');
INSERT INTO `bth_config` VALUES ('14', 'mail_smtp_user', 'email', 'Mail smtp user', '（填写完整用户名）', 'string', '10000', '', '', '', '');
INSERT INTO `bth_config` VALUES ('15', 'mail_smtp_pass', 'email', 'Mail smtp password', '（填写您的密码）', 'string', 'password', '', '', '', '');
INSERT INTO `bth_config` VALUES ('16', 'mail_verify_type', 'email', 'Mail vertify type', '（SMTP验证方式[推荐SSL]）', 'select', '2', '[\"无\",\"TLS\",\"SSL\"]', '', '', '');
INSERT INTO `bth_config` VALUES ('17', 'mail_from', 'email', 'Mail from', '', 'string', '10000@qq.com', '', '', '', '');
INSERT INTO `bth_config` VALUES ('18', 'email', 'basic', 'AdminEmail', '站长邮箱', 'string', '', '', '', '', null);
INSERT INTO `bth_config` VALUES ('19', 'ftqq_sckey', 'basic', 'ftqq_sckey', '官网：https://sc.ftqq.com/', 'string', '', '', '', '', null);
INSERT INTO `bth_config` VALUES ('21', 'access_token', 'secret', 'access_token', '财务三方密钥', 'string', '', '', '', '', '');
INSERT INTO `bth_config` VALUES ('22', 'api_returnip', 'secret', 'api_returnip', '一行一条记录<br/>不设置将允许所有人通讯', 'text', '', '', '', '', '');
INSERT INTO `bth_config` VALUES ('23', 'queue_key', 'secret', 'queue_key', '监控密钥，用于执行计划任务', 'string', 'dA0IwO', '', '', '', '');
INSERT INTO `bth_config` VALUES ('24', 'expire_action', 'config', 'expire_action', '', 'select', 'recycle', '{\"recycle\":\"放入回收站\",\"delete\":\"删除并清除\"}', '', '', '');
INSERT INTO `bth_config` VALUES ('25', 'recycle_delete', 'config', 'recycle_delete', '达到指定天数后删除站点并清除所有数据', 'number', '7', '', '', '', '');
INSERT INTO `bth_config` VALUES ('26', 'api_token', 'server', 'api_token', '宝塔面板API接口密钥，如不修改请为空', 'string', '', '', '', '', null);
INSERT INTO `bth_config` VALUES ('27', 'api_port', 'server', 'api_port', '宝塔面板访问端口', 'number', '8888', '', '', '', null);
INSERT INTO `bth_config` VALUES ('28', 'excess_panel', 'config', 'excess_panel', '资源超出是否停用管理面板', 'select', '0', '{\"0\":\"不停用\",\"1\":\"停用\"}', '', '', '');
INSERT INTO `bth_config` VALUES ('29', 'main_center_notice', 'notice', 'main_center_notice', '支持html', 'text', '', '', '', '', '');
INSERT INTO `bth_config` VALUES ('30', 'main_left_notice', 'notice', 'main_left_notice', '支持html', 'text', '', '', '', '', '');
INSERT INTO `bth_config` VALUES ('31', 'main_right_notice', 'notice', 'main_right_notice', '支持html', 'text', '', '', '', '', '');
INSERT INTO `bth_config` VALUES ('32', 'public_head_notice', 'notice', 'public_head_notice', '支持html', 'text', '', '', '', '', '');
INSERT INTO `bth_config` VALUES ('33', 'iframe_cache', 'personalization', 'iframe_cache', '控制台iframe标签缓存', 'select', 'false', '{\"false\":\"不缓存\",\"true\":\"缓存\"}', '', '', '');
INSERT INTO `bth_config` VALUES ('34', 'console_css', 'personalization', 'console_css', '添加 LESS/CSS 代码以自定义控制台外观样式，此设置将覆盖 btHots 默认样式，不需要带style标签', 'text', '', '', '', '', '');
INSERT INTO `bth_config` VALUES ('35', 'console_head', 'personalization', 'console_head', '添加显示于页面顶部、位于 控制台 默认页眉上方的 HTML 代码。', 'text', '', '', '', '', '');
INSERT INTO `bth_config` VALUES ('36', 'console_foot', 'personalization', 'console_foot', '添加显示于页面底部的 HTML 代码。', 'text', '', '', '', '', '');
INSERT INTO `bth_config` VALUES ('37', 'login_bg', 'personalization', 'login_bg', '', 'image', '/static/images/login-bg1.jpg', '', '', '', '');
INSERT INTO `bth_config` VALUES ('38', 'ftp_server', 'server', 'ftp_server', '填写FTP服务器IP，请勿带ftp协议ftp://', 'string', '', '', '', '', null);
INSERT INTO `bth_config` VALUES ('39', 'ftp_port', 'server', 'ftp_port', 'FTP服务端口， 默认为21', 'number', '21', '', '', '', null);
INSERT INTO `bth_config` VALUES ('40', 'ftp_type', 'server', 'ftp_type', '', 'select', 'true', '{\"true\":\"SSL\",\"none\":\"无\"}', '', '', '{\"table\":\"\",\"conditions\":\"\",\"key\":\"\",\"value\":\"\"}');
INSERT INTO `bth_config` VALUES ('42', 'dnspod_analysis_type', 'domain', 'dnspod_analysis_type', 'A解析用于解析到IP，CNAME解析用于解析到域名地址', 'select', 'A', '{\"A\":\"A\",\"CNAME\":\"CNAME\"}', '', '', '');
INSERT INTO `bth_config` VALUES ('43', 'dnspod_analysis_url', 'domain', 'dnspod_analysis_url', '域名解析地址，可填写服务器公网IP，也可以填写指定的域名', 'string', '', '', '', '', '');
INSERT INTO `bth_config` VALUES ('44', 'default_analysis', 'domain', 'default_analysis', '', 'select', '0', '[\"自身域名\",\"解析地址\"]', '', '', '');
INSERT INTO `bth_config` VALUES ('45', 'analysis_desc', 'domain', 'analysis_desc', '域名绑定页面额外说明内容,支持html', 'text', '', '', '', '', '');
INSERT INTO `bth_config` VALUES ('46', 'split_size', 'server', 'split_size', '在线文件管理器上传文件分片大小，可根据服务器上行带宽适当调整，提升体验', 'number', '10', '', '', '', null);
INSERT INTO `bth_config` VALUES ('47', 'fixedpage', 'basic', 'Fixed page', '请尽量输入左侧菜单栏存在的链接', 'string', 'dashboard', '', 'required', '', null);
INSERT INTO `bth_config` VALUES ('48', 'phpmyadmin', 'server', 'phpmyadmin', '', 'string', '', '', '', '', null);
INSERT INTO `bth_config` VALUES ('49', 'debug', 'basic', 'debug', '网站需要调试时开启此选项', 'switch', '0', '', '', '', '{\"table\":\"\",\"conditions\":\"\",\"key\":\"\",\"value\":\"\"}');
INSERT INTO `bth_config` VALUES ('50', 'status', 'basic', 'status', '网站维护', 'switch', '0', '', '', '', '{\"table\":\"\",\"conditions\":\"\",\"key\":\"\",\"value\":\"\"}');

-- ----------------------------
-- Table structure for bth_domain
-- ----------------------------
DROP TABLE IF EXISTS `bth_domain`;
CREATE TABLE `bth_domain` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `domain` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '域名',
  `domainpools_id` int(10) NOT NULL COMMENT '域名池',
  `dnspod` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0' COMMENT 'Dnspod智能解析:0=禁用,1=启用',
  `dnspod_id` int(10) DEFAULT '0' COMMENT 'dnspod域名id',
  `createtime` int(10) NOT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `deletetime` int(10) DEFAULT NULL COMMENT '删除时间',
  `status` enum('normal','hidden','locked') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='域名列表';

-- ----------------------------
-- Records of bth_domain
-- ----------------------------
INSERT INTO `bth_domain` VALUES ('1', 'test.com', '1', '0', '0', '1597304582', '1599038709', null, 'normal');

-- ----------------------------
-- Table structure for bth_domainlist
-- ----------------------------
DROP TABLE IF EXISTS `bth_domainlist`;
CREATE TABLE `bth_domainlist` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `domain` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '域名',
  `vhost_id` int(10) NOT NULL COMMENT '主机',
  `domain_id` int(10) DEFAULT NULL COMMENT '域名id',
  `dnspod_record` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'dnspod解析值',
  `dnspod_record_id` int(10) DEFAULT NULL COMMENT 'dnspod记录值ID',
  `dnspod_domain_id` int(10) DEFAULT NULL COMMENT 'dnspod域名ID',
  `dir` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '目录',
  `createtime` int(10) NOT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `deletetime` int(10) DEFAULT NULL COMMENT '删除时间',
  `status` enum('0','1','2') COLLATE utf8_unicode_ci NOT NULL DEFAULT '1' COMMENT '状态:0=未审核,1=已审核,2=已驳回',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='域名管理';

-- ----------------------------
-- Records of bth_domainlist
-- ----------------------------

-- ----------------------------
-- Table structure for bth_domainpools
-- ----------------------------
DROP TABLE IF EXISTS `bth_domainpools`;
CREATE TABLE `bth_domainpools` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '名称',
  `tag` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '标识',
  `content` text COLLATE utf8_unicode_ci COMMENT '描述',
  `createtime` int(10) NOT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `deletetime` int(10) DEFAULT NULL COMMENT '删除时间',
  `status` enum('normal','hidden') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='域名池';

-- ----------------------------
-- Records of bth_domainpools
-- ----------------------------
INSERT INTO `bth_domainpools` VALUES ('1', '泛解析域名', 'test', '泛解析域名', '1597304115', '1599038159', null, 'normal');

-- ----------------------------
-- Table structure for bth_domain_block
-- ----------------------------
DROP TABLE IF EXISTS `bth_domain_block`;
CREATE TABLE `bth_domain_block` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain` varchar(255) NOT NULL COMMENT '阻拦域名',
  `createtime` int(11) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(11) DEFAULT NULL COMMENT '更新时间',
  `deletetime` int(10) DEFAULT NULL,
  `notice` text COMMENT '说明',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `domain` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='域名阻拦名单表';

-- ----------------------------
-- Table structure for bth_ftp
-- ----------------------------
DROP TABLE IF EXISTS `bth_ftp`;
CREATE TABLE `bth_ftp` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `vhost_id` int(10) NOT NULL DEFAULT '0' COMMENT '主机',
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '用户名',
  `password` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '密码',
  `createtime` int(10) NOT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `deletetime` int(10) DEFAULT NULL COMMENT '删除时间',
  `status` enum('normal','hidden') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='FTP';

-- ----------------------------
-- Records of bth_ftp
-- ----------------------------

-- ----------------------------
-- Table structure for bth_host
-- ----------------------------
DROP TABLE IF EXISTS `bth_host`;
CREATE TABLE `bth_host` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL COMMENT '用户',
  `sort_id` int(10) DEFAULT NULL COMMENT '分类',
  `bt_id` int(10) NOT NULL COMMENT '宝塔ID',
  `bt_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '主机名',
  `site_size` int(10) NOT NULL DEFAULT '0' COMMENT '站点使用量(M)',
  `site_max` int(10) NOT NULL DEFAULT '0' COMMENT '站点大小(M):0=无限制',
  `flow_size` int(10) NOT NULL DEFAULT '0' COMMENT '流量使用量(M)',
  `flow_max` int(10) NOT NULL DEFAULT '0' COMMENT '流量大小(M)',
  `sql_size` int(10) NOT NULL DEFAULT '0' COMMENT '数据库使用量(M)',
  `sql_max` int(10) NOT NULL DEFAULT '0' COMMENT '数据库大小(M)',
  `ip_address` text COLLATE utf8_unicode_ci COMMENT 'IP地址',
  `domain_max` int(10) NOT NULL DEFAULT '0' COMMENT '域名绑定数',
  `is_audit` tinyint(1) NOT NULL DEFAULT '0' COMMENT '域名绑定审核',
  `check_time` int(10) DEFAULT NULL COMMENT '最后检查时间',
  `web_back_num` int(10) NOT NULL DEFAULT '0' COMMENT '网站备份数',
  `sql_back_num` int(10) NOT NULL DEFAULT '0' COMMENT '数据库备份数',
  `is_vsftpd` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0' COMMENT 'vsftpd:0=否,1=是',
  `notice` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '站点备注',
  `createtime` int(10) NOT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `deletetime` int(10) DEFAULT NULL COMMENT '删除时间',
  `endtime` int(10) NOT NULL DEFAULT '0' COMMENT '到期时间',
  `status` enum('normal','stop','locked','expired','excess','error') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '状态:normal=运行,stop=暂停,locked=锁定,expired=过期,excess=超量,error=异常',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='主机管理';

-- ----------------------------
-- Records of bth_host
-- ----------------------------

-- ----------------------------
-- Table structure for bth_ipaddress
-- ----------------------------
DROP TABLE IF EXISTS `bth_ipaddress`;
CREATE TABLE `bth_ipaddress` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `ip` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'IP',
  `ippools_id` int(10) NOT NULL COMMENT 'IP池',
  `mask` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '掩码',
  `gateway` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '网关',
  `createtime` int(10) NOT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `deletetime` int(10) DEFAULT NULL COMMENT '删除时间',
  `status` enum('normal','hidden','locked') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='IP地址';

-- ----------------------------
-- Records of bth_ipaddress
-- ----------------------------

INSERT INTO `bth_ipaddress` (`id`, `ip`, `ippools_id`, `mask`, `gateway`, `createtime`, `updatetime`, `deletetime`, `status`) VALUES ('1', '127.0.0.1', '1', '255.255.255.0', '192.168.1.0', '1597804165', '1599029813', NULL, 'normal');

-- ----------------------------
-- Table structure for bth_ippools
-- ----------------------------
DROP TABLE IF EXISTS `bth_ippools`;
CREATE TABLE `bth_ippools` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '名称',
  `tag` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '标识',
  `content` text COLLATE utf8_unicode_ci COMMENT '描述',
  `createtime` int(10) NOT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `deletetime` int(10) DEFAULT NULL COMMENT '删除时间',
  `status` enum('normal','hidden') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='IP池';

-- ----------------------------
-- Records of bth_ippools
-- ----------------------------

INSERT INTO `bth_ippools` (`id`, `name`, `tag`, `content`, `createtime`, `updatetime`, `deletetime`, `status`) VALUES ('1', '测试1', 'test1', '这是测试1的IP池\r\n', '1597303276', '1597303276', NULL, 'normal');

-- ----------------------------
-- Table structure for bth_plans
-- ----------------------------
DROP TABLE IF EXISTS `bth_plans`;
CREATE TABLE `bth_plans` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '名称',
  `value` text COLLATE utf8_unicode_ci COMMENT '参数值',
  `createtime` int(10) NOT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `deletetime` int(10) DEFAULT NULL COMMENT '删除时间',
  `status` enum('normal','hidden') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='资源套餐';

-- ----------------------------
-- Records of bth_plans
-- ----------------------------
INSERT INTO `bth_plans` VALUES ('1', '默认', '{\"type\":\"btpanel\",\"name\":\"\\u9ed8\\u8ba4\",\"sql\":\"MySQL\",\"ftp\":\"1\",\"port\":\"80\",\"domain_num\":\"5\",\"web_back_num\":\"5\",\"sql_back_num\":\"5\",\"domainpools_id\":\"1\",\"ippools_id\":\"1\",\"ip_num\":\"0\",\"preset_procedure\":\"\",\"phpver\":\"72\",\"perserver\":\"0\",\"limit_rate\":\"0\",\"site_max\":\"0\",\"sql_max\":\"0\",\"flow_max\":\"0\",\"domain_audit\":\"0\",\"session\":\"0\",\"vsftpd\":\"0\",\"sites_path\":\"\"}', '1597366074', '1600096735', null, 'normal');

-- ----------------------------
-- Table structure for bth_product
-- ----------------------------
DROP TABLE IF EXISTS `bth_product`;
CREATE TABLE `bth_product` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `pid` int(10) DEFAULT NULL COMMENT '资源组ID',
  `name` varchar(30) NOT NULL COMMENT '变量名',
  `group` varchar(30) NOT NULL COMMENT '分组',
  `title` varchar(100) NOT NULL COMMENT '变量标题',
  `tip` varchar(100) NOT NULL COMMENT '变量描述',
  `type` varchar(30) NOT NULL COMMENT '类型:string,text,int,bool,array,datetime,date,file',
  `value` text NOT NULL COMMENT '变量值',
  `content` text COMMENT '变量字典数据',
  `rule` varchar(100) DEFAULT NULL COMMENT '验证规则',
  `extend` text COMMENT '扩展属性',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`) USING BTREE,
  KEY `pid` (`pid`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8 COMMENT='资源套餐配置';

-- ----------------------------
-- Records of bth_product
-- ----------------------------
INSERT INTO `bth_product` VALUES ('1', '1', 'sql', 'btpanel', '数据库', '开通数据库类型，请匹配服务器中安装的数据库类型', 'select', '1', '{\"none\":\"不开通\",\"MySQL\":\"MySQL\",\"SQLServer\":\"SQLServer\"}', '', '');
INSERT INTO `bth_product` VALUES ('2', '1', 'ftp', 'btpanel', '开通FTP', '是否开通FTP', 'select', '1', '{\"1\":\"开通\",\"0\":\"不开通\"}', '', '');
INSERT INTO `bth_product` VALUES ('3', '1', 'port', 'btpanel', '站点端口', '默认端口使用80，如有特殊需要请自定义', 'number', '80', '', '', '');
INSERT INTO `bth_product` VALUES ('4', '1', 'domain_num', 'btpanel', '绑定域名数', '绑定域名数，无限制：0', 'number', '0', '', '', '');
INSERT INTO `bth_product` VALUES ('5', '1', 'web_back_num', 'btpanel', '站点备份数', '站点备份数，无限制：0', 'number', '0', '', '', '');
INSERT INTO `bth_product` VALUES ('6', '1', 'sql_back_num', 'btpanel', '数据库备份数', '数据库备份数，无限制：0', 'number', '0', '', '', '');
INSERT INTO `bth_product` VALUES ('7', '1', 'domainpools_id', 'btpanel', '域名池', '域名池', 'custom', '', '', 'required', '<input id=\"domainpools_id\" data-source=\"domainpools\" class=\"form-control selectpage\" name=\"row[domainpools_id]\" type=\"text\" data-primary-key=\"id\" data-pagination=\"true\" placeholder=\"点击查找\" value=\"{value}\">');
INSERT INTO `bth_product` VALUES ('8', '1', 'ippools_id', 'btpanel', 'IP池', 'IP池', 'custom', '', '', '', '<input id=\"ippools_id\" data-source=\"ippools\" class=\"form-control selectpage\" name=\"row[ippools_id]\" type=\"text\" data-primary-key=\"id\" data-pagination=\"true\" placeholder=\"点击查找\" value=\"{value}\">');
INSERT INTO `bth_product` VALUES ('9', '1', 'ip_num', 'btpanel', 'IP赠送数量', '默认赠送IP数量，也可以不赠送IP', 'number', '0', null, null, null);
INSERT INTO `bth_product` VALUES ('10', '1', 'preset_procedure', 'btpanel', '预装程序', '请选择预装应用程序，不预装请为空', 'custom', '', '', '', '<input id=\"deployment\" data-source=\"ajax/deployment\" class=\"form-control selectpage\" name=\"row[preset_procedure]\" type=\"text\" data-primary-key=\"id\" data-pagination=\"true\" placeholder=\"点击查找\" value=\"{value}\">');
INSERT INTO `bth_product` VALUES ('11', '1', 'phpver', 'btpanel', 'php版本', '请在宝塔面板中安装好该PHP版本', 'custom', '00', '{\"00\":\"纯静态\",\"52\":\"52\",\"53\":\"53\",\"54\":\"54\",\"55\":\"55\",\"56\":\"56\",\"70\":\"70\",\"71\":\"71\",\"72\":\"72\",\"73\":\"73\",\"74\":\"74\"}', '', '<input id=\"phpver\" data-source=\"ajax/phplist\" class=\"form-control selectpage\" name=\"row[phpver]\" type=\"text\" data-primary-key=\"name\" data-pagination=\"true\" placeholder=\"点击查找\" value=\"{value}\">');
INSERT INTO `bth_product` VALUES ('12', '1', 'perserver', 'btpanel', '限制并发', '限制并发，不限制 ：0', 'number', '0', '', '', '');
INSERT INTO `bth_product` VALUES ('13', '1', 'limit_rate', 'btpanel', '限制网速（KB）', '限制网速（KB），不限制：0', 'number', '0', '', '', '');
INSERT INTO `bth_product` VALUES ('14', '1', 'site_max', 'btpanel', '站点空间大小', '站点空间大小（MB），不限制：0', 'number', '0', '', '', '');
INSERT INTO `bth_product` VALUES ('15', '1', 'sql_max', 'btpanel', '数据库大小', '数据库大小（MB），不限制：0', 'number', '0', '', '', '');
INSERT INTO `bth_product` VALUES ('16', '1', 'flow_max', 'btpanel', '流量大小', '流量大小（MB/每月），不限制：0', 'number', '0', '', '', '');
INSERT INTO `bth_product` VALUES ('19', '1', 'domain_audit', 'btpanel', '域名绑定审核', '用户绑定是否需要管理员手动审核，或加入机房白名单', 'radio', '0', '{\"1\":\"需要\",\"0\":\"不需要\"}', '', '');
INSERT INTO `bth_product` VALUES ('20', '1', 'session', 'btpanel', 'session隔离', '开启后将会把session文件存放到独立文件夹独立文件夹，不与其他站点公用存储位置\r\n若您在PHP配置中将session保存到memcache/redis等缓存器时，请不要开启此选项', 'radio', '0', '{\"1\":\"开启\",\"0\":\"不开启\"}', '', '');
INSERT INTO `bth_product` VALUES ('21', '1', 'vsftpd', 'btpanel', 'vsftpd插件', '请在面板中安装vsftpd后开启，用于强制限制站点大小，详情查看https://bbs.btye.net/d/93', 'radio', '0', '{\"1\":\"开启\",\"0\":\"不开启\"}', '', '');
INSERT INTO `bth_product` VALUES ('22', '1', 'sites_path', 'btpanel', '建站目录', '网站搭建目录，为空则系统默认，Linux默认/www/wwwroot，WIndows默认C:/wwwroot，Windows和Linux的建站目录请勿混用', 'string', '', '', '', '');

-- ----------------------------
-- Table structure for bth_queue
-- ----------------------------
DROP TABLE IF EXISTS `bth_queue`;
CREATE TABLE `bth_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `function` varchar(32) NOT NULL COMMENT '执行方法',
  `createtime` int(11) NOT NULL COMMENT '添加时间',
  `updatetime` int(11) DEFAULT NULL COMMENT '修改时间',
  `runtime` int(11) DEFAULT NULL COMMENT '最后运行时间',
  `executetime` int(11) NOT NULL DEFAULT '0' COMMENT '执行间隔时间（s）',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'hidden' COMMENT '状态',
  `weigh` int(10) DEFAULT '0' COMMENT '执行权重，越大越前',
  `configgroup` text COMMENT '额外配置',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='任务对列表';

-- ----------------------------
-- Records of bth_queue
-- ----------------------------
INSERT INTO `bth_queue` VALUES ('1', 'btresource', '1589730915', '1600096560', '1600096560', '60', 'normal', '4', '[{\"key\":\"limit\",\"value\":\"10\",\"info\":\"一次检查多少主机\"},{\"key\":\"checkTime\",\"value\":\"20\",\"info\":\"单台主机检查间隔（分钟），如主机数量过多，请适当提高检查间隔时间或limit的值\"}]');
INSERT INTO `bth_queue` VALUES ('2', 'hosttask', '1589730915', '1600060385', '1600060385', '43200', 'normal', '5', '');
INSERT INTO `bth_queue` VALUES ('3', 'hostclear', '1589730915', '1600060384', '1600060384', '43200', 'normal', '6', '');

-- ----------------------------
-- Table structure for bth_queue_log
-- ----------------------------
DROP TABLE IF EXISTS `bth_queue_log`;
CREATE TABLE `bth_queue_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `createtime` int(11) NOT NULL COMMENT '执行时间',
  `logs` text COMMENT '执行结果',
  `call_time` varchar(32) DEFAULT NULL COMMENT '运行时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='任务队列日志表';

-- ----------------------------
-- Records of bth_queue_log
-- ----------------------------

-- ----------------------------
-- Table structure for bth_sql
-- ----------------------------
DROP TABLE IF EXISTS `bth_sql`;
CREATE TABLE `bth_sql` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `vhost_id` int(10) NOT NULL DEFAULT '0' COMMENT '主机',
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '用户名',
  `database` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '数据库名',
  `password` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '密码',
  `console` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '数据库控制台',
  `type` enum('bt','custom') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'bt' COMMENT '数据库类型:bt=宝塔,custom=自定义',
  `createtime` int(10) NOT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `deletetime` int(10) DEFAULT NULL COMMENT '删除时间',
  `status` enum('normal','hidden') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='数据库';

-- ----------------------------
-- Records of bth_sql
-- ----------------------------

-- ----------------------------
-- Table structure for bth_user
-- ----------------------------
DROP TABLE IF EXISTS `bth_user`;
CREATE TABLE `bth_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户组',
  `username` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户名',
  `nickname` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '昵称',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密码',
  `salt` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密码盐',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '头像',
  `prevtime` int(10) DEFAULT NULL COMMENT '上次登录时间',
  `logintime` int(10) DEFAULT NULL COMMENT '登录时间',
  `loginip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '登录IP',
  `loginfailure` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '失败次数',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `deletetime` int(10) DEFAULT NULL COMMENT '删除时间',
  `token` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Token',
  `notice` text COLLATE utf8mb4_unicode_ci COMMENT '备注',
  `status` enum('normal','hidden','locked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '状态:normal=正常,hidden=停用,locked=锁定',
  PRIMARY KEY (`id`),
  KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员表';

-- ----------------------------
-- Records of bth_user
-- ----------------------------
INSERT INTO `bth_user` VALUES ('1', '1', 'admin', 'Admin', 'ff75nIBMAGgT-o90KQv2DZSw3BfnocUn8hk2xLbHqTj2kibL1Q', 'LB1d8V', '', '1600062285', '1600089415', '127.0.0.1', '0', '1597552377', '1600096908', null, '', 'admin', 'normal');

-- ----------------------------
-- Table structure for bth_user_group
-- ----------------------------
DROP TABLE IF EXISTS `bth_user_group`;
CREATE TABLE `bth_user_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '组名',
  `rules` text COLLATE utf8mb4_unicode_ci COMMENT '权限节点',
  `createtime` int(10) DEFAULT NULL COMMENT '添加时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `status` enum('normal','hidden') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员组表';

-- ----------------------------
-- Records of bth_user_group
-- ----------------------------
INSERT INTO `bth_user_group` VALUES ('1', '默认组', '13,90,89,88,87,86,84,85,81,83,82,80,79,77,75,76,72,74,73,71,67,70,69,68,66,64,65,62,63,59,61,60,58,55,57,56,48,78,54,53,52,51,50,49,41,38,47,46,45,44,43,40,39,37,36,33,35,34,30,32,31,27,29,28,25,26,22,24,23,18,21,20,19,15,17,16,14,2,4,11,10,9,12,1,3,7,6,5,8', '1515386468', '1598353118', 'normal');

-- ----------------------------
-- Table structure for bth_user_rule
-- ----------------------------
DROP TABLE IF EXISTS `bth_user_rule`;
CREATE TABLE `bth_user_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) DEFAULT NULL COMMENT '父ID',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '名称',
  `title` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '标题',
  `remark` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '备注',
  `ismenu` tinyint(1) DEFAULT NULL COMMENT '是否菜单',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `weigh` int(10) DEFAULT '0' COMMENT '权重',
  `status` enum('normal','hidden') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员规则表';

-- ----------------------------
-- Records of bth_user_rule
-- ----------------------------
INSERT INTO `bth_user_rule` VALUES ('2', '0', 'api', 'API接口', '', '1', '1516168062', '1516168062', '2', 'normal');
INSERT INTO `bth_user_rule` VALUES ('3', '0', 'user', '用户模块', '', '1', '1515386221', '1598662979', '12', 'normal');
INSERT INTO `bth_user_rule` VALUES ('5', '3', 'index/user/login', '登录', '', '0', '1515386247', '1515386247', '5', 'normal');
INSERT INTO `bth_user_rule` VALUES ('7', '3', 'index/user/index', '站点中心', '', '0', '1516015012', '1598662927', '9', 'normal');
INSERT INTO `bth_user_rule` VALUES ('13', '0', 'vhost', '控制台', '', '1', '1598326898', '1598326898', '13', 'normal');
INSERT INTO `bth_user_rule` VALUES ('14', '13', 'index/vhost/index', '首页', '', '1', '1598326932', '1598326932', '14', 'normal');
INSERT INTO `bth_user_rule` VALUES ('15', '13', 'index/vhost/domain', '域名', '', '1', '1598327179', '1598327179', '15', 'normal');
INSERT INTO `bth_user_rule` VALUES ('16', '15', 'index/vhost/incdomain', '域名绑定', '', '0', '1598327197', '1598327197', '16', 'normal');
INSERT INTO `bth_user_rule` VALUES ('17', '15', 'index/vhost/deldomain', '域名删除', '', '0', '1598327212', '1598327222', '17', 'normal');
INSERT INTO `bth_user_rule` VALUES ('18', '13', 'index/vhost/pass', '密码设置', '', '1', '1598327235', '1598327235', '18', 'normal');
INSERT INTO `bth_user_rule` VALUES ('19', '18', 'index/vhost/passvhost', '主机密码修改', '', '0', '1598327284', '1598327284', '19', 'normal');
INSERT INTO `bth_user_rule` VALUES ('20', '18', 'index/vhost/passsql', '数据库密码修改', '', '0', '1598327296', '1598327296', '20', 'normal');
INSERT INTO `bth_user_rule` VALUES ('21', '18', 'index/vhost/passftp', 'ftp密码修改', '', '0', '1598327307', '1598328849', '21', 'normal');
INSERT INTO `bth_user_rule` VALUES ('22', '13', 'index/vhost/speed', '网站限速', '', '1', '1598327326', '1598354857', '22', 'normal');
INSERT INTO `bth_user_rule` VALUES ('23', '22', 'index/vhost/speedup', '限速修改', '', '0', '1598327343', '1598327343', '23', 'normal');
INSERT INTO `bth_user_rule` VALUES ('24', '22', 'index/vhost/speedoff', '关闭限速', '', '0', '1598327355', '1598327355', '24', 'normal');
INSERT INTO `bth_user_rule` VALUES ('25', '13', 'index/vhost/defaultfile', '默认文件', '', '1', '1598327369', '1598327369', '25', 'normal');
INSERT INTO `bth_user_rule` VALUES ('26', '25', 'index/vhost/fileup', '默认文件修改', '', '0', '1598327385', '1598327385', '26', 'normal');
INSERT INTO `bth_user_rule` VALUES ('27', '13', 'index/vhost/rewrite301', '域名跳转', '', '1', '1598327399', '1598327399', '27', 'normal');
INSERT INTO `bth_user_rule` VALUES ('28', '27', 'index/vhost/r301up', '修改域名跳转', '', '0', '1598327410', '1598327426', '28', 'normal');
INSERT INTO `bth_user_rule` VALUES ('29', '27', 'index/vhost/r301off', '关闭域名跳转', '', '0', '1598327420', '1598327420', '29', 'normal');
INSERT INTO `bth_user_rule` VALUES ('30', '13', 'index/vhost/redir', '重定向', '', '1', '1598327437', '1598327437', '30', 'normal');
INSERT INTO `bth_user_rule` VALUES ('31', '30', 'index/vhost/redirup', '修改重定向', '', '0', '1598327448', '1598327448', '31', 'normal');
INSERT INTO `bth_user_rule` VALUES ('32', '30', 'index/vhost/redirdel', '删除重定向', '', '0', '1598327460', '1598327460', '32', 'normal');
INSERT INTO `bth_user_rule` VALUES ('33', '13', 'index/vhost/rewrite', '伪静态规则', '', '1', '1598327471', '1598327471', '33', 'normal');
INSERT INTO `bth_user_rule` VALUES ('34', '33', 'index/vhost/rewriteget', '获取伪静态规则', '', '0', '1598327484', '1598328842', '34', 'normal');
INSERT INTO `bth_user_rule` VALUES ('35', '33', 'index/vhost/rewriteset', '设置伪静态规则', '', '0', '1598327493', '1598327493', '35', 'normal');
INSERT INTO `bth_user_rule` VALUES ('36', '13', 'index/vhost/file', '文件管理', '', '1', '1598327509', '1598327509', '36', 'normal');
INSERT INTO `bth_user_rule` VALUES ('37', '13', 'index/vhost/file_ftp', '文件管理（FTP）', '', '1', '1598327535', '1598327535', '37', 'normal');
INSERT INTO `bth_user_rule` VALUES ('38', '13', 'index/vhost/back', '备份', '', '1', '1598327549', '1598352993', '38', 'normal');
INSERT INTO `bth_user_rule` VALUES ('39', '38', 'index/vhost/webbackinc', '新增网站备份', '', '0', '1598327559', '1598327559', '39', 'normal');
INSERT INTO `bth_user_rule` VALUES ('40', '38', 'index/vhost/webbackdel', '删除网站备份', '', '0', '1598327569', '1598327569', '40', 'normal');
INSERT INTO `bth_user_rule` VALUES ('43', '38', 'index/vhost/sqlbackinc', '新增备份', '', '0', '1598327608', '1598353021', '43', 'normal');
INSERT INTO `bth_user_rule` VALUES ('44', '38', 'index/vhost/sqlbackdel', '删除备份', '', '0', '1598327619', '1598353018', '44', 'normal');
INSERT INTO `bth_user_rule` VALUES ('45', '38', 'index/vhost/sqlbackdown', '下载备份', '', '0', '1598327628', '1598353014', '45', 'normal');
INSERT INTO `bth_user_rule` VALUES ('46', '38', 'index/vhost/sqlinputsql', '备份还原', '', '0', '1598327637', '1598353009', '46', 'normal');
INSERT INTO `bth_user_rule` VALUES ('48', '13', 'index/vhost/ssl', 'SSL证书', '', '1', '1598327663', '1598327663', '48', 'normal');
INSERT INTO `bth_user_rule` VALUES ('49', '48', 'index/vhost/tohttps', '强制HTTPS', '', '0', '1598327673', '1598327673', '49', 'normal');
INSERT INTO `bth_user_rule` VALUES ('50', '48', 'index/vhost/sslset', 'SSL配置', '', '0', '1598327685', '1598327685', '50', 'normal');
INSERT INTO `bth_user_rule` VALUES ('51', '48', 'index/vhost/ssloff', 'SSL关闭', '', '0', '1598327697', '1598327697', '51', 'normal');
INSERT INTO `bth_user_rule` VALUES ('52', '48', 'index/vhost/sslapply', '宝塔SSL', '', '0', '1598327726', '1598327726', '52', 'normal');
INSERT INTO `bth_user_rule` VALUES ('53', '48', 'index/vhost/sslapplylets', "Let's Encrypt", '', '0', '1598327747', '1598327747', '53', 'normal');
INSERT INTO `bth_user_rule` VALUES ('54', '48', 'index/vhost/sslrenewlets', '证书续签', '', '0', '1598327758', '1598327758', '54', 'normal');
INSERT INTO `bth_user_rule` VALUES ('55', '13', 'index/vhost/protection', '防盗链', '', '1', '1598327770', '1598327770', '55', 'normal');
INSERT INTO `bth_user_rule` VALUES ('56', '55', 'index/vhost/protectionset', '设置防盗链', '', '0', '1598327782', '1598327782', '56', 'normal');
INSERT INTO `bth_user_rule` VALUES ('57', '55', 'index/vhost/protectionoff', '关闭防盗链', '', '0', '1598327791', '1598327791', '57', 'normal');
INSERT INTO `bth_user_rule` VALUES ('58', '13', 'index/vhost/sitelog', '网站日志', '', '1', '1598327800', '1598327800', '58', 'normal');
INSERT INTO `bth_user_rule` VALUES ('59', '13', 'index/vhost/httpauth', 'HTTP认证', '', '1', '1598327849', '1598327849', '59', 'normal');
INSERT INTO `bth_user_rule` VALUES ('60', '59', 'index/vhost/httpauthset', '配置密码', '', '0', '1598327863', '1598327863', '60', 'normal');
INSERT INTO `bth_user_rule` VALUES ('61', '59', 'index/vhost/httpauthoff', '关闭密码', '', '0', '1598327876', '1598327876', '61', 'normal');
INSERT INTO `bth_user_rule` VALUES ('62', '13', 'index/vhost/runpath', '网站运行目录', '', '1', '1598327886', '1598327886', '62', 'normal');
INSERT INTO `bth_user_rule` VALUES ('63', '62', 'index/vhost/setsiterunpath', '目录配置', '', '0', '1598327899', '1598327899', '63', 'normal');
INSERT INTO `bth_user_rule` VALUES ('64', '13', 'index/vhost/deployment', '一键部署', '', '1', '1598327908', '1598327908', '64', 'normal');
INSERT INTO `bth_user_rule` VALUES ('65', '64', 'index/vhost/deploymentset', '一键部署到站点', '', '0', '1598327924', '1598327924', '65', 'normal');
INSERT INTO `bth_user_rule` VALUES ('66', '13', 'index/vhost/deployment_new', '一键部署(新)', '', '1', '1598327942', '1598328811', '66', 'normal');
INSERT INTO `bth_user_rule` VALUES ('67', '13', 'index/vhost/proof', '防篡改', '', '1', '1598327971', '1598327971', '67', 'normal');
INSERT INTO `bth_user_rule` VALUES ('68', '67', 'index/vhost/proofstatus', '防篡改开关', '', '0', '1598327980', '1598328012', '68', 'normal');
INSERT INTO `bth_user_rule` VALUES ('69', '67', 'index/vhost/delproof', '删除规则', '', '0', '1598327991', '1598327991', '69', 'normal');
INSERT INTO `bth_user_rule` VALUES ('70', '67', 'index/vhost/incproof', '添加规则', '', '0', '1598328001', '1598328001', '70', 'normal');
INSERT INTO `bth_user_rule` VALUES ('71', '13', 'index/vhost/total', '流量报表', '', '1', '1598328009', '1598355046', '71', 'normal');
INSERT INTO `bth_user_rule` VALUES ('72', '13', 'index/vhost/waf', '防火墙', '', '1', '1598328024', '1598328024', '72', 'normal');
INSERT INTO `bth_user_rule` VALUES ('73', '72', 'index/vhost/wafstatus', 'waf开关', '', '0', '1598328041', '1598328790', '73', 'normal');
INSERT INTO `bth_user_rule` VALUES ('74', '72', 'index/vhost/setwafcc', '防cc控制', '', '0', '1598328051', '1598328051', '74', 'normal');
INSERT INTO `bth_user_rule` VALUES ('75', '13', 'index/vhost/proxy', '反向代理', '', '1', '1598328059', '1598328059', '75', 'normal');
INSERT INTO `bth_user_rule` VALUES ('76', '75', 'index/vhost/proxydel', '反代删除', '', '0', '1598328072', '1598328072', '76', 'normal');
INSERT INTO `bth_user_rule` VALUES ('77', '13', 'index/vhost/phpset', 'php版本切换', '', '0', '1598328082', '1598328082', '77', 'normal');
INSERT INTO `bth_user_rule` VALUES ('78', '48', 'index/vhost/getfilelog', 'Lets申请日志', '', '0', '1598328101', '1598328101', '78', 'normal');
INSERT INTO `bth_user_rule` VALUES ('79', '13', 'index/vhost/webstart', '启用站点', '', '0', '1598328117', '1598328117', '79', 'normal');
INSERT INTO `bth_user_rule` VALUES ('80', '13', 'index/vhost/webstop', '停用站点', '', '0', '1598328125', '1598328125', '80', 'normal');
INSERT INTO `bth_user_rule` VALUES ('81', '13', 'index/vhost/dirauth', '目录保护', '', '1', '1598328168', '1598328168', '81', 'normal');
INSERT INTO `bth_user_rule` VALUES ('82', '81', 'index/vhost/setdirauth', '添加目录保护', '', '0', '1598328177', '1598328177', '82', 'normal');
INSERT INTO `bth_user_rule` VALUES ('83', '81', 'index/vhost/deldirauth', '删除目录保护', '', '0', '1598328185', '1598328187', '83', 'normal');
INSERT INTO `bth_user_rule` VALUES ('84', '13', 'index/vhost/sqltools', 'Mysql工具箱', '', '1', '1598328892', '1598328892', '84', 'normal');
INSERT INTO `bth_user_rule` VALUES ('85', '84', 'index/vhost/sqlToolsAction', '表操作', '', '0', '1598328921', '1598328960', '85', 'normal');
INSERT INTO `bth_user_rule` VALUES ('86', '13', 'index/vhost/ftpstatus', 'FTP开关', '', '0', '1598328930', '1598329099', '86', 'normal');
INSERT INTO `bth_user_rule` VALUES ('87', '13', 'index/vhost/main', '控制台', '', '1', '1598329439', '1598329439', '87', 'normal');

-- ----------------------------
-- Table structure for bth_user_token
-- ----------------------------
DROP TABLE IF EXISTS `bth_user_token`;
CREATE TABLE `bth_user_token` (
  `token` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Token',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `expiretime` int(10) DEFAULT NULL COMMENT '过期时间',
  PRIMARY KEY (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员Token表';

-- ----------------------------
-- Records of bth_user_token
-- ----------------------------

-- ----------------------------
-- Table structure for bth_version
-- ----------------------------
DROP TABLE IF EXISTS `bth_version`;
CREATE TABLE `bth_version` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version` varchar(32) DEFAULT NULL COMMENT '当前版本号',
  `last_version` varchar(32) DEFAULT NULL COMMENT '升级前版本号',
  `desc` text COMMENT '升级说明',
  `updatetime` int(11) DEFAULT NULL COMMENT '升级时间',
  `error_msg` text COMMENT '错误内容',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='版本升级表';

-- ----------------------------
-- Records of bth_version
-- ----------------------------

SET FOREIGN_KEY_CHECKS = 1;


-- 1.0.2+20200917
INSERT INTO `bth_config` (`id`, `name`, `group`, `title`, `tip`, `type`, `value`, `content`, `rule`, `extend`, `setting`) VALUES (null, 'http', 'server', 'http', '', 'select', 'http://', '{\"http:\\/\\/\":\"http:\\/\\/\",\"https:\\/\\/\":\"https:\\/\\/\"}', '', '', '{\"table\":\"\",\"conditions\":\"\",\"key\":\"\",\"value\":\"\"}');

-- 1.1.0+20201020
INSERT INTO `bth_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES ('315', 'file', '2', 'general/upgrade/index', '在线更新', 'fa fa-cloud-upload', '', '', '1', '1602665512', '1602665872', '0', 'normal');
ALTER TABLE `bth_user` ADD COLUMN `email`  varchar(100) NOT NULL COMMENT '电子邮箱' AFTER `password`;

-- 1.1.1+20201024
INSERT INTO `bth_config` (`id`, `name`, `group`, `title`, `tip`, `type`, `value`, `content`, `rule`, `extend`, `setting`) VALUES (null, 'auto_flow', 'personalization', 'auto_flow', '控制台自动加载动态流量图及服务器信息', 'switch', '1', '', '', '', '{\"table\":\"\",\"conditions\":\"\",\"key\":\"\",\"value\":\"\"}');
INSERT INTO `bth_config` (`id`, `name`, `group`, `title`, `tip`, `type`, `value`, `content`, `rule`, `extend`, `setting`) VALUES (null, 'auto_update', 'personalization', 'auto_update', '自动检查更新', 'switch', '1', '', '', '', '{\"table\":\"\",\"conditions\":\"\",\"key\":\"\",\"value\":\"\"}');
INSERT INTO `bth_config` (`id`, `name`, `group`, `title`, `tip`, `type`, `value`, `content`, `rule`, `extend`, `setting`) VALUES (null, 'auto_notice', 'personalization', 'auto_notice', '自动获取公告', 'switch', '1', '', '', '', '{\"table\":\"\",\"conditions\":\"\",\"key\":\"\",\"value\":\"\"}');
ALTER TABLE `bth_user` MODIFY COLUMN `email`  varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '电子邮箱' AFTER `password`;

-- 1.2.0+20201212
ALTER TABLE `bth_host`
ADD COLUMN `perserver`  int(10) NOT NULL DEFAULT 0 COMMENT '限制并发' AFTER `sql_back_num`,
ADD COLUMN `limit_rate`  int(10) NOT NULL DEFAULT 0 COMMENT '限制流量' AFTER `perserver`,
ADD COLUMN `sub_bind`  enum('0','1') NOT NULL DEFAULT '1' COMMENT '绑定子目录' AFTER `is_vsftpd`;
INSERT INTO `bth_product` (`id`, `pid`, `name`, `group`, `title`, `tip`, `type`, `value`, `content`, `rule`, `extend`) VALUES (null, '1', 'sub_bind', 'btpanel', '子目录绑定', '是否允许绑定域名到子目录', 'radio', '1', '{\"1\":\"允许\",\"0\":\"不允许\"}', NULL, NULL);

-- 1.3.0+20210107
INSERT INTO `bth_user_rule` (`id`, `pid`, `name`, `title`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES ('88', '13', 'index/vhost/hostreset', '站点重置', '站点重置', '1', '1609135243', '1609135406', '88', 'normal');

DROP TABLE IF EXISTS `bth_hostreset_log`;
CREATE TABLE `bth_hostreset_log` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL COMMENT '用户ID',
  `host_id` int(10) NOT NULL COMMENT '原有主机ID',
  `new_host_id` int(10) DEFAULT NULL COMMENT '新主机ID',
  `bt_id` int(10) NOT NULL COMMENT '宝塔ID',
  `new_bt_id` int(10) DEFAULT NULL COMMENT '新宝塔ID',
  `createtime` int(10) NOT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `info` text COLLATE utf8_unicode_ci COMMENT '其他信息',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='站点重置记录表';

DROP TABLE IF EXISTS `bth_hostresources_log`;
CREATE TABLE `bth_hostresources_log` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `host_id` int(10) NOT NULL COMMENT '主机ID',
  `site_size` int(10) NOT NULL DEFAULT '0' COMMENT '空间大小',
  `flow_size` int(10) NOT NULL DEFAULT '0' COMMENT '流量大小',
  `sql_size` int(10) NOT NULL COMMENT '数据库大小',
  `createtime` int(10) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='主机资源记录表';


-- 1.4.0+20210130
ALTER TABLE `bth_config` ADD COLUMN `weigh`  int(10) NOT NULL DEFAULT 0 COMMENT '排序' AFTER `setting`;

INSERT INTO `bth_config` (`id`, `name`, `group`, `title`, `tip`, `type`, `value`, `content`, `rule`, `extend`, `setting`, `weigh`) VALUES (null, 'signature_time', 'secret', 'signature_time', '签名有效时长,单位s', 'number', '10', '', '', '', '{\"table\":\"\",\"conditions\":\"\",\"key\":\"\",\"value\":\"\"}', '100');

UPDATE `bth_config` SET `weigh`='101' WHERE (`name`='access_token');
UPDATE `bth_config` SET `weigh`='100' WHERE (`name`='api_token');
UPDATE `bth_config` SET `weigh`='101' WHERE (`name`='http');
UPDATE `bth_config` SET `weigh`='10' WHERE (`name`='split_size');
UPDATE `bth_config` SET `weigh`='100' WHERE (`name`='mail_type');
UPDATE `bth_config` SET `weigh`='95' WHERE (`name`='mail_smtp_host');
UPDATE `bth_config` SET `weigh`='93' WHERE (`name`='mail_smtp_port');
UPDATE `bth_config` SET `weigh`='90' WHERE (`name`='mail_from');
UPDATE `bth_config` SET `weigh`='85' WHERE (`name`='mail_smtp_pass');
UPDATE `bth_config` SET `weigh`='83' WHERE (`name`='mail_verify_type');
UPDATE `bth_config` SET `weigh`='80' WHERE (`name`='mail_smtp_user');

ALTER TABLE `bth_domain_block` ADD COLUMN `is_all`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '所有域名';
ALTER TABLE `bth_domain_block` ADD COLUMN `type`  varchar(255) NULL COMMENT '类型:block:拦截,pass:白名单';

DROP TABLE IF EXISTS `bth_domain_beian`;
CREATE TABLE `bth_domain_beian` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `vhost_id` int(10) NOT NULL DEFAULT '0' COMMENT '主机ID',
  `bt_id` int(10) NOT NULL COMMENT '原宝塔ID',
  `bt_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '原站点名',
  `bt_id_n` int(10) DEFAULT '0' COMMENT '现宝塔ID',
  `bt_name_n` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '站点名称',
  `domain` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '域名',
  `dir` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '绑定目录',
  `createtime` int(10) NOT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `deletetime` int(10) DEFAULT NULL,
  `status` enum('normal','auto','success') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '状态',
  `beian_info` text COLLATE utf8_unicode_ci COMMENT '备案完整信息',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='未备案域名绑定';


INSERT INTO `bth_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES ('316', 'file', '216', 'domainbeian', '备案审查', 'fa fa-circle-o', '', 'Domainbeian tips', '1', '1610513397', '1610513397', '0', 'normal');
INSERT INTO `bth_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES ('317', 'file', '216', 'domainblock', '域名过滤', 'fa fa-circle-o', '', 'Domainblock tips', '1', '1610700743', '1610700743', '0', 'normal');
INSERT INTO `bth_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES ('318', 'file', '275', 'user/hostlog', '操作日志', 'fa fa-list-alt', '', 'Hostlog tips', '1', '1610888550', '1610888550', '0', 'normal');
INSERT INTO `bth_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES ('319', 'file', '2', 'general/apilog', 'API日志', 'fa fa-list-alt', '', 'Apilog tips', '1', '1611813879', '1611813879', '0', 'normal');
UPDATE `bth_auth_rule` SET `title`='文件更新' WHERE (`name`='general/upgrade/index') LIMIT 1;

DROP TABLE IF EXISTS `bth_host_log`;
CREATE TABLE `bth_host_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `username` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户名字',
  `url` varchar(1500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作页面',
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '日志标题',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '内容',
  `ip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'IP',
  `useragent` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'User-Agent',
  `createtime` int(10) DEFAULT NULL COMMENT '操作时间',
  PRIMARY KEY (`id`),
  KEY `name` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='主机日志表';

DELETE FROM `bth_queue`;
DROP TABLE IF EXISTS `bth_queue`;
CREATE TABLE `bth_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `function` varchar(32) NOT NULL COMMENT '执行方法',
  `createtime` int(11) NOT NULL COMMENT '添加时间',
  `updatetime` int(11) DEFAULT NULL COMMENT '修改时间',
  `runtime` int(11) DEFAULT NULL COMMENT '最后运行时间',
  `executetime` int(11) NOT NULL DEFAULT '0' COMMENT '执行间隔时间（s）',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'hidden' COMMENT '状态',
  `weigh` int(10) DEFAULT '0' COMMENT '执行权重，越大越前',
  `configgroup` text COMMENT '额外配置',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='任务对列表';

INSERT INTO `bth_queue` VALUES ('1', 'btresource', '1589730915', '1611800059', '1611800025', '60', 'normal', '4', '[{\"key\":\"limit\",\"value\":\"10\",\"info\":\"一次检查多少主机\"},{\"key\":\"checkTime\",\"value\":\"20\",\"info\":\"单台主机检查间隔（分钟），如主机数量过多，请适当提高检查间隔时间或limit的值\"},{\"key\":\"ftmsg\",\"value\":\"1\",\"info\":\"方糖通知任务执行结果，0=不发送;1=发送\"},{\"key\":\"email\",\"value\":\"1\",\"info\":\"邮件通知任务执行结果，0=不发送;1=发送\"}]');
INSERT INTO `bth_queue` VALUES ('2', 'hosttask', '1589730915', '1611800069', '1611800020', '43200', 'normal', '5', '[{\"key\":\"ftmsg\",\"value\":\"0\",\"info\":\"方糖通知任务执行结果，0=不发送;1=发送\"},{\"key\":\"email\",\"value\":\"0\",\"info\":\"邮件通知任务执行结果，0=不发送;1=发送\"}]');
INSERT INTO `bth_queue` VALUES ('3', 'hostclear', '1589730915', '1611800078', '1611800020', '43200', 'normal', '6', '[{\"key\":\"ftmsg\",\"value\":\"0\",\"info\":\"方糖通知任务执行结果，0=不发送;1=发送\"},{\"key\":\"email\",\"value\":\"0\",\"info\":\"邮件通知任务执行结果，0=不发送;1=发送\"}]');

DROP TABLE IF EXISTS `bth_api_log`;
CREATE TABLE `bth_api_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `url` varchar(1500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作页面',
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '日志标题',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '内容',
  `ip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'IP',
  `useragent` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'User-Agent',
  `createtime` int(10) DEFAULT NULL COMMENT '操作时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='API日志表';

-- 1.4.3+20210204
INSERT INTO `bth_queue` (`id`, `function`, `createtime`, `updatetime`, `runtime`, `executetime`, `status`, `weigh`, `configgroup`) VALUES (null, 'updatecheck', '1612338301', '1612339415', '1612339412', '86400', 'normal', '7', '[{\"key\":\"ftmsg\",\"value\":\"1\",\"info\":\"方糖通知任务执行结果，0=不发送;1=发送\"},{\"key\":\"email\",\"value\":\"1\",\"info\":\"邮件通知任务执行结果，0=不发送;1=发送\"}]');

-- 1.5.0+20210320
INSERT INTO `bth_user_rule` (`id`, `pid`, `name`, `title`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (null, '13', 'index/vhost/speed_cache', '缓存加速', '', '1', '1612427864', '1612427864', '89', 'normal');
INSERT INTO `bth_user_rule` (`id`, `pid`, `name`, `title`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (null, '13', 'index/vhost/speed_cache_list', '缓存规则列表', '', '0', '1612441579', '1612441703', '90', 'normal');

ALTER TABLE `bth_host` ADD COLUMN `is_api`  tinyint(1) NOT NULL DEFAULT 0 COMMENT 'API' AFTER `notice`;

INSERT INTO `bth_config` (`id`, `name`, `group`, `title`, `tip`, `type`, `value`, `content`, `rule`, `extend`, `setting`, `weigh`) VALUES (null, 'ask_beian', 'config', 'ask_beian', '绑定域名时是否检测域名备案', 'radio', '0', '[\"关\",\"开\"]', '', '', '{\"table\":\"\",\"conditions\":\"\",\"key\":\"\",\"value\":\"\"}', '0');
INSERT INTO `bth_config` (`id`, `name`, `group`, `title`, `tip`, `type`, `value`, `content`, `rule`, `extend`, `setting`, `weigh`) VALUES (null, 'chinaz_key', 'config', 'chinaz_key', 'http://api.chinaz.com/ApiDetails/Domain', 'string', '', '', '', '', '{\"table\":\"\",\"conditions\":\"\",\"key\":\"\",\"value\":\"\"}', '0');
INSERT INTO `bth_config` (`id`, `name`, `group`, `title`, `tip`, `type`, `value`, `content`, `rule`, `extend`, `setting`, `weigh`) VALUES (null, 'icp_check_api', 'config', 'icp_check_api', '数据同源(Chinaz）没钱的用免费版', 'radio', '0', '[\"大米[免费]\",\"Chinaz[收费]\"]', '', '', '{\"table\":\"\",\"conditions\":\"\",\"key\":\"\",\"value\":\"\"}', '0');

-- 1.6.1+20210731
UPDATE `bth_product` SET `tip` = '是否允许绑定域名到子目录。警告：该功能存在高危漏洞，该配置已转移到全局配置中，如需使用该功能，请查看文档' WHERE `name` = 'sub_bind';