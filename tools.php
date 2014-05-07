<?php
/*
[Discuz!] Tools (C)2001-2008 Comsenz Inc.
This is NOT a freeware, use is subject to license terms

$Id: tools.php 1193 2010-01-20 09:35:41Z songlixin $
*/

/**********************	密码配置区 开始*******************************/
$tool_password = ''; // ☆★☆★☆★ 请您设置一个工具包的高强度密码，不能为空！☆★☆★☆★
/**********************	密码配置区 结束*******************************/
error_reporting(E_ERROR | E_PARSE);	//E_ERROR | E_WARNING | E_PARSE | E_ALL
@set_time_limit(0);
define('TOOLS_ROOT', dirname(__FILE__)."/");
define('VERSION', '2009');
define('Release','100120');
$functionall = array(
	array('all', 'all_repair', '检查或修复数据库', '对所有数据表进行检查修复工作。'),
	array('all', 'all_runquery', '快速设置(SQL)', '可以运行任意SQL语句，请慎用。'),
	array('all', 'all_checkcharset', '字段编码检测', '对所有数据表进行编码检查和修复。'),
	array('all', 'all_config', '修改配置文件', '配置文件修改助手'),
	array('all', 'all_restore', '恢复数据库备份', '恢复论坛数据备份。'),
	array('all', 'all_setadmin', '找回管理员', '将把您指定的会员设置为管理员，也可以重新设置密码。'),
	array('dz', 'dz_filecheck', '文件校验', '检查论坛程序目录下的非Discuz!官方文件。'),
	array('dz', 'dz_rplastpost', '修复最后回复', '修复版块最后回复。'),
	array('dz', 'dz_rpthreads', '批量修复主题', '某些帖子页面会出现未定义操作，可以用批量修复主题的功能修复下。'),
	array('dz', 'dz_mysqlclear', '数据库冗余清理', '对您的数据进行有效性检查，删除冗余数据信息。'),
	array('dz', 'dz_moveattach', '附件保存方式', '将您现在的附件存储方式按照指定方式进行目录结构调整并重新存储。'),
	array('dz_uch', 'uch_dz_replace', '应用过滤规则', '按照设置的词语过滤列表，可选择性的对所有内容进行处理,内容将按照过滤规则进行处理。'),
	array('all', 'all_updatecache', '<font color=red>更新缓存</font>', '清除缓存。'),
);
$toolbar = array(
	array('phpinfo','INFO'),
	array('datago','转码'),
	array('all_logout','退出'),	
);
//初始化
$plustitle = '';
$lockfile = '';
//临时文件放置的目录，getplace()函数中设置
$docdir = '';
$action = '';
$target_fsockopen = '0'; 
$alertmsg = ' onclick="alert(\'点击确定开始运行,可能需要一段时间,请稍候\');"';
foreach(array('_COOKIE', '_POST', '_GET') as $_request) { 
	foreach($$_request as $_key => $_value) {
		($_key{0} != '_' && $_key != 'tool_password' && $_key != 'lockfile') && $$_key = taddslashes($_value);
	}
}
$whereis = getplace();
require_once $cfgfile;


if($whereis == 'is_dz' && !defined('DISCUZ_ROOT')) {
	define('DISCUZ_ROOT', TOOLS_ROOT);
}
if(!$whereis && !in_array($whereis, array('is_dz', 'is_uc', 'is_uch', 'is_ss'))) {
	$alertmsg = '';
	errorpage('<ul><li>工具箱必须放在Discuz!、UCenter、UCente Home或SupeSite的根目录下才能正常使用。</li><li>如果你确实放在了上述程序目录下，请检查上述程序运配置文件（config）的可读写权限是否正确</li>');
}
if(@file_exists($lockfile)) { 
	$alertmsg = '';
	errorpage("<h6>工具箱已关闭，如需开启只要通过 FTP 删除 $lockfile 文件即可！ </h6>");
} elseif($tool_password == '') {
	$alertmsg = '';
	errorpage('<h6>工具箱密码默认为空，第一次使用前请您修改本文件中$tool_password设置密码！</h6>');
}
if($action == 'login') {
	setcookie('toolpassword',md5($toolpassword), 0);
	echo '<meta http-equiv="refresh" content="2 url=?">';
	errorpage("<h6>请稍等，程序登录中！</h6>");
}
if(isset($toolpassword)) {
	if($toolpassword != md5($tool_password)) {
		$alertmsg = '';	
		errorpage("login");
	}
} else {
	$alertmsg = '';
	errorpage("login");
}
getdbcfg();
$mysql = mysql_connect($dbhost, $dbuser, $dbpw);
mysql_select_db($dbname);
$my_version = mysql_get_server_info();
if($my_version > '4.1') {
	$serverset = $dbcharset ? 'character_set_connection='.$dbcharset.', character_set_results='.$dbcharset.', character_set_client=binary' : '';
	$serverset .= $my_version > '5.0.1' ? ((empty($serverset))? '' : ',').'sql_mode=\'\'' : '';
	$serverset && mysql_query("SET $serverset");
}
//流程开始
if($action == 'all_repair') {
	$counttables = $oktables = $errortables = $rapirtables = 0;
	$doc = $docdir.'/repaireport.txt';
	if($check) {
		$tables = mysql_query("SHOW TABLES");
		if($iterations) {
			$iterations --;
		}
		while($table = mysql_fetch_row($tables)) {
				$counttables += 1;
				$answer = checktable($table[0],$iterations,$doc);
		}
		if($simple) {
			htmlheader();
			echo '<h4>检查修复数据库</h4>
			    <h5>检查结果:</h5>
					<table>
						<tr><th>检查表(张)</th><th>正常表(张)</th><th>修复的表(张)</th><th>出错(个)</th></tr>
						<tr><td>'.$counttables.'</td><td>'.$oktables.'</td><td>'.$rapirtables.'</td><td>'.$errortables.'</td></tr>
					</table>
				<p>检查结果没有错误后请返回工具箱首页反之则继续修复</p>
				<p><b><a href="tools.php?action=all_repair">继续修复</a>&nbsp;&nbsp;&nbsp;&nbsp;<b><a href="'.$doc.'">修复报告</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="tools.php">返回首页</a></b></p>
				</td></tr></table>';
			specialdiv();
		}
	} else {
		htmlheader();
		@unlink($doc);
		echo "<h4>检查修复数据库</h4>
		<div class='specialdiv'>
				操作提示：
				<ul>
				<li>您可以通过下面的方式修复已经损坏的数据库。点击后请耐心等待修复结果！</li>
				<li>本程序可以修复常见的数据库错误，但无法保证可以修复所有的数据库错误。(需要 MySQL 3.23+)</li>
				</ul>
				</div>
				<h5>操作：</h5>
				<ul>
				<li><a href=\"?action=all_repair&check=1&simple=1\">检查并尝试修复数据库1次</a>
				<li><a href=\"?action=all_repair&check=1&iterations=5&simple=1\">检查并尝试修复数据库5次</a> (因为数据库读写关系可能有时需要多修复几次才能完全修复成功)
				</ul>";
		specialdiv();
	}
	htmlfooter();
} elseif($action == 'all_restore') {//导入数据库备份
	ob_implicit_flush();
	$backdirarray = array( //不同的程序存放备份文件的目录是不同的
						'is_dz' => 'forumdata',
						'is_uc' => 'data/backup',
						'is_uch' => 'data',
						'is_ss' => 'data'
	);
	if(!get_cfg_var('register_globals')) {
		@extract($HTTP_GET_VARS);
	}
	$sqldump = '';
	htmlheader();
	?><h4>数据库恢复实用工具 </h4><?php
	echo "<div class=\"specialdiv\">操作提示：<ul>
		<li>只能恢复存放在服务器(远程或本地)上的数据文件,如果您的数据不在服务器上,请用 FTP 上传</li>
		<li>数据文件必须为 Discuz! 导出格式,并设置相应属性使 PHP 能够读取</li>
		<li>请尽量选择服务器空闲时段操作,以避免超时.如程序长久(超过 10 分钟)不反应,请刷新</li></ul></div>";
	if($file) {
		if(!mysql_select_db($dbname)) {
			mysql_query("CREATE DATABASE $dbname;");
		}
		if(strtolower(substr($file, 0, 7)) == "http://") {
			echo "从远程数据库恢复数据 - 读取远程数据:<br><br>";
			echo "从远程服务器读取文件 ... ";
			$sqldump = @fread($fp, 99999999);
			@fclose($fp);
			if($sqldump) {
				echo "成功<br><br>";
			} elseif(!$multivol) {
				cexit("失败<br><br><b>无法恢复数据</b>");
			}
		} else {
			echo "<div class=\"specialtext\">从本地恢复数据 - 检查数据文件:<br><br>";
			if(file_exists($file)) {
				echo "数据文件 $file 存在检查 ... 成功<br><br>";
			} elseif(!$multivol) {
				cexit("数据文件 $file 存在检查 ... 失败<br><br><br><b>无法恢复数据</b></div>");
			}
			if(is_readable($file)) {
				echo "数据文件 $file 可读检查 ... 成功<br><br>";
				@$fp = fopen($file, "r");
				@flock($fp, 3);
				$sqldump = @fread($fp, filesize($file));
				@fclose($fp);
				echo "从本地读取数据 ... 成功<br><br>";
			} elseif(!$multivol) {
				cexit("数据文件 $file 可读检查 ... 失败<br><br><br><b>无法恢复数据</b></div>");
			}
		}
		if($multivol && !$sqldump) {
			cexit("分卷备份范围检查 ... 成功<br><br><b>恭喜您,数据已经全部成功恢复!安全起见,请务必删除本程序.</b></div>");
		}
		echo "数据文件 $file 格式检查 ... ";
		if($whereis == 'is_uc') {
			
			$identify = explode(',', base64_decode(preg_replace("/^# Identify:\s*(\w+).*/s", "\\1", substr($sqldump, 0, 256))));		
			$method = 'multivol';
			$volume = $identify[4];
		} else {
			@list(,,,$method, $volume) = explode(',', base64_decode(preg_replace("/^# Identify:\s*(\w+).*/s", "\\1", preg_replace("/^(.+)/", "\\1", substr($sqldump, 0, 256)))));
		}
		if($method == 'multivol' && is_numeric($volume)) {
			echo "成功<br><br>";
		} else {
			cexit("失败<br><br><b>数据非 Discuz! 分卷备份格式,无法恢复</b></div>");
		}
		if($onlysave == "yes") {
			echo "将数据文件保存到本地服务器 ... ";
			$filename = TOOLS_ROOT.'./'.$backdirarray[$whereis].strrchr($file, "/");
			@$filehandle = fopen($filename, "w");
			@flock($filehandle, 3);
			if(@fwrite($filehandle, $sqldump)) {
				@fclose($filehandle);
				echo "成功<br><br>";
			} else {
				@fclose($filehandle);
				die("失败<br><br><b>无法保存数据</b>");
			}
			echo "成功<br><br><b>恭喜您,数据已经成功保存到本地服务器 <a href=\"".strstr($filename, "/")."\">$filename</a>.安全起见,请务必删除本程序.</b></div>";
		} else {
			$sqlquery = splitsql($sqldump);
			echo "拆分操作语句 ... 成功<br><br>";
			unset($sqldump);

			echo "正在恢复数据,请等待 ... </div>";
			foreach($sqlquery as $sql) {
				$dbversion = mysql_get_server_info();
				$sql = syntablestruct(trim($sql), $dbversion > '4.1', $dbcharset);
				if(trim($sql)) {
					@mysql_query($sql);
				}
			}
			if($auto == 'off') {
				$nextfile = str_replace("-$volume.sql", '-'.($volume + 1).'.sql', $file);
				cexit("<ul><li>数据文件 <b>$volume#</b> 恢复成功,如果有需要请继续恢复其他卷数据文件</li><li>请点击<b><a href=\"?action=all_restore&file=$nextfile&multivol=yes\">全部恢复</a></b>	或许单独恢复下一个数据文件<b><a href=\"?action=all_restore&file=$nextfile&multivol=yes&auto=off\">单独恢复下一数据文件</a></b></li></ul>");
			} else {
				$nextfile = str_replace("-$volume.sql", '-'.($volume + 1).'.sql', $file);
				echo "<ul><li>数据文件 <b>$volume#</b> 恢复成功,现在将自动导入其他分卷备份数据.</li><li><b>请勿关闭浏览器或中断本程序运行</b></li></ul>";
				redirect("?action=all_restore&file=$nextfile&multivol=yes");
			}
		}
	} else {
		$exportlog = array();
		if(is_dir(TOOLS_ROOT.'./'.$backdirarray[$whereis])) {
			$dir = dir(TOOLS_ROOT.'./'.$backdirarray[$whereis]);
			while($entry = $dir->read()) {
				$entry = "./".$backdirarray[$whereis]."/$entry";
				if(is_file($entry) && preg_match("/\.sql/i", $entry)) {
					$filesize = filesize($entry);
					$fp = @fopen($entry, 'rb');
					@$identify = explode(',', base64_decode(preg_replace("/^# Identify:\s*(\w+).*/s", "\\1", fgets($fp, 256))));
					@fclose ($fp);
						if(preg_match("/\-1.sql/i", $entry) || $identify[3] == 'shell') {
							$exportlog[$identify[0]] = array(	'version' => $identify[1],
												'type' => $identify[2],
												'method' => $identify[3],
												'volume' => $identify[4],
												'filename' => $entry,
												'size' => $filesize);
						}
				} elseif(is_dir($entry) && preg_match("/backup\_/i", $entry)) {
					$bakdir = dir($entry);
						while($bakentry = $bakdir->read()) {
							$bakentry = "$entry/$bakentry";
							if(is_file($bakentry)) {
								@$fp = fopen($bakentry, 'rb');
								@$bakidentify = explode(',', base64_decode(preg_replace("/^# Identify:\s*(\w+).*/s", "\\1", fgets($fp, 256))));
								@fclose ($fp);
								if(preg_match("/\-1\.sql/i", $bakentry) || $bakidentify[3] == 'shell') {
									$identify['bakentry'] = $bakentry;
								}
							}
						}
						if(preg_match("/backup\_/i", $entry)) {
							$exportlog[filemtime($entry)] = array(	'version' => $bakidentify[1],
												'type' => $bakidentify[2],
												'method' => $bakidentify[3],
												'volume' => $bakidentify[4],
												'bakentry' => $identify['bakentry'],
												'filename' => $entry);
						}
				}
			}
			$dir->close();
		} else {
			echo 'error';
		}
		krsort($exportlog);
		reset($exportlog);

		$title = '<h5><a href="?action=all_restore">【恢复数据】</a>';
		if($dz_version >= 700 || $whereis == 'is_uc' || $whereis == 'is_uch' || $ss_version >= 70) {
			$title .= '&nbsp;&nbsp;&nbsp;<a href="?action=all_backup&begin=1">【备份数据】</a></h5>';
		} else {
			$title .= '</h5>';	
		}
		$exportinfo = $title.'<table><caption>&nbsp;&nbsp;&nbsp;数据库文件夹</caption><tr><th>备份项目</th><th>版本</th><th>时间</th><th>类型</th><th>查看</th><th>操作</th></tr>';
		foreach($exportlog as $dateline => $info) {
			$info['dateline'] = is_int($dateline) ? gmdate("Y-m-d H:i", $dateline + 8*3600) : '未知';
				switch($info['type']) {
					case 'full':
						$info['type'] = '全部备份';
						break;
					case 'standard':
						$info['type'] = '标准备份(推荐)';
						break;
					case 'mini':
						$info['type'] = '最小备份';
						break;
					case 'custom':
						$info['type'] = '自定义备份';
						break;
				}
			$info['volume'] = $info['method'] == 'multivol' ? $info['volume'] : '';
			$info['method'] = $info['method'] == 'multivol' ? '多卷' : 'shell';
			$info['url'] = str_replace(".sql", '', str_replace("-$info[volume].sql", '', substr(strrchr($info['filename'], "/"), 1)));
			$exportinfo .= "<tr>\n".
				"<td>".$info['url']."</td>\n".
				"<td>$info[version]</td>\n".
				"<td>$info[dateline]</td>\n".
				"<td>$info[type]</td>\n";
			if($info['bakentry']) {
			$exportinfo .= "<td><a href=\"?action=all_restore&bakdirname=".$info['url']."\">查看</a></td>\n".
				"<td><a href=\"?action=all_restore&file=$info[bakentry]&importsubmit=yes\">[全部导入]</a></td>\n</tr>\n";
			} else {
			$exportinfo .= "<td><a href=\"?action=all_restore&filedirname=".$info['url']."\">查看</a></td>\n".
				"<td><a href=\"?action=all_restore&file=$info[filename]&importsubmit=yes\">[全部导入]</a></td>\n</tr>\n";
			}
		}
		$exportinfo .= '</table>';
		echo $exportinfo;
		unset($exportlog);
		unset($exportinfo);
		echo "<br>";
	//查看目录里的备份文件列表，一级目录下
		if(!empty($filedirname)) {
			$exportlog = array();
			if(is_dir(TOOLS_ROOT.'./'.$backdirarray[$whereis])) {
				$dir = dir(TOOLS_ROOT.'./'.$backdirarray[$whereis]);
				while($entry = $dir->read()) {
					$entry = "./".$backdirarray[$whereis]."/$entry";
					if(is_file($entry) && preg_match("/\.sql/i", $entry) && preg_match("/$filedirname/i", $entry)) {
						$filesize = filesize($entry);
						@$fp = fopen($entry, 'rb');
						@$identify = explode(',', base64_decode(preg_replace("/^# Identify:\s*(\w+).*/s", "\\1", fgets($fp, 256))));
						@fclose ($fp);
	
						$exportlog[$identify[0]] = array(	'version' => $identify[1],
											'type' => $identify[2],
											'method' => $identify[3],
											'volume' => $identify[4],
											'filename' => $entry,
											'size' => $filesize);
					}
				}
				$dir->close();
			}
			krsort($exportlog);
			reset($exportlog);
	
			$exportinfo = '<table>
							<caption>&nbsp;&nbsp;&nbsp;数据库文件列表</caption>
							<tr>
							<th>文件名</th><th>版本</th>
							<th>时间</th><th>类型</thd>
							<th>大小</th><td>方式</th>
							<th>卷号</th><th>操作</th></tr>';
			foreach($exportlog as $dateline => $info) {
				$info['dateline'] = is_int($dateline) ? gmdate("Y-m-d H:i", $dateline + 8*3600) : '未知';
					switch($info['type']) {
						case 'full':
							$info['type'] = '全部备份';
							break;
						case 'standard':
							$info['type'] = '标准备份(推荐)';
							break;
						case 'mini':
							$info['type'] = '最小备份';
							break;
						case 'custom':
							$info['type'] = '自定义备份';
							break;
					}
				$info['volume'] = $info['method'] == 'multivol' ? $info['volume'] : '';
				$info['method'] = $info['method'] == 'multivol' ? '多卷' : 'shell';
				$exportinfo .= "<tr>\n".
					"<td><a href=\"$info[filename]\" name=\"".substr(strrchr($info['filename'], "/"), 1)."\">".substr(strrchr($info['filename'], "/"), 1)."</a></td>\n".
					"<td>$info[version]</td>\n".
					"<td>$info[dateline]</td>\n".
					"<td>$info[type]</td>\n".
					"<td>".get_real_size($info[size])."</td>\n".
					"<td>$info[method]</td>\n".
					"<td>$info[volume]</td>\n".
					"<td><a href=\"?action=all_restore&file=$info[filename]&importsubmit=yes&auto=off\">[导入]</a></td>\n</tr>\n";
			}
			$exportinfo .= '</table>';
			echo $exportinfo;
		}
		// 查看目录里的备份文件列表， 二级目录下，其中二级目录是随机产生的
		if(!empty($bakdirname)) {
			$exportlog = array();
			$filedirname = TOOLS_ROOT.'./'.$backdirarray[$whereis].'/'.$bakdirname;
			if(is_dir($filedirname)) {
				$dir = dir($filedirname);
				while($entry = $dir->read()) {
					$entry = $filedirname.'/'.$entry;
					if(is_file($entry) && preg_match("/\.sql/i", $entry)) {
						$filesize = filesize($entry);
						@$fp = fopen($entry, 'rb');
						@$identify = explode(',', base64_decode(preg_replace("/^# Identify:\s*(\w+).*/s", "\\1", fgets($fp, 256))));
						@fclose ($fp);
	
						$exportlog[$identify[0]] = array(	
											'version' => $identify[1],
											'type' => $identify[2],
											'method' => $identify[3],
											'volume' => $identify[4],
											'filename' => $entry,
											'size' => $filesize);
					}
				}
				$dir->close();
			}
			krsort($exportlog);
			reset($exportlog);
	
			$exportinfo = '<table>
					<caption>&nbsp;&nbsp;&nbsp;数据库文件列表</caption>
					<tr>
					<th>文件名</th><th>版本</th>
					<th>时间</th><th>类型</th>
					<th>大小</th><th>方式</th>
					<th>卷号</th><th>操作</th></tr>';
			foreach($exportlog as $dateline => $info) {
				$info['dateline'] = is_int($dateline) ? gmdate("Y-m-d H:i", $dateline + 8*3600) : '未知';
				switch($info['type']) {
					case 'full':
						$info['type'] = '全部备份';
						break;
					case 'standard':
						$info['type'] = '标准备份(推荐)';
						break;
					case 'mini':
						$info['type'] = '最小备份';
						break;
					case 'custom':
						$info['type'] = '自定义备份';
						break;
				}
				$info['volume'] = $info['method'] == 'multivol' ? $info['volume'] : '';
				$info['method'] = $info['method'] == 'multivol' ? '多卷' : 'shell';
				$exportinfo .= "<tr>\n".
						"<td><a href=\"$info[filename]\" name=\"".substr(strrchr($info['filename'], "/"), 1)."\">".substr(strrchr($info['filename'], "/"), 1)."</a></td>\n".
						"<td>$info[version]</td>\n".
						"<td>$info[dateline]</td>\n".
						"<td>$info[type]</td>\n".
						"<td>".get_real_size($info[size])."</td>\n".
						"<td>$info[method]</td>\n".
						"<td>$info[volume]</td>\n".
						"<td><a href=\"?action=all_restore&file=$info[filename]&importsubmit=yes&auto=off\">[导入]</a></td>\n</tr>\n";
			}
			$exportinfo .= '</table>';
			echo $exportinfo;
		}
		echo "<br>";
		cexit("");
	}
} elseif($action == 'all_runquery') {//运行sql
		if(!empty($_POST['sqlsubmit']) && $_POST['queries']) {
			runquery($queries);
		}
		htmlheader();
		runquery_html();
		htmlfooter();	
} elseif($action == 'all_checkcharset') {//编码检测
	$maincharset = $dbcharset;
	$tooltip = '<h4>编码检查</h4>'."<div class=\"specialdiv\">操作提示：<ul>
				<li>MySQL版本在4.1以上才有字符集的设定，所以数据库4.1版本以上的才能使用本功能</li>
				<li>如果某些字段的字符集不一致，有可能会导致程序中出现乱码，尽量把字符集不一致的字段转换成统一字符集</li>
				<li>有关MySQL编码机制可以参考 <a href='http://www.discuz.net/viewthread.php?tid=1022673' target='_blank'>点击查看</a></li>
				<li>一些关于MySQL编码方面的<a href='http://www.discuz.net/viewthread.php?tid=1070306' target='_blank'>教程</a></li>
				<li><font color=red>此功能只是帮你将数据库字段的编码转换，并不进行数据库内数据的编码转换，修复前请先备份你的数据库，以免造成不必要的损失，如果因为你没有备份数据库造成的损失与本程序无关</font></li>
				<li><font color=red>如需要转换数据库内的数据编码，请使用“<a href='?action=datago'>转码</a>”功能</font></li>
				</ul></div>";
	if($my_version > '4.1') {
		if($repairsubmit) {
			htmlheader();
			echo $tooltip;
			if(!is_array($repair)) {
				$repair=array();
				show_tools_message('没有修复任何字段', 'tools.php?action=all_checkcharset');
				htmlfooter();
				exit;
			}
			foreach($repair as $key=>$value) {
				$tableinfo = '';
				$tableinfo = explode('|', $value);
				$tablename = $tableinfo[0];
				$collation = $tableinfo[1];
				$maincharset = $tableinfo[2];
                		$query = mysql_query("SHOW CREATE TABLE $tablename");
				while($createsql = mysql_fetch_array($query)) {
					$colationsql = explode(",\n",$createsql[1]);
					foreach($colationsql as $numkey => $collsql) {
						if(strpos($collsql,'`'.$collation.'`')) {	
							if(strpos($collsql,'character set') > 0){
								$collsql = substr($collsql,0,strpos($collsql,'character set'));	
							} else {
								$collsql = substr($collsql,0,strpos($collsql,'NOT NULL'));
							}
							$collsql = $collsql." character set $maincharset NOT NULL";							
							$changesql = 'alter table '.$tablename.' change `'.$collation.'` '.$collsql;
							mysql_query($changesql);
						}
					}
				}
			}
			show_tools_message('修复完毕', 'tools.php?action=all_checkcharset');
			htmlfooter();
			exit;
		} else {
			$sql = "SELECT `TABLE_NAME` AS `Name`, `TABLE_COLLATION` AS `Collation` FROM `information_schema`.`TABLES` WHERE   ".(strpos("php".PHP_OS,"WIN")?"":"BINARY")."`TABLE_SCHEMA` IN ('$dbname') AND TABLE_NAME like '$tablepre%'";
			$query = @mysql_query($sql);
			$dbtable = array();
			$chars = array('gbk' => 0,'big5' => 0,'utf8' => 0,'latin1' => 0);
			if(!$query) {
				htmlheader();
				errorpage('您当前的数据库版本无法检查字符集设定，可能是由于版本过低不支持检查语句导致', '', 0, 0);
				htmlfooter();
				exit;
			}
			while($dbdetail = mysql_fetch_array($query)) {
				$dbtable[$dbdetail["Name"]]["Collation"] = pregcharset($dbdetail["Collation"],1); 
				$dbtable[$dbdetail["Name"]]["tablename"] = $dbdetail["Name"]; 
				$tablequery = mysql_query("SHOW FULL FIELDS FROM `".$dbdetail["Name"]."`");
				while($tables= mysql_fetch_array($tablequery)) {
					if(!empty($tables["Collation"])) {
						$collcharset = pregcharset($tables["Collation"], 0);
						$tableschar[$collcharset][$dbdetail["Name"]][] = $tables["Field"];
						$chars[pregcharset($tables["Collation"], 0)]++;
					}
				}
				
			}
		}
	}

	htmlheader();
	echo $tooltip;
	if($my_version > '4.1') {
	echo'<div class="tabbody">
		<style>.tabbody p em { color:#09C; padding:0 10px;} .char_div { margin-top:30px; margin-bottom:30px;} .char_div h4, .notice h4 { font-weight:600; font-size:16px; margin:0; padding:0; margin-bottom:10px;}</style>
		<div class="char_div"><h5>数据库('.$dbname.')的字符集统计：</h5>
		<table style="width:40%; margin:0; margin-bottom:20px;"><tr><th>gbk字段</th><th>big5字段</th><th>utf8字段</th><th>latin1字段</th></tr><tr><td>'.$chars[gbk].'&nbsp;</td><td>'.$chars[big5].'&nbsp;</td><td>'.$chars[utf8].'&nbsp;</td><td>'.$chars[latin1].'&nbsp;</td></tr></table>
		<div class="notice">
			<h5>下列字段可能存在编码设置异常：</h5>';
			?>
<script type="text/JavaScript">
	function setrepaircheck(obj, form, table, char) {
		eval('var rem = /^' + table + '\\|.+?\\|.+?\\|' + char + '$/;');
		eval('var rechar = /latin1/;');
		for(var i = 0; i < form.elements.length; i++) {
			var e = form.elements[i];
			if(e.type == 'checkbox' && e.name == 'repair[]') {
				if(rem.exec(e.value) != null) {
					if(obj.checked) {
						if(rechar.exec(e.value) != null) {
							e.checked = true;
						} else {
							e.checked = true;
						}
					} else {
						e.checked = false;
					}
				}
			}
		}
	}
</script>
<?php
		  foreach($chars as $char => $num) {
			  if($char != $maincharset) {
				if(is_array($tableschar[$char])) {
					echo '<form name="form" action="" method="post">';
					foreach($tableschar[$char] as $tablename => $fields) {
					   	echo '<table style="margin-left:0; width:40%;">
							<tr>
								<th><input type="checkbox" id="tables[]" style="border-style:none;"  name="chkall"  onclick="setrepaircheck(this, this.form, \''.$tablename.'\', \''.$char.'\');"  value="'.$tablename.'">全选</th>
								<th width=60%><strong>'.$tablename.'</strong> <font color="red">表异常的字段</font></th>
								<th>编码</th>
							</tr>';
							foreach($fields as $collation) {
								echo'<tr><td><input type="checkbox" style="border-style:none;"';
								echo 'id="fields['.$tablename.'][]"';
								echo 'name=repair[] value="'.$tablename.'|'.$collation.'|'.$maincharset.'|'.$char.'">';
								echo '</td><td>'.$collation.'</td><td><font color="red">'.$char.'</font></td></tr>';
							}
						echo '</table>';
					}
				}	 
			}
		}
		echo '<input type="submit" value="把指定的字段编码转换为'.$maincharset.'" name="repairsubmit" onclick="javascript:if(confirm(\'Tools工具箱只是尝试帮你修复数据库字段字符集，修复前请先备份你的数据库，以免造成不必要的损失，如果因为你没有备份数据库造成的损失与本程序无关\'));else return false;"></form>';
		echo '<br /><br /><br /></div> </div>';
	} else {
		errorpage('MySQL数据库版本在4.1以下，没有字符集设定，无需检测', '', 0, 0);
	}
	htmlfooter();
} elseif($action == 'dz_filecheck') {//搜索未知文件
	htmlheader();
	if($begin != 1) {
		echo '<h4>文件校验</h4>';
		infobox('文件校验是针对 Discuz! 官方发布的文件为基础进行核对，点击下面按钮开始进行校验。','tools.php?action=dz_filecheck&begin=1');
		htmlfooter();
		exit;
	}
	
	$md5data = array();
	if(!$dz_files = @file(TOOLS_ROOT.'./admin/discuzfiles.md5')) {
		errorpage('没有找到md5文件');
	}
	checkfiles('./', '\.php', 0, 'config.inc.php');
	checkfiles('api/', '\.php');
	checkfiles('admin/', '\.php');
	checkfiles('archiver/', '\.php');
	checkfiles('include/', '\.php|\.js|\.htm');
	checkfiles('modcp/', '\.php');
	checkfiles('plugins/', '\.php');
	checkfiles('templates/default/', '\.htm|\.php');
	checkfiles('uc_client/', '\.php',0);
	checkfiles('uc_client/control/', '\.php',0);
	checkfiles('uc_client/lib/', '\.php',0);
	checkfiles('uc_client/model/', '\.php',0);
	checkfiles('wap/', '\.php');
	
	$modifylists = $deletedfiles = $unknownfiles = array();
	
	docheckfiles($dz_files,$md5data);
	checkfilesoutput($modifylists,$deletedfiles,$unknownfiles);
	htmlfooter();
} elseif($action == 'dz_mysqlclear') {//数据库清理
	ob_implicit_flush();
	define('IN_DISCUZ', TRUE);
	if(@!include("./config.inc.php")) {
		if(@!include("./config.php")) {
			htmlheader();
			cexit("<h4>请先上传config文件以保证您的数据库能正常链接！</h4>");
		}
	}
	require './include/db_'.$database.'.class.php';
	$db = new dbstuff;
	$db->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect);
	$db->select_db($dbname);

	if(!get_cfg_var('register_globals')) {
		@extract($_GET, EXTR_SKIP);
	}
	$rpp = "1000"; //每次处理多少条数据
	$totalrows = isset($totalrows) ? $totalrows : 0;
	$convertedrows = isset($convertedrows) ? $convertedrows : 0;
	$start = isset($start) && $start > 0 ? $start : 0;
	$sqlstart = isset($start) && $start > $convertedrows ? $start - $convertedrows : 0;
	$end = $start + $rpp - 1;
	$stay = isset($stay) ? $stay : 0;
	$converted = 0;
	$step = isset($step) ? $step : 0;
	$info = isset($info) ? $info : '';
	$action = array(
		'1'=>'冗余回复数据清理',
		'2'=>'冗余附件数据清理',
		'3'=>'冗余会员数据清理',
		'4'=>'冗余板块数据清理',
		'5'=>'主题信息清理',
		'6'=>'完成数据冗余清理'
					);
	$steps = count($action);
	$actionnow = isset($action[$step]) ? $action[$step] : '结束';
	$maxid = isset($maxid) ? $maxid : 0;
	$tableid = isset($tableid) ? $tableid : 1;
	htmlheader();
	if($step == 0) {
	?>
		<h4>数据库冗余数据清理</h4>
		<h5>清理项目详细信息</h5>
		<table>
		<tr><th width="30%">Posts表的清理</th><td>[<a href="?action=dz_mysqlclear&step=1&stay=1">单步清理</a>]</td></tr>
		<tr><th width="30%">Attachments表的清理</th><td>[<a href="?action=dz_mysqlclear&step=2&stay=1">单步清理</a>]</td></tr>
		<tr><th width="30%">Members表的清理</th><td>[<a href="?action=dz_mysqlclear&step=3&stay=1">单步清理</a>]</td></tr>
		<tr><th width="30%">Forums表的清理</th><td>[<a href="?action=dz_mysqlclear&step=4&stay=1">单步清理</a>]</td></tr>
		<tr><th width="30%">Threads表的清理</th><td>[<a href="?action=dz_mysqlclear&step=5&stay=1">单步清理</a>]</td></tr>
		<tr><th width="30%">所有表的清理</th><td>[<a href="?action=dz_mysqlclear&step=1&stay=0">全部清理</a>]</td></tr>
		</table>
	<?php
		specialdiv();
		echo "<script>$('jsmenu').style.display='inline';</script>";
	} elseif($step == '1') {
		if($start == 0) {
			validid('pid','posts');
		}
		$query = "SELECT pid, tid FROM {$tablepre}posts WHERE pid >= $start AND pid <= $end";
		$posts = $db->query($query);
			while ($post = $db->fetch_array($posts)) {
				$query = $db->query("SELECT tid FROM {$tablepre}threads WHERE tid='".$post['tid']."'");
				if($db->result($query, 0)) {
					} else {
						$convertedrows ++;
						$db->query("DELETE FROM {$tablepre}posts WHERE pid='".$post['pid']."'");
					}
				$converted = 1;
				$totalrows ++;
		}
			if($converted || $end < $maxid) {
				continue_redirect();
			} else {
				stay_redirect();
			}
	} elseif($step == '2') {
		if($start == 0) {
			validid('aid','attachments');
		}
		$query = "SELECT aid,pid,attachment FROM {$tablepre}attachments WHERE aid >= $start AND aid <= $end";
		$posts = $db->query($query);
			while ($post = $db->fetch_array($posts)) {
				$query = $db->query("SELECT pid FROM {$tablepre}posts WHERE pid='".$post['pid']."'");
				if($db->result($query, 0)) {
					} else {
						$convertedrows ++;
						$db->query("DELETE FROM {$tablepre}attachments WHERE aid='".$post['aid']."'");
						$attachmentdir = TOOLS_ROOT.'./attachments/';
						@unlink($attachmentdir.$post['attachment']);
					}
				$converted = 1;
				$totalrows ++;
		}
			if($converted || $end < $maxid) {
				continue_redirect();
			} else {
				stay_redirect();
			}
	} elseif($step == '3') {
		if($start == 0) {
			validid('uid','memberfields');
		}
		$query = "SELECT uid FROM {$tablepre}memberfields WHERE uid >= $start AND uid <= $end";
		$posts = $db->query($query);
			while ($post = $db->fetch_array($posts)) {
				$query = $db->query("SELECT uid FROM {$tablepre}members WHERE uid='".$post['uid']."'");
					if($db->result($query, 0)) {
					} else {
						$convertedrows ++;
						$db->query("DELETE FROM {$tablepre}memberfields WHERE uid='".$post['uid']."'");
					}
				$converted = 1;
				$totalrows ++;
		}
			if($converted || $end < $maxid) {
				continue_redirect();
			} else {
				stay_redirect();
			}
	} elseif($step == '4') {
		if($start == 0) {
			validid('fid','forumfields');
		}
		$query = "SELECT fid FROM {$tablepre}forumfields WHERE fid >= $start AND fid <= $end";
		$posts = $db->query($query);
			while ($post = $db->fetch_array($posts)) {
				$query = $db->query("SELECT fid FROM {$tablepre}forums WHERE fid='".$post['fid']."'");
				if($db->result($query, 0)) {
					} else {
						$convertedrows ++;
						$db->query("DELETE FROM {$tablepre}forumfields WHERE fid='".$post['fid']."'");
					}
				$converted = 1;
				$totalrows ++;
		}
			if($converted || $end < $maxid) {
				continue_redirect();
			} else {
				stay_redirect();
			}
	} elseif($step == '5') {
		if($start == 0) {
			validid('tid','threads');
		}
		$query = "SELECT tid, subject FROM {$tablepre}threads WHERE tid >= $start AND tid <= $end";
		$posts = $db->query($query);
			while ($threads = $db->fetch_array($posts)) {
				$query = $db->query("SELECT COUNT(*) FROM {$tablepre}posts WHERE tid='".$threads['tid']."' AND invisible='0'");
				$replynum = $db->result($query, 0) - 1;
				if($replynum < 0) {
					$db->query("DELETE FROM {$tablepre}threads WHERE tid='".$threads['tid']."'");
				} else {
					$query = $db->query("SELECT a.aid FROM {$tablepre}posts p, {$tablepre}attachments a WHERE a.tid='".$threads['tid']."' AND a.pid=p.pid AND p.invisible='0' LIMIT 1");
					$attachment = $db->num_rows($query) ? 1 : 0;//修复附件
					$query  = $db->query("SELECT pid, subject, rate FROM {$tablepre}posts WHERE tid='".$threads['tid']."' AND invisible='0' ORDER BY dateline LIMIT 1");
					$firstpost = $db->fetch_array($query);
					$firstpost['subject'] = trim($firstpost['subject']) ? $firstpost['subject'] : $threads['subject']; //针对某些转换过来的论坛的处理
					$firstpost['subject'] = addslashes($firstpost['subject']);
					@$firstpost['rate'] = $firstpost['rate'] / abs($firstpost['rate']);//修复发帖
					$query  = $db->query("SELECT author, dateline FROM {$tablepre}posts WHERE tid='".$threads['tid']."' AND invisible='0' ORDER BY dateline DESC LIMIT 1");
					$lastpost = $db->fetch_array($query);//修复最后发帖
					$db->query("UPDATE {$tablepre}threads SET subject='".$firstpost['subject']."', replies='$replynum', lastpost='".$lastpost['dateline']."', lastposter='".addslashes($lastpost['author'])."', rate='".$firstpost['rate']."', attachment='$attachment' WHERE tid='".$threads['tid']."'", 'UNBUFFERED');
					$db->query("UPDATE {$tablepre}posts SET first='1', subject='".$firstpost['subject']."' WHERE pid='".$firstpost['pid']."'", 'UNBUFFERED');
					$db->query("UPDATE {$tablepre}posts SET first='0' WHERE tid='".$threads['tid']."' AND pid<>'".$firstpost['pid']."'", 'UNBUFFERED');
					$convertedrows ++;
				}
				$converted = 1;
				$totalrows ++;
			}
			if($converted || $end < $maxid) {
				continue_redirect();
			} else {
				stay_redirect();
			}
	} elseif($step == '6') {
		echo '<h4>数据库冗余数据清理</h4><table>
			  <tr><th>完成冗余数据清理</th></tr><tr>
			  <td><br>所有数据清理操作完毕.&nbsp;共处理<font color=red>'.$allconvertedrows.'</font>条数据.<br><br></td></tr></table>';
	}
	
	htmlfooter();
	
} elseif($action == 'uch_dz_replace') {//内容替换s
	htmlheader();
	$rpp = "500"; //每次处理多少条数据
	$totalrows = isset($totalrows) ? $totalrows : 0;
	$convertedrows = isset($convertedrows) ? $convertedrows : 0;
	$convertedtrows	= isset($convertedtrows) ? $convertedtrows : 0;
	$start = isset($start) && $start > 0 ? $start : 0;
	$end = $start + $rpp - 1;
	$converted = 0;
	$maxid = isset($maxid) ? $maxid : 0;
	$threads_mod = isset($threads_mod) ? $threads_mod : 0;
	$threads_banned = isset($threads_banned) ? $threads_banned : 0;
	$posts_mod = isset($posts_mod) ? $posts_mod : 0;
	if($stop == 1) {
		echo "<h4>应用过滤规则</h4><table>
			<tr>
			<th>暂停替换</th>
			</tr>";
		$threads_banned > 0 && print("<tr><td><br><li>".$threads_banned."个主题被放入回收站.</li></td></tr>");
		$threads_mod > 0 && print("<tr><td><br><li>".$threads_mod."个主题被放入审核列表.</li></td></tr>");
		$posts_mod > 0 && print("<tr><td><br><li>".$posts_mod."个回复被放入审核列表.</li></td></tr>");
		echo "<tr><td><li>替换了".$convertedrows."条记录</li></td></tr>";
		echo "<tr><td><a href='?action=uch_dz_replace&step=".$step."&start=".($end + 1 - $rpp * 2)."&stay=$stay&totalrows=$totalrows&convertedrows=$convertedrows&maxid=$maxid&replacesubmit=1&threads_banned=$threads_banned&threads_mod=$threads_mod&posts_mod=$posts_mod'>继续</a></td></tr>";
		echo "</table>";
		htmlfooter();
	}
	ob_implicit_flush();
	if($whereis == 'is_uch') {
		$selectwords_cache = './data/selectwords_cache.php';
	} elseif($whereis == 'is_dz') {
		$selectwords_cache = './forumdata/cache/selectwords_cache.php';
	}

	if(isset($replacesubmit) || $start > 0) {
		if(!file_exists($selectwords_cache) || is_array($selectwords)) {
			if(count($selectwords) < 1) {
				echo "<h4>应用过滤规则</h4><table><tr><th>提示信息</th></tr><tr><td>您还没有选择要过滤的词语. &nbsp [<a href=tools.php?action=uch_dz_replace>返回</a>]</td></tr></table>";
				htmlfooter();
			} else {
				$fp = @fopen($selectwords_cache,w);
				$content = "<?php \n";
				$selectwords = implode(',',$selectwords);
				$content .= "\$selectwords = '$selectwords';\n?>";
				if(!@fwrite($fp,$content)) {
					echo "写入缓存文件$selectwords_cache 错误,请确认路径是否可写. &nbsp [<a href=tools.php?action=uch_dz_replace>返回</a>]";
					htmlfooter();
				} else {
					require_once "$selectwords_cache";
				}
				@fclose($fp);
			}
		} else {
			require_once "$selectwords_cache";
		}
		$array_find = $array_replace = $array_findmod = $array_findbanned = array();
		
		if($whereis == 'is_dz') {
			$query = mysql_query("SELECT find,replacement from {$tablepre}words where id in($selectwords)");//获得现有规则{BANNED}放回收站 {MOD}放进审核列表
			while($row = mysql_fetch_array($query)) {
				$find = preg_quote($row['find'], '/');
				$replacement = $row['replacement'];
				if($replacement == '{BANNED}') {
					$array_findbanned[] = $find;
				} elseif($replacement == '{MOD}') {
					$array_findmod[] = $find;
				} else {
					$array_find[] = $find;
					$array_replace[] = $replacement;
				}
			}
		} elseif($whereis == 'is_uch') {
			$query = mysql_query("SELECT datavalue FROM `uchome_data` WHERE `var` = 'censor'");
			$query = mysql_fetch_array($query);
			$censor = explode("\n",$query[datavalue]);
			foreach($censor as $key => $value) {
				if(in_array($key,explode(',',$selectwords))){
					$rows = explode('=',$value);
					$row[] = $rows;					
				}
			}
			foreach($row as $value) {
				$find = preg_quote($value[0], '/');
				$replacement = $value[1];
				if($replacement == '{BANNED}') {
					$array_findbanned[] = $find;
				} else {
					$array_find[] = $find;
					$array_replace[] = $replacement;
				}				
			}
		}
		
		$array_find = topattern_array($array_find);
		$array_findmod = topattern_array($array_findmod);
		$array_findbanned = topattern_array($array_findbanned);
		if($whereis == 'is_dz'){
			if($maxid == 0) {
				validid('pid','posts');
			}
			//查询posts表准备替换
			$sql = "SELECT pid, tid, first, subject, message from {$tablepre}posts where pid >= $start and pid <= $end";
			$query = mysql_query($sql);
			while($row =  mysql_fetch_array($query)) {
				$pid = $row['pid'];
				$tid = $row['tid'];
				$subject = $row['subject'];
				$message = $row['message'];
				$first = $row['first'];
				$displayorder = 0;//  -2审核 -1回收站
				if(count($array_findmod) > 0) {
					foreach($array_findmod as $value) {
						if(preg_match($value,$subject.$message)) {
							$displayorder = '-2';
							break;
						}
					}
				}
				if(count($array_findbanned) > 0) {
					foreach($array_findbanned as $value) {
						if(preg_match($value,$subject.$message)) {
							$displayorder = '-1';
							break;
						}
					}
				}
				if($displayorder < 0) {
					if($displayorder == '-2' && $first == 0) {//如成立就移到审核回复
						$posts_mod ++;
						mysql_query("UPDATE {$tablepre}posts SET invisible = '$displayorder' WHERE pid = $pid");
					} else {
						if($db->affected_rows($db->query("UPDATE {$tablepre}threads SET displayorder = '$displayorder' WHERE tid = $tid and displayorder >= 0")) > 0) {
							$displayorder == '-2' && $threads_mod ++;
							$displayorder == '-1' && $threads_banned ++;
						}
					}
				}
				$subject = preg_replace($array_find,$array_replace,addslashes($subject));
				$message = preg_replace($array_find,$array_replace,addslashes($message));
				if($subject != addslashes($row['subject']) || $message != addslashes($row['message'])) {
					if(mysql_query("UPDATE {$tablepre}posts SET subject = '$subject', message = '$message' WHERE pid = $pid")) {
						$convertedrows ++;
					}
				}
				$converted = 1;
			}
			//查询threads表
			$sql2 = "SELECT tid,subject from {$tablepre}threads where tid >= $start and tid <= $end";
			$query2 = mysql_query($sql2);
			while($row2 = mysql_fetch_array($query2)) {
				$tid = $row2['tid'];
				$subject = $row2['subject'];
				$subject = preg_replace($array_find,$array_replace,addslashes($subject));
				if($subject != addslashes($row2['subject'])) {
					if(mysql_query("UPDATE {$tablepre}threads SET subject = '$subject' WHERE tid = $tid")) {
						$convertedrows ++;
					}
				}
				$converted = 1;
			}
		} elseif ($whereis == 'is_uch') {
			if($maxid == 0) {
				validid('blogid','blog');
				$temp = $maxid;
				validid('cid','comment');
				$temp = max($temp,$maxid);
				validid('oid','polloption');
				$temp = max($temp,$maxid);
				validid('pid','post');
				$temp = max($temp,$maxid);
				validid('doid','doing');
				$temp = max($temp,$maxid);
				$maxid = $temp;
			}
			//blog处理
			$sql =  "SELECT b.blogid,b.subject,f.message from {$tablepre}blog b,{$tablepre}blogfield f where b.blogid=f.blogid AND b.blogid >= $start and b.blogid <= $end";
			$query = mysql_query($sql);
			while($row =  mysql_fetch_array($query)) {
				$blogid = $row['blogid'];
				$subject = $row['subject'];
				$subject = preg_replace($array_find,$array_replace,addslashes($subject));
				if($subject != addslashes($row['subject']) || $message != addslashes($row['message'])) {
					if(mysql_query("UPDATE {$tablepre}blog SET subject = '$subject' WHERE blogid = $blogid")) {
						mysql_query("UPDATE {$tablepre}blogfield SET message = '$message' WHERE blogid = $blogid");
						$convertedrows ++;
					}
				}
				$converted = 1;
			}
			//comment处理
			$sql =  "SELECT cid,message from {$tablepre}comment where cid >= $start and cid <= $end";
			$query = mysql_query($sql);
			while($row =  mysql_fetch_array($query)) {
				$cid = $row['cid'];
				$message = $row['message'];
				$message = preg_replace($array_find,$array_replace,addslashes($message));
				if($message != addslashes($row['message'])) {
					if(mysql_query("UPDATE {$tablepre}coment SET message = '$message' WHERE cid = $cid")) {
						$convertedrows ++;
					}
				}
				$converted = 1;
			}
			//poll处理
			$sql =  "SELECT p.pid,p.subject,f.message,f.option from {$tablepre}poll p,{$tablepre}pollfield f where p.pid=f.pid AND p.pid >= $start and p.pid <= $end";
			$query = mysql_query($sql);
			while($row =  mysql_fetch_array($query)) {
				$pid = $row['pid'];
				$subject = $row['subject'];
				$message = $row['message'];
				$option = unserialize($row['option']);
				$subject = preg_replace($array_find,$array_replace,addslashes($subject));
				$message = preg_replace($array_find,$array_replace,addslashes($message));
				$option = addslashes(serialize(preg_replace($array_find,$array_replace,$option)));
				if($message != addslashes($row['message']) || $subject != addslashes($row['subject']) || $option != addslashes($row['option'])) {
					if(mysql_query("UPDATE {$tablepre}poll SET subject = '$subject' WHERE pid = $pid")) {
						mysql_query("UPDATE {$tablepre}pollfield SET `message` = '$message' WHERE pid = $pid");
						mysql_query("UPDATE {$tablepre}pollfield SET `option` = '$option' WHERE pid = $pid");
						$convertedrows ++;
					}
				}
				$converted = 1;
			}
			//polloption处理
			$sql =  "SELECT oid,option from {$tablepre}polloption where oid >= $start and oid <= $end";
			$query = mysql_query($sql);
			while($row =  mysql_fetch_array($query)) {
				$oid = $row['oid'];
				$option = $row['option'];
				$option = preg_replace($array_find,$array_replace,addslashes($option));
				if($option != addslashes($row['option'])) {
					if(mysql_query("UPDATE {$tablepre}polloption SET option = '$option' WHERE oid = $oid")) {
						$convertedrows ++;
					}
				}
				$converted = 1;
			}
			//polloption处理
			$sql =  "SELECT oid,option from {$tablepre}polloption where oid >= $start and oid <= $end";
			$query = mysql_query($sql);
			while($row =  mysql_fetch_array($query)) {
				$oid = $row['oid'];
				$option = $row['option'];
				$option = preg_replace($array_find,$array_replace,addslashes($option));
				if($option != addslashes($row['option'])) {
					if(mysql_query("UPDATE {$tablepre}polloption SET option = '$option' WHERE oid = $oid")) {
						$convertedrows ++;
					}
				}
				$converted = 1;
			}
			//post处理
			$sql =  "SELECT pid,message from {$tablepre}post where pid >= $start and pid <= $end";
			$query = mysql_query($sql);
			while($row =  mysql_fetch_array($query)) {
				$pid = $row['pid'];
				$message = $row['message'];
				$message = preg_replace($array_find,$array_replace,addslashes($message));
				if($message != addslashes($row['message'])) {
					if(mysql_query("UPDATE {$tablepre}post SET message = '$message' WHERE pid = $pid")) {
						$convertedrows ++;
					}
				}
				$converted = 1;
			}
			//doing处理
			$sql =  "SELECT doid,message from {$tablepre}doing where doid >= $start and doid <= $end";
			$query = mysql_query($sql);
			while($row =  mysql_fetch_array($query)) {
				$doid = $row['doid'];
				$message = $row['message'];
				$message = preg_replace($array_find,$array_replace,addslashes($message));
				if($message != addslashes($row['message'])) {
					if(mysql_query("UPDATE {$tablepre}doing SET message = '$message' WHERE doid = $doid")) {
						$convertedrows ++;
					}
				}
				$converted = 1;
			}
			//spacefield处理
			$sql =  "SELECT uid,note,spacenote from {$tablepre}spacefield where uid >= $start and uid <= $end";
			$query = mysql_query($sql);
			while($row =  mysql_fetch_array($query)) {
				$uid = $row['uid'];
				$note = $row['note'];
				$spacenote = $row['spacenote'];
				$note = preg_replace($array_find,$array_replace,addslashes($note));
				$spacenote = preg_replace($array_find,$array_replace,addslashes($spacenote));
				if($note != addslashes($row['note']) || $spacenote != addslashes($row['spacenote'])) {
					if(mysql_query("UPDATE {$tablepre}spacefield SET note = '$note' WHERE uid = $uid")) {
						mysql_query("UPDATE {$tablepre}spacefield SET spacenote = '$spacenote' WHERE uid = $uid");
						$convertedrows ++;
					}
				}
				$converted = 1;
			}
		}

		//完成
		if($converted  || $end < $maxid) {
			continue_redirect('uch_dz_replace',"&replacesubmit=1&threads_banned=$threads_banned&threads_mod=$threads_mod&posts_mod=$posts_mod");
		} else {
			echo "<h4>应用过滤规则</h4><table>
						<tr>
							<th>应用过滤规则完毕</th>
						</tr>";
			if($threads_banned > 0) { echo "<tr><td><li>".$threads_banned."个主题被放入回收站.</li></td></tr>";}
			if($threads_mod > 0) {echo "<tr><td><li>".$threads_mod."个主题被放入审核列表.</li></td></tr>";}
			if($posts_mod > 0) {echo "<tr><td><li>".$posts_mod."个回复被放入审核列表.</li></td></tr>";}
			echo "<tr><td><li>替换了".$convertedrows."条记录</li></td></tr>";
			echo "</table>";
			@unlink($selectwords_cache);
		}
	} else {
		if(mysql_get_server_info > '4.1') {
			$serverset = 'character_set_connection=gbk, character_set_results=gbk, character_set_client=binary';
			$serverset && mysql_query("SET $serverset");
		}
		$i = 1;
		if ($whereis == 'is_dz') {
			define('IN_DISCUZ',TRUE);
			require_once "./forumdata/cache/cache_censor.php";
			$censorarray = $_DCACHE['censor'];
			$query = mysql_query("select * from {$tablepre}words");
		} elseif($whereis == 'is_uch') {
			define('IN_UCHOME',TRUE);
			require_once "./data/data_censor.php";
			$censorarray = $_SGLOBAL['censor'];
			$query = mysql_query("SELECT datavalue FROM `uchome_data` WHERE `var` = 'censor'");
			$query = mysql_fetch_array($query);
			$censor = explode("\n",$query[datavalue]);
			foreach($censor as $key => $value) {
				$rows = explode('=',$value);
				$row[] = $rows; 
			}
		}

		if(count($censorarray) < 1) {
			echo "<h4>应用过滤规则</h4><table><tr><th>提示信息</th></tr><tr><td><br>对不起,现在还没有过滤规则,请进入程序后台相关设置.<br><br></td></tr></table>";
			htmlfooter();
		}

		echo '<form method="post" action="tools.php?action=uch_dz_replace">
			<script language="javascript">
				function checkall(form, prefix, checkall) {
					var checkall = checkall ? checkall : \'chkall\';
					for(var i = 0; i < form.elements.length; i++) {
						var e = form.elements[i];
						if(e.name != checkall && (!prefix || (prefix && e.name.match(prefix)))) {
							e.checked = form.elements[checkall].checked;
						}
					}
				}
			</script>
			<h4>应用过滤规则</h4>
				<table>
					<tr>
						<th><input class="checkbox" name="chkall" onclick="checkall(this.form)" type="checkbox" checked>序号</th>
						<th>不良词语</th>
						<th>替换为</th></tr>';
						if($whereis == 'is_dz') {
							while($row = mysql_fetch_array($query)) {
							echo'<tr>
								<td><input class="checkbox" name="selectwords[]" value="'.$row['id'].'" type="checkbox" checked>&nbsp '.$i++.'</td>
								<td>&nbsp '.$row['find'].'</td>
								<td>&nbsp '.stripslashes($row['replacement']).'</td>
							</tr>';
							}
						} elseif($whereis == 'is_uch') {
							foreach($row as $key => $rowvalue) {
								echo'<tr>
								<td><input class="checkbox" name="selectwords[]" value="'.$key.'" type="checkbox" checked>&nbsp '.$i++.'</td>
								<td>&nbsp '.$rowvalue[0].'</td>
								<td>&nbsp '.stripslashes($rowvalue[1]).'</td>
								</tr>';	
							}
						}

			echo '</table>
				<input type="submit" name=replacesubmit value="开始替换">
			</form>
			<div class="specialdiv">
				<h6>注意：</h6>
				<ul>
				<li>本程序会按照论坛现有过滤规则操作所有帖子内容.如需修改请<a href="./admincp.php?action=censor" target=\'_blank\'>进论坛后台</a>。</li>
				<li>上表列出了您论坛当前的过滤词语.</li>
				</ul></div><br><br>';
	}
	htmlfooter();
} elseif($action == 'all_updatecache') {//更新缓存
  	if($whereis =='is_dz') {
		$clearmsg = dz_updatecache();
	} elseif($whereis == 'is_uch') {
		$clearmsg = uch_updatecache();
	} elseif($whereis == 'is_ss') {
		$clearmsg = ss_updatecache();
		}
	htmlheader();
	echo '<h4>更新缓存</h4><table><tr><th>提示信息</th></tr><tr><td>';
	if($clearmsg == '') $clearmsg = '更新缓存完毕.';
	echo $clearmsg.'</td></tr></table>';
	htmlfooter();
} elseif($action == 'all_setadmin') {//重置管理员帐号密码，
	$sql_findadmin = '';
	$sql_select = '';
	$sql_update = '';
	$sql_rspw = '';
	$secq = '';
	$rspw = '';
	$username = '';
	$uid = '';
	all_setadmin_set($tablepre,$whereis);
	$info = '';
	$info_uc = '';	
	htmlheader();
	?>
	<h4>找回管理员</h4>
	<?php
		//查询已经存在的管理员
	if($whereis != 'is_uc') {
		$findadmin_query = mysql_query($sql_findadmin);
		$admins = '';
		while($findadmins = mysql_fetch_array($findadmin_query)) {
			$admins .= ' '.$findadmins[$username];
		}
	}
	if(!empty($_POST['loginsubmit'])) {
		if($whereis == 'is_uc') {
			define(ROOT_DIR,dirname(__FILE__)."/");
			$configfile = ROOT_DIR."./data/config.inc.php";
			$uc_password = $_POST["password"];
			$salt = substr(uniqid(rand()), 0, 6);
			if(!$uc_password) {
				$info = "密码不能为空";
			} else {
				$md5_uc_password = md5(md5($uc_password).$salt);
				$config = file_get_contents($configfile);
				$config = preg_replace("/define\('UC_FOUNDERSALT',\s*'.*?'\);/i", "define('UC_FOUNDERSALT', '$salt');", $config);
				$config = preg_replace("/define\('UC_FOUNDERPW',\s*'.*?'\);/i", "define('UC_FOUNDERPW', '$md5_uc_password');", $config);
				$fp = @fopen($configfile, 'w');
				@fwrite($fp, $config);
				@fclose($fp);
				$info = "UCenter创始人密码更改成功为：$uc_password";
			}
		} else {
			if(@mysql_num_rows(mysql_query($sql_select)) < 1) {
					$info = '<font color="red">无此用户！请检查用户名是否正确。</font>请<a href="?action=all_setadmin">重新输入</a> 或者重新注册.<br><br>';
			} else {
				if($whereis == 'is_dz') {
					$sql_update1 = "UPDATE {$tablepre}members SET adminid='1', groupid='1' WHERE $_POST[loginfield] = '$_POST[where]' limit 1";
					$sql_update2 = "UPDATE {$tablepre}members SET adminid='1', groupid='1',secques='' WHERE $_POST[loginfield] = '$_POST[where]' limit 1";
					$sql_update = $_POST['issecques'] ? $sql_update2 : $sql_update1;
				}
				if($whereis == 'is_ss') {
					$sql_update1 = "UPDATE {$tablepre}members SET  groupid='1' WHERE $_POST[loginfield] = '$_POST[where]' limit 1";
					$sql_update =  $sql_update1;
				}
				if(mysql_query($sql_update)&& !$rspw) {
					$_POST[loginfield] = $_POST[loginfield] == $username ? '用户名' : 'UID号码';
					$info = "已将$_POST[loginfield]为 $_POST[where] 的用户设置成管理员。<br><br>";
				}
				if($rspw) {
					if($whereis == 'is_dz') {
						if($dz_version < 610) {
							$psw = md5($_POST['password']);
							 mysql_query("update {$tablepre}members set password='$psw' where $_POST[loginfield] = '$_POST[where]' limit 1");
						} else {
							//如果是dz，首先要连接到uc里面然后执行$sql_rspw修改密码
							$salt = substr(md5(time()), 0, 6);
							$psw = md5(md5($_POST['password']).$salt);
							mysql_connect(UC_DBHOST, UC_DBUSER, UC_DBPW);
							if($_POST['issecques'] && $dz_version >= 700) {
								$sql_rspw = "UPDATE ".UC_DBTABLEPRE."members SET password='".$psw."',salt='".$salt."',secques='' WHERE $_POST[loginfield] = '$_POST[where]' limit 1";
							} else {
								$sql_rspw = "UPDATE ".UC_DBTABLEPRE."members SET password='".$psw."',salt='".$salt."' WHERE username = '$_POST[where]' limit 1";
							}
							mysql_query($sql_rspw);
						}
						$info .= "已将$_POST[loginfield]为 $_POST[where] 的管理员密码设置为：$_POST[password]<br><br>";
					} elseif($whereis == 'is_uch') {
						$salt = substr(md5(time()), 0, 6);
						$psw = md5(md5($_POST['password']).$salt);
						mysql_connect(UC_DBHOST, UC_DBUSER, UC_DBPW);
						$sql_rspw = "UPDATE ".UC_DBTABLEPRE."members SET password='".$psw."',salt='".$salt."' WHERE $_POST[loginfield] = '$_POST[where]' limit 1";
						mysql_query($sql_rspw);
						$info .= "已将$_POST[loginfield]为 $_POST[where] 的管理员密码设置为：$_POST[password]<br><br>";
					} elseif($whereis == 'is_ss') {
						if($ss_version >= 70) {
							$salt = substr(md5(time()), 0, 6);
							$psw = md5(md5($_POST['password']).$salt);
							mysql_connect(UC_DBHOST, UC_DBUSER, UC_DBPW);
							$sql_rspw = "UPDATE ".UC_DBTABLEPRE."members SET password='".$psw."',salt='".$salt."' WHERE $_POST[loginfield] = '$_POST[where]' limit 1";
							mysql_query($sql_rspw);
						}
						$info .= "已将$_POST[loginfield]为 $_POST[where] 的管理员密码设置为：$_POST[password]<br><br>";
					}
			} else {
				$info_rspw = "管理员密码请登录UC后台去改。 <a href=11 target='_blank'>点击进入UC后台</a>";
			}
			}
		}
		
		errorpage($info,'重置管理员帐号',0,0);
	} else {
	?>
	<form action="?action=all_setadmin" method="post">
		<table>
			<?php
				if($whereis != 'is_uc') {
			?>
				<tr>
					<th>已存在管理员列表</th>
					<td><?php echo $admins; ?></td>
				</tr>
				<tr>
					<th width="30%"><input class="radio" type="radio" name="loginfield" value="<?php echo $username; ?>" checked >用户名<input class="radio" type="radio" name="loginfield" value="<?php echo $uid; ?>" >UID</th>
					<td width="70%"><input class="textinput" type="" name="where" size="25" maxlength="40">
					<?php if(!$rspw) {
						echo '可以把指定的用户提升为管理员';
					}?>
					</td>
				</tr>
			<?php
				} else {
					
				}
			?>
	
			<?php
				if($rspw) {
			?>
				<tr>
					<th width="30%">请输入密码</th>
					<td width="70%"><input class="textinput" type="text" name="password" size="25"></td>
				</tr>
			<?php
				} else {
			?>
				<tr>
					<th width="30%">密码修改提示</th>
					<td width="70%">管理员密码请登录UC后台去改。<a href=11 target='_blank'>点击进入UC后台</a> </td>
				</tr>
			<?php
				}
				if($secq) {
			?>
				<tr>
					<th width="30%">是否清除安全提问</th>
					<td width="70%"><input class="radio" name="issecques" value="1" checked="checked" type="radio">是&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input name="issecques" value="" class="radio" type="radio">否</td>
				</tr>
			<?php
				}
			?>
		</table>
		<input type="submit" name="loginsubmit" value="提 &nbsp; 交">
	</form>
	<?php
	}
	specialdiv();
	htmlfooter();
} elseif($action == 'all_setlock') {//锁定工具箱
	touch($lockfile);
	if(file_exists($lockfile)) {
		echo '<meta http-equiv="refresh" content="3 url=?">';
		errorpage("<h6>成功关闭工具箱！强烈建议您在不需要本程序的时候及时进行删除</h6>",'锁定工具箱');
	} else {
		errorpage('注意您的目录没有写入权限，我们无法给您提供安全保障，请删除论坛根目录下的tool.php文件！','锁定工具箱');
	}
} elseif($action == 'dz_moveattach') {//移动附件存放方式
	define('IN_DISCUZ', TRUE);
	require_once TOOLS_ROOT."./config.inc.php";
	require_once TOOLS_ROOT."./include/db_mysql.class.php";
    	$db = new dbstuff;
	$db->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect, true, $dbcharset);
	$dbuser = $dbpw = $dbname = $pconnect = NULL;
	htmlheader();
	if(!function_exists('mkdir')) {
		echo "<h4>您的服务器不支持mkdir函数，不能够转移附件。</h4>";
		}
	echo "<h4>附件保存方式</h4>";
	$atoption = array(
		'0' => '标准(全部存入同一目录)',
		'1' => '按论坛存入不同目录',
		'2' => '按文件类型存入不同目录',
		'3' => '按月份存入不同目录',
		'4' => '按天存入不同目录',
	);
	if(!empty($_POST['moveattsubmit']) || $step == 1) {
		$rpp = "500"; //每次处理多少条数据
		$totalrows = isset($totalrows) ? $totalrows : 0;
		$convertedrows = isset($convertedrows) ? $convertedrows : 0;
		$start = isset($start) && $start > 0 ? $start : 0;
		$end =	$start + $rpp - 1;
		$converted = 0;
		$maxid = isset($maxid) ? $maxid : 0;
		$newattachsave = isset($newattachsave) ? $newattachsave : 0;
		$step = 1;
		if($start <= 1) {
			$db->query("UPDATE {$tablepre}settings SET value = '$newattachsave' WHERE variable = 'attachsave'");
			$cattachdir = $db->result($db->query("SELECT value FROM {$tablepre}settings WHERE variable = 'attachdir'"), 0);
			validid('aid', 'attachments');
		}
		$attachpath = isset($cattachdir) ? TOOLS_ROOT.$cattachdir : TOOLS_ROOT.'./attachments';
		$query = $db->query("SELECT aid, tid, dateline, filename, filetype, attachment, isimage, thumb FROM {$tablepre}attachments WHERE aid >= $start AND aid <= $end");
		while ($a = $db->fetch_array($query)) {
			$aid = $a['aid'];
			$tid = $a['tid'];
			$dateline = $a['dateline'];
			$filename = $a['filename'];
			$filetype = $a['filetype'];
			$attachment = $a['attachment'];
			$isimage = $a['isimage'];
			$thumb = $a['thumb'];
			$oldpath = $attachpath.'/'.$attachment;
			if(file_exists($oldpath)) {
				$realname = substr(strrchr('/'.$attachment, '/'), 1);
				if($newattachsave == 1) {
					$fid = $db->result($db->query("SELECT fid FROM {$tablepre}threads WHERE tid = '$tid' LIMIT 1"), 0);
					$fid = $fid ? $fid : 0;
				} elseif($newattachsave == 2) {
					$extension = strtolower(fileext($filename));
				}

				if($newattachsave) {
					switch($newattachsave) {
						case 1: $attach_subdir = 'forumid_'.$fid; break;
						case 2: $attach_subdir = 'ext_'.$extension; break;
						case 3: $attach_subdir = 'month_'.gmdate('ym', $dateline); break;
						case 4: $attach_subdir = 'day_'.gmdate('ymd', $dateline); break;
					}
					$attach_dir = $attachpath.'/'.$attach_subdir;
					if(!is_dir($attach_dir)) {
						mkdir($attach_dir, 0777);
						@fclose(fopen($attach_dir.'/index.htm', 'w'));
					}
					$newattachment = $attach_subdir.'/'.$realname;
					
				} else {
					$newattachment = $realname;
				}
				$newpath = $attachpath.'/'.$newattachment;
				$asql1 = "UPDATE {$tablepre}attachments SET attachment = '$newattachment' WHERE aid = '$aid'";
				$asql2 = "UPDATE {$tablepre}attachments SET attachment = '$attachment' WHERE aid = '$aid'";
				if($db->query($asql1)) {
					if(rename($oldpath, $newpath)) {
						if($isimage && $thumb) {
							$thumboldpath = $oldpath.'.thumb.jpg';
							$thumbnewpath = $newpath.'.thumb.jpg';
							rename($thumboldpath, $thumbnewpath);
						}
						$convertedrows ++;
					} else {
						$db->query($asql2);
					}
				}
				$totalrows ++;
			}
		}
		if($converted || $end < $maxid) {
			continue_redirect('dz_moveattach', '&newattachsave='.$newattachsave.'&cattachdir='.$cattachdir);
		} else {
			$msg = "$atoption[$newattachsave] 移动附件完毕<br><li>共有".$totalrows."个附件数据</li><br /><li>移动了".$convertedrows."个附件</li>";
			errorpage($msg,'',0,0);
		}

	} else {
		$attachsave = $db->result($db->query("SELECT value FROM {$tablepre}settings WHERE variable = 'attachsave' LIMIT 1"), 0);
		$checked[$attachsave] = 'checked';
		echo "<form method=\"post\" action=\"tools.php?action=dz_moveattach\" onSubmit=\"return confirm('您确认已经备份好数据库和附件\\n可以进行附件移动操作么？');\">
		<table>
		<tr>
		<th>本设置将重新规范所有附件的存放方式。<font color=\"red\">注意：为防止发生意外，请注意备份数据库和附件。</font></th></tr><tr><td>";
		foreach($atoption as $key => $val) {
			echo "<li style=\"list-style:none;\"><input class=\"radio\" name=\"newattachsave\" type=\"radio\" value=\"$key\" $checked[$key]>&nbsp; $val</input></li><br>";
		}
		echo "
		</td></tr></table>
		<input type=\"hidden\" id=\"oldattachsave\" name=\"oldattachsave\" style=\"display:none;\" value=\"$attachsave\">
		<input type=\"submit\" name=\"moveattsubmit\" value=\"提 &nbsp; 交\">
		</form>";
		specialdiv();
		echo "<script>$('jsmenu').style.display='inline';</script>";
	}
	htmlfooter();
} elseif($action == 'dz_rplastpost') {//修复版块的最后回复

//初始化数据库连接帐号
	define('IN_DISCUZ', TRUE);
	require_once TOOLS_ROOT."./config.inc.php";
	require_once TOOLS_ROOT."./include/db_mysql.class.php";
    	$db = new dbstuff;
	$db->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect, true, $dbcharset);
	$dbuser = $dbpw = $dbname = $pconnect = NULL;
	if($db->version > '4.1') {
			$serverset = "character_set_connection=$dbcharset, character_set_results=$dbcharset, character_set_client=binary";
			$serverset && $db->query("SET $serverset");
	}
	$selectfid = $_POST['fid'];
	if($selectfid) {
			$i = 0;
			foreach($selectfid as $fid) {
				$sql = "select t.tid, t.subject, p.subject AS psubject, p.dateline, p.author from {$tablepre}threads t,  {$tablepre}posts p where t.fid=$fid and p.tid=t.tid and t.displayorder>=0 and p.invisible=0 and p.status=0 order by p.dateline DESC limit 1";
				$query = $db->query($sql);
				$lastarray = array();
				if($lastarray = $db->fetch_array($query)) {
					$lastarray['subject'] = $lastarray['psubject']?$lastarray['psubject']:$lastarray['subject'];
					$lastpoststr = $lastarray['tid']."\t".$lastarray['subject']."\t".$lastarray['dateline']."\t".$lastarray['author'];
					$db->query("update {$tablepre}forums set lastpost='$lastpoststr' where fid=$fid");
				}
			}
			htmlheader();
			show_tools_message("重置成功", 'tools.php?action=dz_rplastpost');
			htmlfooter();

		} else {
			htmlheader();
		echo '<h4>修复版块最后回复 </h4><div class=\"specialdiv\">操作提示：<ul>
		<li>可以指定需要修复的版块，提交后程序会重新查询出版块的最后回复信息并且修复</li>
		</ul></div>';
		echo '<div class="tabbody">
			<script language="javascript">
				function checkall(form, prefix, checkall) {
					var checkall = checkall ? checkall : \'chkall\';
					for(var i = 0; i < form.elements.length; i++) {
						var e = form.elements[i];
						if(e.name != checkall && (!prefix || (prefix && e.name.match(prefix)))) {
							e.checked = form.elements[checkall].checked;
						}
					}
				}
			</script>
	   	 <form action="tools.php?action=dz_rplastpost" method="post">
	
	        	<h4 style="font-size:14px;">论坛版块列表</h4>
				<style>table.re_forum_list { margin-left:0; width:30%;} .re_forum_list input { margin:0; margin-right:10px; border-style:none;}</style>
	        	<table class="re_forum_list">
				<tr><th><input class="checkbox re_forum_input" name="chkall" onclick="checkall(this.form)" type="checkbox" ><strong>全选</strong></th></tr>';
		$sql = "SELECT fid,name FROM {$tablepre}forums WHERE type='forum' or type='sub'";
		$query = mysql_query($sql);
		$forum_array = array();
	        while($forumarray = mysql_fetch_array($query)) {
	            echo '<tr><td><input name="fid[]" value="'.$forumarray[fid].'" type="checkbox" >'.$forumarray['name'].'</td></tr>';
		}
	        echo '</table>
			<div class="opt">
			 <input type="submit" name="submit" value="提交" tabindex="3" />
			</div>
	        
	    </form>
	</div>';
	specialdiv();
	echo "<script>$('jsmenu').style.display='inline';</script>";
	htmlfooter();
	}
} elseif($action == 'dz_rpthreads') {//批量修复主题
//初始化数据库连接帐号
	define('IN_DISCUZ', TRUE);
	require_once TOOLS_ROOT."./config.inc.php";
	require_once TOOLS_ROOT."./include/db_mysql.class.php";
    	$db = new dbstuff;
	$db->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect, true, $dbcharset);
	$dbuser = $dbpw = $dbname = $pconnect = NULL;
  
	if($db->version > '4.1') {
			$serverset = "character_set_connection=$dbcharset, character_set_results=$dbcharset, character_set_client=binary";
			$serverset && $db->query("SET $serverset");
	}
	if($rpthreadssubmit) {
		  if(empty($start)) {
			  $start = 0;
		  }
		if($fids) {
			 if(is_array($fids)) {
				$fidstr = implode(',', $fids);
			 } else {
				$fidstr = $fids;
			 }
			 $sql = "select tid from {$tablepre}threads where fid in (0,$fidstr) and displayorder>='0' limit $start, 500"; 
			 $countsql = "select count(*) from {$tablepre}threads where fid in (0,$fidstr) and displayorder>='0'";
		} else {
			 $sql = "select tid from {$tablepre}threads where displayorder>='0' limit $start, 500";
			 $countsql = "select count(*) from {$tablepre}threads where displayorder>='0'";
		}
		$query = mysql_query($countsql);
		$threadnum = mysql_result($query,0);
		if($threadnum < $start) {
			htmlheader();
			show_tools_message('帖子修复完毕，点这里返回', 'tools.php?action=dz_rpthreads');
			htmlfooter();
			exit;
		}
		$query = mysql_query($sql);
		while($thread = mysql_fetch_array($query)) {
			$tid = $thread['tid'];
			$processed = 1;
			$updatequery = mysql_query("SELECT COUNT(*) FROM {$tablepre}posts WHERE tid='$tid' AND invisible='0'");
			$replies = mysql_result($updatequery, 0) - 1;
			$updatequery = mysql_query("SELECT a.aid FROM {$tablepre}posts p, {$tablepre}attachments a WHERE a.tid='$tid' AND a.pid=p.pid AND p.invisible='0' LIMIT 1");
			$attachment = mysql_num_rows($updatequery) ? 1 : 0;
			$updatequery  = mysql_query("SELECT pid, subject, rate FROM {$tablepre}posts WHERE tid='$tid' AND invisible='0' ORDER BY dateline LIMIT 1");
			$firstpost = mysql_fetch_array($updatequery);
			$firstpost['subject'] = addslashes(cutstr($firstpost['subject'], 79));
			@$firstpost['rate'] = $firstpost['rate'] / abs($firstpost['rate']);
			$updatequery  = mysql_query("SELECT author, dateline FROM {$tablepre}posts WHERE tid='$tid' AND invisible='0' ORDER BY dateline DESC LIMIT 1");
			$lastpost = mysql_fetch_array($updatequery);
			mysql_query("UPDATE {$tablepre}threads SET subject='$firstpost[subject]', replies='$replies', lastpost='$lastpost[dateline]', lastposter='".addslashes($lastpost['author'])."', rate='$firstpost[rate]', attachment='$attachment' WHERE tid='$tid'");
			mysql_query("UPDATE {$tablepre}posts SET first='1', subject='$firstpost[subject]' WHERE pid='$firstpost[pid]'");
			mysql_query("UPDATE {$tablepre}posts SET first='0' WHERE tid='$tid' AND pid<>'$firstpost[pid]'");
		}

		htmlheader();
		show_tools_message('正在处理第 '.$start.' 条到第 '.($start+500).' 条数据', 'tools.php?action=dz_rpthreads&rpthreadssubmit=true&fids='.$fidstr.'&start='.($start+500));
		htmlfooter();
	} else {
	htmlheader();
	echo '<h4>批量修复主题 </h4><div class=\"specialdiv\">操作提示：<ul>
		<li>当浏览某些帖子提示"未定义操作"，可以尝试用批量修复主题的功能进行修复</li>
		<li>可以指定需要修复的版块，提交后程序会批量修复指定版块的主题</li>
		<li>全选或者全不选都会修复所有论坛的主题</li>
		</ul></div>';
	echo '<div class="tabbody">
		<script language="javascript">
				function checkall(form, prefix, checkall) {
					var checkall = checkall ? checkall : \'chkall\';
							
					for(var i = 0; i < form.elements.length; i++) {
						var e = form.elements[i];
						if(e.name != checkall && (!prefix || (prefix && e.name.match(prefix)))) {
							e.checked = form.elements[checkall].checked;
						}
					}
				}
		</script>
		<h4 style="font-size:14px;">论坛版块列表</h4>
		<style>table.re_forum_list { margin-left:0; width:30%;} .re_forum_list input { margin:0; margin-right:10px; border-style:none;}</style>
		<form id="rpthreads" name="rpthreads" method="post"   action="tools.php?action=dz_rpthreads">
			<table class="re_forum_list">
	  	<tr>
		<th><input type="checkbox" name="chkall" onclick="checkall(this.form)" class="checkbox re_forum_input" name="selectall" value="" />全选</th>
		</tr>';
		$sql = "SELECT fid,name FROM {$tablepre}forums WHERE type='forum' or type='sub'";
		$query = mysql_query($sql);
		$forum_array = array();
		while($forumarray = mysql_fetch_array($query)) {
	            echo '<tr><td><input name="fids[]" value="'.$forumarray[fid].'" type="checkbox" >'.$forumarray['name'].'</td></tr>';
		}
	echo '</table>
		<div class="opt">
			<input type="submit" name="rpthreadssubmit" value="提交" />
		</div>
		</form>
		</div>';
	specialdiv();
	echo "<script>$('jsmenu').style.display='inline';</script>";
	htmlfooter();
	}
} elseif($action == 'all_logout') {//退出登陆
	setcookie('toolpassword', '', -86400 * 365);
	errorpage("<h6>您已成功退出,欢迎下次使用.强烈建议您在不使用时删除此文件.</h6>");
} elseif($action == 'all_config') {
	htmlheader();
	echo '<h4>修改配置文件助手</h4>';
	echo "<div class=\"specialdiv\">操作提示：<ul id=\"ping\">
		<li>修改后提交程序会自动修改配置文件中的各项配置，修改前请保证配置文件可写权限。</li>
		</ul></div>";
	if($submit) {
		all_doconfig_modify($whereis);
	}
	ping($whereis);
	all_doconfig_output($whereis);	
	htmlfooter();
} elseif($action == 'phpinfo') {
	echo phpinfo(13);exit;
} elseif($action == 'datago') {
	htmlheader();
	!$tableno && $tableno = 0;
	!$do && $do = 'create';
	!$start && $start = 0;
	$limit = 2000;
	echo '<h4>数据库编码转换</h4>';
	echo "<div class=\"specialdiv\">操作提示：<ul>
		<li><font color=red>转换后请自行修改配置文件中的数据库前缀、页面编码、数据库编码</font></li>
		<li>详细转换教程：<a href='http://www.discuz.net/thread-1460873-1-1.html'><font color=red>使用Tools转换数据库编码教程</font></a></li>
		<li>如果数据库过大，可能需要过多时间</li>
		</ul></div>";
	if($submit) {
		do_datago($mysql,$tableno,$do,$start,$limit);
	} elseif($my_version > '4.1') {
		datago_output();
	} else {
		echo '数据库版本不支持数据库编码';
	}
	htmlfooter();
} elseif($action == 'all_backup') {
	htmlheader();
	echo "<script type='text/javascript'>
			function jumpurl(url){
				location.href = url;
				return false;
			}
		</script>";
	if($begin == '1') {
		echo "<h4>数据库备份</h4><div class=\"specialdiv\">操作提示：<ul>
			<li>数据库备份通过api/dbbak.php来执行，请确保这个文件存在</li>
			<li>备份前请关闭论坛访问，以免备份数据不完整</li>
			<li>请尽量选择服务器空闲时段操作,以避免超时。如程序长久(超过 10 分钟)不反应,请刷新</li></ul></div>";
		$title = '<h5><a href="?action=all_restore">【恢复数据】</a>';
		$title .= '&nbsp;&nbsp;&nbsp;<a href="?action=all_backup&begin=1">【备份数据】</a></h5>';
		echo $title;
		$begin = '<button style="margin:0px;" onclick=jumpurl("tools.php?action=all_backup")>开始备份</button>';
		cexit($begin);
	}
	$notice = "<div class=\"specialdiv\">操作提示：<ul>
			<li>接口文件不存在！</li>
			</ul></div>";
	if(!file_exists('./api/dbbak.php')) {
		cexit($notice);
	}
	if($nexturl) {
		$url = $nexturl;	
	} else {
		$url = getbakurl($whereis);
	}	
	dobak($url,$num);
	htmlfooter();
} else {
	htmlheader();
	echo '<h4>欢迎您使用 Comsenz 系统维护工具箱</h4>
		<tr><td><br>';
	echo '<h5>Comsenz 系统维护工具箱功能简介：</h5><ul>';
	foreach($functionall as  $value) {
		$apps = explode('_', $value['0']);
		if(in_array(substr($whereis, 3), $apps) || $value['0'] == 'all') {	
				echo '<li>'.$value[2].'：'.$value[3].'</li>';
		}
	}
	echo '</ul>';
	htmlfooter();
}
//函数区
function cexit($message) {
	echo $message;
	specialdiv();
	htmlfooter();
}
//检查数据表
function checktable($table, $loops = 0,$doc) {
	global $db, $nohtml, $simple, $counttables, $oktables, $errortables, $rapirtables;
	$query = mysql_query("show create table $table");
	if($createarray = mysql_fetch_array($query)) {
		if(strpos($createarray[1], 'TYPE=HEAP')) {	
		   $counttables --;
			return ;
		}
	}
	$result = mysql_query("CHECK TABLE $table");
	if(!$result) {
		$counttables --;
		return ;
	}
	$message = "\n>>>>>>>>>>>>>Checking Table $table\r\n---------------------------------\r\n";
	@writefile($doc,$message,'a');
	$error = 0;
	while($r = mysql_fetch_row($result)) {
		if($r[2] == 'error') {
			if($r[3] == "The handler for the table doesn't support check/repair") {
				$r[2] = 'status';
				$r[3] = 'This table does not support check/repair/optimize';
				unset($bgcolor);
				$nooptimize = 1;
			} else {
				$error = 1;
				$bgcolor = 'red';
				unset($nooptimize);
			}
			$view = '错误';
			$errortables += 1;
		} else {
			unset($bgcolor);
			unset($nooptimize);
			$view = '正常';
			if($r[3] == 'OK') {
				$oktables += 1;
			} elseif($r[3] == 'The storage engine for the table doesn\'t support check') {
				$oktables += 1;
			}
		}
		$message = "$r[0] | $r[1] | $r[2] | $r[3]\r\n";
		@writefile($doc,$message,'a');
	}
	if($error) {
		$message = ">>>>>>>>正在修复中 / Repairing Table $table\r\n";
		@writefile($doc,$message,'a');
		$result2=mysql_query("REPAIR TABLE $table");
		while($r2 = mysql_fetch_row($result2)) {
			if($r2[3] == 'OK') {
				$bgcolor='blue';
				$rapirtables += 1;
			} else {
				unset($bgcolor);
			}
			$message = "$r2[0] | $r2[1] | $r2[2] | $r2[3]\r\n";
			@writefile($doc,$message,'a');
		}
	}
	if(($result2[3] == 'OK'||!$error)&&!$nooptimize) {
		$message = ">>>>>>>>>>>>>Optimizing Table $table\r\n";
		@writefile($doc,$message,'a');
		$result3 = mysql_query("OPTIMIZE TABLE $table");
		$error = 0;
		while($r3 = mysql_fetch_row($result3)) {
			if($r3[2] == 'error') {
				$error = 1;
				$bgcolor = 'red';
			} else {
				unset($bgcolor);
			}
			$message = "$r3[0] | $r3[1] | $r3[2] | $r3[3]\r\n\r\n";
			@writefile($doc,$message,'a');
		}
	}
	if($error && $loops) {
		checktable($table,($loops-1),$doc);
	}
}
//检查文件
function checkcachefiles($currentdir){
	global $authkey;
	$dir = opendir($currentdir);
	$exts = '/\.php$/i';
	$showlist = $modifylist = $addlist = array();
	while($entry = readdir($dir)) {
		$file = $currentdir.$entry;
		if($entry != '.' && $entry != '..' && preg_match($exts, $entry)) {
			@$fp = fopen($file, 'rb');
			@$cachedata = fread($fp, filesize($file));
			@fclose($fp);

			if(preg_match("/^<\?php\n\/\/Discuz! cache file, DO NOT modify me!\n\/\/Created: [\w\s,:]+\n\/\/Identify: (\w{32})\n\n(.+?)\?>$/s", $cachedata, $match)) {
				$showlist[$file] = $md5 = $match[1];
				$cachedata = $match[2];

				if(md5($entry.$cachedata.$authkey) != $md5) {
					$modifylist[$file] = $md5;
				}
			} else {
				$showlist[$file] = $addlist[$file] = '';
			}
		}

	}

	return array($showlist, $modifylist, $addlist);
}

function continue_redirect($action = 'dz_mysqlclear', $extra = ''){
	global $scriptname, $step, $actionnow, $start, $end, $stay, $convertedrows, $allconvertedrows, $totalrows, $maxid;
	if($action == 'doctor') {
		$url = "?action=$action{$extra}";
	} else {
		$url = "?action=$action&step=".$step."&start=".($end + 1)."&stay=$stay&totalrows=$totalrows&convertedrows=$convertedrows&maxid=$maxid&allconvertedrows=$allconvertedrows".$extra;
	}
	$timeout = $GLOBALS['debug'] ? 5000 : 2000;
	echo "<script>\r\n";
	echo "<!--\r\n";
	echo "function redirect() {\r\n";
	echo "	window.location.replace('".$url."');\r\n";
	echo "}\r\n";
	echo "setTimeout('redirect();', $timeout);\r\n";
	echo "-->\r\n";
	echo "</script>\r\n";
	if($action== 'doctor') {
		echo '<h4>论坛医生</h4><br><table>
		<tr><th>正在进行检查,请稍候</th></tr><tr><td>';
		echo "<br><a href=\"".$url."\">如果您的浏览器长时间没有自动跳转，请点击这里！</a><br><br>";
		echo '</td></tr></table>';
	} elseif($action == 'uch_dz_replace') {
		echo '<h4>数据处理中</h4><table>
		<tr><th>正在进行'.$actionnow.'</th></tr><tr><td>';
		echo "正在处理 $start ---- $end 条数据[<a href='$url&stop=1' style='color:red'>停止运行</a>]";

		echo "<br><br><a href=\"".$url."\">如果您的浏览器长时间没有自动跳转，请点击这里！</a>";
		echo '</td></tr></table>';
	} else {
		echo '<h4>数据处理中</h4><table>
		<tr><th>正在进行'.$actionnow.'</th></tr><tr><td>';
		echo "正在处理 $start ---- $end 条数据[<a href='?action=$action' style='color:red'>停止运行</a>]";
		echo "<br><br><a href=\"".$url."\">如果您的浏览器长时间没有自动跳转，请点击这里！</a>";
		echo '</td></tr></table>';
	}
}

function dirsize($dir){
	$dh = @opendir($dir);
	$size = 0;
	while($file = @readdir($dh)) {
		if($file != '.' && $file != '..') {
			$path = $dir.'/'.$file;
			if(@is_dir($path)) {
				$size += dirsize($path);
			} else {
				$size += @filesize($path);
			}
		}
	}
	@closedir($dh);
	return $size;
}

function get_real_size($size){
	$kb = 1024;
	$mb = 1024 * $kb;
	$gb = 1024 * $mb;
	$tb = 1024 * $gb;

	if($size < $kb) {
		return $size.' Byte';
	} elseif($size < $mb) {
		return round($size/$kb,2).' KB';
	} elseif($size < $gb) {
		return round($size/$mb,2).' MB';
	} elseif($size < $tb) {
		return round($size/$gb,2).' GB';
	} else {
		return round($size/$tb,2).' TB';
	}
}

function htmlheader() {
	global $uch_version,$alertmsg, $whereis, $functionall,$dz_version,$ss_version,$toolpassword,$tool_password,$toolbar,$plustitle;
	switch($whereis) {
		case 'is_dz':
			$plustitle = 'Discuz '.substr($dz_version,0,1).'.'.substr($dz_version,1,1);
			break;
		case 'is_uch':
			$plustitle = 'UCenter Home '.substr($uch_version,0,1).'.'.substr($uch_version,1);
			break;
		case 'is_ss':
			$plustitle = 'SupeSite '.substr($ss_version,0,1).'.'.substr($ss_version,1,1);;
			break;
		case 'is_uc':
			$plustitle = 'UCenter';
			break;
		default:
			$plustitle = '';
			break;
		}
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html>
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>Comsenz 系统维护工具箱 '.VERSION.'-New</title>
		<style type="text/css"><!--
		body {font-family: Tahoma,Arial, Helvetica, sans-serif, "宋体";font-size: 12px;color:#000;line-height: 120%;padding:0;margin:0;background:#DDE0FF;overflow-x:hidden;word-break:break-all;white-space:normal;scrollbar-3d-light-color:#606BFF;scrollbar-highlight-color:#E3EFF9;scrollbar-face-color:#CEE3F4;scrollbar-arrow-color:#509AD8;scrollbar-shadow-color:#F0F1FF;scrollbar-base-color:#CEE3F4;}
        a:hover {color:#60F;}
		ul {padding:2px 0 10px 0;margin:0;}
		textarea,table,td,th,select{border:1px solid #868CFF;border-collapse:collapse;}
		table li {margin-left:10px;}
		input{margin:10px 0 0px 30px;border-width:1px;border-style:solid;border-color:#FFF #64A7DD #64A7DD #FFF;padding:2px 8px;background:#E3EFF9;}
			input.radio,input.checkbox,input.textinput,input.specialsubmit {margin:0;padding:0;border:0;padding:0;background:none;}
			input.textinput,input.specialsubmit {border:1px solid #AFD2ED;background:#FFF;}
			input.textinput {padding:4px 0;} 			input.specialsubmit {border-color:#FFF #64A7DD #64A7DD #FFF;background:#E3EFF9;padding:0 5px;}
		option {background:#FFF;}
		select {background:#F0F1FF;}
		#header {border-top:4px solid #86B9D6;height:60px;width:100%;padding:0;margin:0;}
		    h2 {font-size:20px;font-weight:normal;position:absolute;top:20px;left:20px;padding:10px;margin:0;}
		    h3 {font-size:14px;position:absolute;top:28px;right:20px;padding:10px;margin:0;}
		#content {height:510px;background:#F0F1FF;overflow-x:hidden;z-index:1000;}
		    #nav {top:60px;left:0;height:510px;width:180px;border-right:1px solid #DDE0FF;position:absolute;z-index:2000;}
		        #nav ul {padding:0 10px;padding-top:30px;}
		        #nav li {list-style:none;}
		        #nav li a {font-size:14px;line-height:180%;font-weight:400;color:#000;}
		        #nav li a:hover {color:#60F;}
		    #textcontent {padding-left:200px;height:510px;width:80%;*width:100%;line-height:160%;overflow-y:auto;overflow-x:hidden;}
			    h4,h5,h6 {padding:4px;font-size:16px;font-weight:bold;margin-top:20px;margin-bottom:5px;color:#006;}
				h5,h6 {font-size:14px;color:#000;}
				h6 {color:#F00;padding-top:5px;margin-top:0;}
				.specialdiv {width:70%;border:1px dashed #C8CCFF;padding:0 5px;margin-top:20px;background:#F9F9FF;}
				.specialdiv2 {height:240px;width:60%;border:1px dashed #C8CCFF;padding:15px;margin-top:20px;background:#F9F9FF;overflow-y:scroll;}
				#textcontent ul {margin-left:30px;}
				textarea {width:78%;height:300px;text-align:left;border-color:#AFD2ED;}
				select {border-color:#AFD2ED;}
				table {width:74%;font-size:12px;margin-left:18px;margin-top:10px;}
				    table.specialtable,table.specialtable td {border:0;}
					td,th {padding:5px;text-align:left;}
				    caption {font-weight:bold;padding:8px 0;color:#3544FF;text-align:left;}
				    th {background:#D9DCFF;font-weight:600;}
					td.specialtd {text-align:left;}
				.specialtext {background:#FCFBFF;margin-top:20px;padding:5px 40px;width:64.5%;margin-bottom:10px;color:#006;}
		#footer p {padding:0 5px;text-align:center;}
		#jsmenu {margin-left:-200px;margin-top:-110px;border:5px solid #868CFF;width:400px;height:140px;padding:4px 10px 0 10px; text-align:left;background:#FFF; left:50%; top:50%; position:absolute; font:12px;zIndex:10001;}
		.button {margin-top:20px;}
		.infobox {background:#FFF;border-bottom:4px solid #868CFF;border-top:4px solid #868CFF;margin-bottom:10px;padding:30px;text-align:center;width:90%;}
		pre {*margin-top:10px;}
		.current { font-weight: bold; color: #090 !important; border-bottom-color: #F90 !important; }
		-->
		</style>
		</head>
		<script>function $(id) {return document.getElementById(id);}
		function menuclose(){
			$(\'jsmenu\').style.display = \'none\';
		}
		</script>
		<body>
	<div id = "jsmenu" style="display:none">
	<h6>提示：</h6>
	提示：在进行此操作前建议备份数据库，以免处理过程中出现错误造成数据丢失！！<br/>
	<input class=button onclick=menuclose() type=button value=我知道了></input>
	</div>
        <div id="header">
		<h2>< Comsenz Tools '.VERSION.' > Now In: '.$plustitle.'</h2>
		<h3>[ <a href="?" target="_self">首页</a> ]&nbsp;
		[ <a href="?action=all_setlock" target="_self">锁定</a> ]&nbsp;';
	if($toolpassword == md5($tool_password)) {
		foreach($toolbar as $value) {
			echo '[ <a href="?action='.$value[0].'" target="_self">'.$value[1].'</a> ]&nbsp';
		}
	}
	echo '</h3></div>
		<div id="nav">';
		echo '<ul>';//导航菜单中根据不同的目录显示不同
		if($toolpassword == md5($tool_password)) {
			foreach($functionall as  $value) {
				$apps = explode('_', $value['0']);
				if(in_array(substr($whereis, 3), $apps) || $value['0'] == 'all') {	
					if($whereis == 'is_ss' && $value[1] == 'all_setadmin' && $ss_version<70 ) {
						continue;
					}
					echo '<li>[ <a href="?action='.$value[1].'" target="_self">'.$value[2].'</a> ]</li>';
				}
			}
		} else {
			echo '<li>[ <a href="tools.php" target="_self">使用前请登录</a> ]</li>';	
		}
		echo '</ul>';
		echo '</div>
		<div id="content">
		<div id="textcontent">';
}
//页面底部
function htmlfooter(){
	echo '</div></div>
		<div id="footer"><p>Comsenz 系统维护工具箱 &nbsp;Release:'.Release.'&nbsp;
		 &copy; <a href="http://www.comsenz.com" style="color: #000000; text-decoration: none">Comsenz Inc.</a> 2001-2009 </font></td></tr><tr style="font-size: 0px; line-height: 0px; spacing: 0px; padding: 0px; background-color: #698CC3">
		</p></div>
		</body>
		</html>';
	exit;
}
//错误信息
function errorpage($message,$title = '',$isheader = 1,$isfooter = 1){
	if($isheader) {
		htmlheader();
	}
	!$isheader && $title = '';
	if($message == 'login') {
		$message ='<h4>工具箱登录</h4>
				<form action="?" method="post">
					<table class="specialtable"><tr>
					<td width="20%"><input class="textinput" type="password" name="toolpassword"></input></td>
					<td><input class="specialsubmit" type="submit" value="登 录"></input></td></tr></table>
					<input type="hidden" name="action" value="login">
				</form>';
	} else {
		$message = "<h4>$title</h4><br><br><table><tr><th>提示信息</th></tr><tr><td>$message</td></tr></table>";
	}
	echo $message;
	if($isfooter) {
		htmlfooter();
	}
}
//跳转
function redirect($url) {
	echo "<script>";
	echo "function redirect() {window.location.replace('$url');}\n";
	echo "setTimeout('redirect();', 2000);\n";
	echo "</script>";
	echo "<br><br><a href=\"$url\">如果您的浏览器没有自动跳转，请点击这里</a>";
	cexit("");
}
/**
 * 检查目录里下的文件权限函数
 * @param unknown_type $directory
 */

//检查sql语句
function splitsql($sql){
	$sql = str_replace("\r", "\n", $sql);
	$ret = array();
	$num = 0;
	$queriesarray = explode(";\n", trim($sql));
	unset($sql);
	foreach($queriesarray as $query) {
		$queries = explode("\n", trim($query));
		foreach($queries as $query) {
			$ret[$num] .= $query[0] == "#" ? NULL : $query;
		}
		$num++;
	}
	return($ret);
}

function syntablestruct($sql, $version, $dbcharset) {

	if(strpos(trim(substr($sql, 0, 18)), 'CREATE TABLE') === FALSE) {
		return $sql;
	}
	if(substr(trim($sql), 0, 9) == 'SET NAMES' && !$version) {
        return '';
    } 
	$sqlversion = strpos($sql, 'ENGINE=') === FALSE ? FALSE : TRUE;

	if($sqlversion === $version) {

		return $sqlversion && $dbcharset ? preg_replace(array('/ character set \w+/i', '/ collate \w+/i', "/DEFAULT CHARSET=\w+/is"), array('', '', "DEFAULT CHARSET=$dbcharset"), $sql) : $sql;
	}

	if($version) {
		return preg_replace(array('/TYPE=HEAP/i', '/TYPE=(\w+)/is'), array("ENGINE=MEMORY DEFAULT CHARSET=$dbcharset", "ENGINE=\\1 DEFAULT CHARSET=$dbcharset"), $sql);

	} else {
		return preg_replace(array('/character set \w+/i', '/collate \w+/i', '/ENGINE=MEMORY/i', '/\s*DEFAULT CHARSET=\w+/is', '/\s*COLLATE=\w+/is', '/ENGINE=(\w+)(.*)/is'), array('', '', 'ENGINE=HEAP', '', '', 'TYPE=\\1\\2'), $sql);
	}
}
function stay_redirect() {
	global $action, $actionnow, $step, $stay, $convertedrows, $allconvertedrows;
	$nextstep = $step + 1;
	echo '<h4>数据库冗余数据清理</h4><table>
			<tr><th>正在进行'.$actionnow.'</th></tr><tr>
			<td>';
	if($stay) {
		$actions = isset($action[$nextstep]) ? $action[$nextstep] : '结束';
		echo "$actionnow 操作完毕.共处理<font color=red>{$convertedrows}</font>条数据.".($stay == 1 ? "&nbsp;&nbsp;&nbsp;&nbsp;" : '').'<br><br>';
		echo "<a href='?action=dz_mysqlclear&step=".$nextstep."&stay=1'>( $actions )，请点击这里！</a><br>";
	} else {
		if(isset($action[$nextstep])) {
			echo '即将进入：'.$action[$nextstep].'......';
		}
		$allconvertedrows = $allconvertedrows + $convertedrows;
		$timeout = $GLOBALS['debug'] ? 5000 : 2000;
		echo "<script>\r\n";
		echo "<!--\r\n";
		echo "function redirect() {\r\n";
		echo "	window.location.replace('?action=dz_mysqlclear&step=".$nextstep."&allconvertedrows=".$allconvertedrows."');\r\n";
		echo "}\r\n";
		echo "setTimeout('redirect();', $timeout);\r\n";
		echo "-->\r\n";
		echo "</script>\r\n";
		echo "[<a href='?action=dz_mysqlclear' style='color:red'>停止运行</a>]<br><br><a href=\"".$scriptname."?step=".$nextstep."\">如果您的浏览器长时间没有自动跳转，请点击这里！</a>";
	}
	echo '</td></tr></table>';
}
//检查数据库表字段
function loadtable($table, $force = 0) {	
	global $carray;
	$discuz_tablepre = $carray['tablepre'];
	static $tables = array();

	if(!isset($tables[$table])) {
		if(mysql_get_server_info() > '4.1') {
			$query = @mysql_query("SHOW FULL COLUMNS FROM {$discuz_tablepre}$table");
		} else {
			$query = @mysql_query("SHOW COLUMNS FROM {$discuz_tablepre}$table");
		}
		while($field = @mysql_fetch_assoc($query)) {
			$tables[$table][$field['Field']] = $field;
		}
	}
	return $tables[$table];
}

//获得数据表的最大和最小 id 值
function validid($id, $table) {
	global $start, $maxid, $mysql, $tablepre;
	$sql = mysql_query("SELECT MIN($id) AS minid, MAX($id) AS maxid FROM {$tablepre}$table");
	$result = mysql_fetch_array($sql);
	$start = $result['minid'] ? $result['minid'] - 1 : 0;
	$maxid = $result['maxid'];
}
//提示
function specialdiv() {
	echo '<div class="specialdiv">
		<h6>注意：</h6>
		<ul>
		<li>对数据库操作可能会出现意外现象的发生及破坏，所以请先备份好数据库再进行上述操作！另外请您选择服务器压力比较小的时候进行一些优化操作。</li>
		<li>当您使用完毕Comsenz 系统维护工具箱后，请点击锁定工具箱以确保系统的安全！下次使用前只需要在/forumdata目录下删除tool.lock文件即可开始使用。</li></ul></div>';
}
//判断目录
function getplace() {
	global $lockfile, $cfgfile, $docdir;
	$whereis = false;
	if(is_writeable('./config.inc.php') && is_writeable('./forumdata')) {//判断Discuz!目录
			$whereis = 'is_dz';
			$lockfile = './forumdata/tools.lock';
			$cfgfile = './config.inc.php';
			$docdir = './forumdata';
	}
	if(is_writeable('./data/config.inc.php') && is_dir('./control')) {//判断UCenter目录
			$whereis = 'is_uc';
			$lockfile = './data/tools.lock';
			$cfgfile = './data/config.inc.php';
			$docdir = './data';
	}
	if(is_writeable('./config.php') && is_dir('source')) {//判断UCenter Home目录
			$whereis = 'is_uch';
			$lockfile = './data/tools.lock';
			$cfgfile = './config.php';
			$docdir = './data';
	}
	if(is_writeable('./config.php') && file_exists('./batch.common.php')) {//判断SupeSite目录
			$whereis = 'is_ss';
			$lockfile = './data/tools.lock';
			$cfgfile = './config.php';
			$docdir = './data';
	}
	return $whereis;
}
//获得数据库配置信息
function getdbcfg(){
	global $uc_dbcharset,$uc_dbhost,$uc_dbuser,$uc_dbpw,$uc_dbname,$uc_tablepre,$dbhost, $dbuser, $dbpw, $dbname, $dbcfg, $whereis, $cfgfile, $tablepre, $dbcharset,$dz_version,$ss_version,$uch_version;
	if(@!include($cfgfile)) {
			htmlheader();
			cexit("<h4>请先上传config文件以保证您的数据库能正常链接！</h4>");
	}
	if(UC_DBHOST) {
		$uc_dbhost = UC_DBHOST;
		$uc_dbuser = UC_DBUSER;
		$uc_dbpw = UC_DBPW;
		$uc_dbname = UC_DBNAME;	
		$uc_tablepre =  UC_DBTABLEPRE;
		$uc_dbcharset = UC_DBCHARSET;
	}
	switch($whereis) {
		case 'is_dz':
			$dbhost = $dbhost;
			$dbuser = $dbuser;
			$dbpw = $dbpw;
			$dbname = $dbname;	
			$tablepre =  $tablepre;
			$dbcharset = !$dbcharset ? (strtolower($charset) == 'utf-8' ? 'utf8' : $charset): $dbcharset;
			define('IN_DISCUZ',true);
			@require_once "./discuz_version.php";
			$dz_version = DISCUZ_VERSION;
			if($dz_version >= '7.1') {
				$dz_version = intval(str_replace('.','',$dz_version)).'0';
			} else {
				$dz_version = intval(str_replace('.','',$dz_version));
				}
			break;
		case 'is_uc':
			$dbhost = UC_DBHOST;
			$dbuser = UC_DBUSER;
			$dbpw = UC_DBPW;
			$dbname = UC_DBNAME;	
			$tablepre =  UC_DBTABLEPRE;
			$dbcharset = !UC_DBCHARSET ? (strtolower(UC_CHARSET) == 'utf-8' ? 'utf8' : UC_CHARSET) : UC_DBCHARSET;
			break;
		case 'is_uch':
			$dbhost = $_SC["dbhost"];
			$dbuser = $_SC["dbuser"];
			$dbpw = $_SC["dbpw"];
			$dbname = $_SC["dbname"];	
			$tablepre =  $_SC["tablepre"];
			if(file_exists("./ver.php")) {
				require './ver.php';
				$uch_version = X_VER;
			} else {
				$common = 'common.php';
				$version = fopen($common,'r');
				$version = fread($version,filesize($common));
				$len = strpos($version,'define(\'D_BUG\')');
				$version = substr($version,0,$len);
				$cache = fopen('./data/version.php','w');
				fwrite($cache,$version);
				fclose($cache);
				require_once './data/version.php';
				$uch_version = intval(str_replace('.','',X_VER));
				unlink('./data/version.php');
			}		
			$uch_version = intval(str_replace('.','',$uch_version));
			$dbcharset = !$_SC['dbcharset'] ? (strtolower($_SC["charset"]) == 'utf-8' ? 'utf8' : $_SC["charset"]) : $_SC['dbcharset'] ;
			break;
		case 'is_ss':
			$dbhost = $dbhost ? $dbhos : $_SC['dbhost'];
			$dbuser = $dbuser ? $dbuser : $_SC['dbuser'];
			$dbpw = $dbpw ? $dbpw : $_SC['dbpw'];
			$dbname = $dbname ? $dbname : $_SC['dbname'];	
			$tablepre =  $tablepre ? $tablepre : $_SC['tablepre'];
			$dbcharset = !$dbcharset ? (strtolower($charset) == 'utf-8' ? 'utf8' : $charset) : $dbcharset;
			if(!$dbcharset) {
				$dbcharset = !$_SC['dbcharset'] ? (strtolower($_SC['charset']) == 'utf-8' ? 'utf8' : $_SC['charset']) : $_SC['dbcharset'];			
			}
			if($_SC['dbhost'] || $_SC['dbuser']) {
				$common = 'common.php';
				$version = fopen($common,'r');
				$version = fread($version,filesize($common));
				$len = strpos($version,'define(\'S_RELEASE\'');
				$version = substr($version,0,$len);
				$cache = fopen('./data/version.php','w');
				fwrite($cache,$version);
				fclose($cache);
				require_once './data/version.php';
				$ss_version = intval(str_replace('.','',S_VER));
				unlink('./data/version.php');
			}
			break;
		default:
			$dbhost = $dbuser = $dbpw = $dbname = $tablepre = $dbcharset = '';
			break;
	}
}

function taddslashes($string, $force = 0) {
	!defined('MAGIC_QUOTES_GPC') && define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());
	if(!MAGIC_QUOTES_GPC || $force) {
		if(is_array($string)) {
			foreach($string as $key => $val) {
				$string[$key] = taddslashes($val, $force);
			}
		} else {
			$string = addslashes($string);
		}
	}
	return $string;
}

function pregcharset($charset,$color = 0) {
		if(strpos('..'.strtolower($charset), 'gbk')) {
			if($color) {
				return '<font color="#0000CC">gbk</font>';
			} else {
				return 'gbk';
			}
		} elseif(strpos('..'.strtolower($charset), 'latin1')) {
			if($color) {
				return '<font color="#993399">latin1</font>';
			} else {
				return 'latin1';
			}
		} elseif(strpos('..'.strtolower($charset), 'utf8')) {
			if($color) {
				return '<font color="#993300">utf8</font>';
			} else {
				return 'utf8';
			}
		} elseif(strpos('..'.strtolower($charset), 'big5')) {
			if($color) {
				return '<font color="#006699">big5</font>';
			} else {
				return 'big5';	
			}
		} else {
	       return $charset;
		}
}

function show_tools_message($message, $url = 'tools.php',$time = '2000') {
	echo "<script>";
	echo "function redirect() {window.location.replace('$url');}\n";
	echo "setTimeout('redirect();', $time);\n";
	echo "</script>";
	echo "<h4>$title</h4><br><br><table><tr><th>提示信息</th></tr><tr><td>$message<br><a href=\"$url\">如果您的浏览器没有自动跳转，请点击这里</a></td></tr></table>";
	exit;
}

function fileext($filename) {
	return trim(substr(strrchr($filename, '.'), 1, 10));
}
function cutstr($string, $length, $dot = ' ...') {
	global $charset;
	if(strlen($string) <= $length) {
		return $string;
	}
	$string = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array('&', '"', '<', '>'), $string);
	$strcut = '';
	if(strtolower($charset) == 'utf-8') {
		$n = $tn = $noc = 0;
		while($n < strlen($string)) {

			$t = ord($string[$n]);
			if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
				$tn = 1; $n++; $noc++;
			} elseif(194 <= $t && $t <= 223) {
				$tn = 2; $n += 2; $noc += 2;
			} elseif(224 <= $t && $t < 239) {
				$tn = 3; $n += 3; $noc += 2;
			} elseif(240 <= $t && $t <= 247) {
				$tn = 4; $n += 4; $noc += 2;
			} elseif(248 <= $t && $t <= 251) {
				$tn = 5; $n += 5; $noc += 2;
			} elseif($t == 252 || $t == 253) {
				$tn = 6; $n += 6; $noc += 2;
			} else {
				$n++;
			}
			if($noc >= $length) {
				break;
			}
		}
		if($noc > $length) {
			$n -= $tn;
		}
		$strcut = substr($string, 0, $n);
	} else {
		for($i = 0; $i < $length; $i++) {
			$strcut .= ord($string[$i]) > 127 ? $string[$i].$string[++$i] : $string[$i];
		}
	}
	$strcut = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $strcut);
	return $strcut.$dot;
}

function checkfiles($currentdir, $ext = '', $sub = 1, $skip = '') {
	global $md5data, $dz_files;
	$dir = @opendir($currentdir);
	$exts = '/('.$ext.')$/i';
	$skips = explode(',', $skip);

	while($entry = @readdir($dir)) {
		$file = $currentdir.$entry;
		if($entry != '.' && $entry != '..' && (preg_match($exts, $entry) || $sub && is_dir($file)) && !in_array($entry, $skips)) {
			if($sub && is_dir($file)) {
				checkfiles($file.'/', $ext, $sub, $skip);
			} else {
				$md5data[$file] = md5_file($file);
			}
		}
	}
}

function loadtable_ucenter($table, $force = 0) {	
	global $carray;
	$discuz_tablepre = $carray['UC_DBTABLEPRE'];
	static $tables = array();

	if(!isset($tables[$table])) {
		if(mysql_get_server_info() > '4.1') {
			$query = @mysql_query("SHOW FULL COLUMNS FROM {$discuz_tablepre}$table");
		} else {
			$query = @mysql_query("SHOW COLUMNS FROM {$discuz_tablepre}$table");
		}
		while($field = @mysql_fetch_assoc($query)) {
			$tables[$table][$field['Field']] = $field;
		}
	}
	return $tables[$table];
}

function dz_updatecache(){
	global $dz_version;
	if($dz_version < 710) {
		$cachedir = array('cache','templates');
	} else {
		$cachedir = array('cache','templates','feedcaches');
		}
	$clearmsg = '';
	foreach($cachedir as $dir) {
		if($dh = dir('./forumdata/'.$dir)) {
			while (($file = $dh->read()) !== false) {
				if($file != "." && $file != ".." && $file != "index.htm" && !is_dir($file)) {
					unlink('./forumdata/'.$dir.'/'.$file);
				}
			}
		} else {
			$clearmsg .= './forumdata/'.$dir.'清除失败.<br>';
		}
	}
	return $clearmsg;
}

function uch_updatecache(){
	$cachedir = array('data','data/tpl_cache');
	$clearmsg = '';
	foreach($cachedir as $dir) {
		if($dh = dir('./'.$dir)) {
			while (($file = $dh->read()) !== false) {
				if(!is_dir($file) && $file != "." && $file != ".." && $file != "index.htm" && $file != "install.lock" && $file != "sendmail.lock" ) {
					unlink('./'.$dir.'/'.$file);
				}
			}
		} else {
			$clearmsg .= './'.$dir.'清除失败.<br>';
		}
	}
	return $clearmsg;
}

function ss_updatecache(){
	$cachedir = array('cache/model','cache/tpl');
	$clearmsg = '';
	foreach($cachedir as $dir) {
		if($dh = dir('./'.$dir)) {
			while (($file = $dh->read()) !== false) {
				if(!is_dir($file) && $file != "." && $file != ".." && $file != "index.htm" && $file != "install.lock" && $file != "sendmail.lock" ) {
					unlink('./'.$dir.'/'.$file);
				}
			}
		} else {
			$clearmsg .= './'.$dir.'清除失败.<br>';
		}
	}
	return $clearmsg;
}

function runquery($queries){//执行sql语句
	global $tablepre,$whereis;
	$sqlquery = splitsql(str_replace(array(' cdb_', ' {tablepre}', ' `cdb_'), array(' '.$tablepre, ' '.$tablepre, ' `'.$tablepre), $queries));
	$affected_rows = 0;
	foreach($sqlquery as $sql) {
	$sql = syntablestruct(trim($sql), $my_version > '4.1', $dbcharset);
	if(trim($sql) != '') {
		mysql_query(stripslashes($sql));
		if($sqlerror = mysql_error()) {
			break;
			} else {
			$affected_rows += intval(mysql_affected_rows());
			}
		}
	}
	if(strpos($queries,'seccodestatus') && $whereis == 'is_dz') {
		dz_updatecache();	
	}
	if(strpos($queries,'bbclosed') && $whereis == 'is_dz') {
		dz_updatecache();	
	}
	if(strpos($queries,'template') && $whereis == 'is_uch') {
		uch_updatecache();	
	}
	if(strpos($queries,'seccode_login') && $whereis == 'is_uch') {
		uch_updatecache();	
	}
	if(strpos($queries,'close') && $whereis == 'is_uch') {
		uch_updatecache();	
	}
	errorpage($sqlerror? $sqlerror : "数据库升级成功,影响行数: &nbsp;$affected_rows",'数据库升级');

	if(strpos($queries,'settings') && $whereis == 'is_dz') {
		require_once './include/cache.func.php';
		updatecache('settings');		
	}
}

function runquery_html(){ //输出快速设置的所有选项
	global $whereis,$tablepre;
	echo "<h4>快速设置(SQL)</h4>
		<form method=\"post\" action=\"tools.php?action=all_runquery\">
		<h5>请下拉选择程序内置的快速设置</h4>
		<font color=red>提示：</font>也可以自己书写SQL执行，不过请确保您知道该SQL的用途，以免造成不必要的损失.<br/><br/>";
	if($whereis == 'is_dz') {
		echo "<select name=\"queryselect\" onChange=\"queries.value = this.value\">
			<option value = ''>可选择TOOLS内置升级语句</option>
			<option value = \"REPLACE INTO ".$tablepre."settings (variable, value) VALUES ('bbclosed', '0')\">开启论坛访问</option>
			<option value = \"REPLACE INTO ".$tablepre."settings (variable, value) VALUES ('seccodestatus', '0')\">关闭所有验证码功能</option>
			<option value = \"UPDATE ".$tablepre."usergroups SET allowdirectpost = '1'\">论坛所有用户发帖受过滤词汇限制</option>
			<option value = \"REPLACE INTO ".$tablepre."settings (variable, value) VALUES ('supe_status', '0')\">关闭论坛中的supersite功能</option>
			<option value = \"TRUNCATE TABLE ".$tablepre."failedlogins\">清空登陆错误记录</option>
			<option value = \"UPDATE ".$tablepre."members SET pmsound=2 WHERE pmsound=1\">打开所用用户的短消息提示音</option>
			<option value = \"UPDATE ".$tablepre."forums f, cdb_posts p SET p.htmlon=p.htmlon|1 WHERE p.fid=f.fid AND f.allowhtml='1';\">开启所有可以使用HTML板块中的帖子的HTML代码</option>
			<option value = \"UPDATE ".$tablepre."attachments SET `remote`=1;\">将论坛所有附件设为远程附件，谨慎使用！</option>
			</select>";
		}
	if($whereis == 'is_uc') {
		echo "<select name=\"queryselect\" onChange=\"queries.value = this.value\">
			<option value = ''>可选择TOOLS内置升级语句</option>
			<option value = \"TRUNCATE TABLE ".$tablepre."notelist;\">清空通知列表</option>
			</select>";
		}
	if($whereis == 'is_uch') {
		echo "<select name=\"queryselect\" onChange=\"queries.value = this.value\">
			<option value = ''>可选择TOOLS内置升级语句</option>
			<option value = \"REPLACE INTO ".$tablepre."config (datavalue, var) VALUES ('template','default')\">更换为默认模板，解决后台登陆错误</option>
			<option value = \"REPLACE INTO ".$tablepre."config (datavalue, var) VALUES ('seccode_login','0')\">关闭登陆的验证码功能</option>
			<option value = \"REPLACE INTO ".$tablepre."config (datavalue, var) VALUES ('close','0')\">快速开放站点</option>
			<option value = \"UPDATE ".$tablepre."pic SET `remote`=1\">将所有附件设为远程附件，谨慎使用！</option>
			</select>";
		}
		echo "<br />
			<br /><textarea name=\"queries\">$queries</textarea><br />
			<input type=\"submit\" name=\"sqlsubmit\" value=\"提 &nbsp; 交\">
			</form>";
}

function topattern_array($source_array) { //将数组正则化
	$source_array = preg_replace("/\{(\d+)\}/",".{0,\\1}",$source_array);
	foreach($source_array as $key => $value) {
		$source_array[$key] = '/'.$value.'/i';
	}
	return $source_array;
}

function all_setadmin_set($tablepre,$whereis){ //重设管理员根据程序生成各种变量
	global $ss_version,$dz_version,$sql_findadmin,$sql_select,$sql_update,$sql_rspw,$secq,$rspw,$username,$uid;
	if($whereis == 'is_dz') {
		$sql_findadmin = "SELECT * FROM {$tablepre}members WHERE adminid=1";
		$sql_select = "SELECT uid FROM {$tablepre}members WHERE $_POST[loginfield] = '$_POST[where]'";		$username = 'username';
		$uid = 'uid';
		
		if(UC_CONNECT == 'mysql' || $dz_version < 610) {//判断连接ucenter的方式，如果是mysql方式，可以修改密码，否则提示去uc后台修改密码
			$rspw = 1;
			
		} else {
			$rspw = 0;
		}
		if($dz_version<710) {//是否存在安全问答 7.0以后安全问答放在用户中心中
			$secq = 1;
		} elseif($rspw) {
			$secq = 1;
		} else {
			$secq = 0;
		}
	} elseif($whereis == 'is_uc') {
		$secq = 0;
		$rspw = 1;
	} elseif($whereis == 'is_uch') {
		$sql_findadmin = "SELECT * FROM {$tablepre}space WHERE groupid = 1";
		$sql_select = "SELECT uid FROM {$tablepre}space WHERE $_POST[loginfield] = '$_POST[where]'";
		$sql_update = "UPDATE {$tablepre}space SET groupid='1' WHERE $_POST[loginfield] = '$_POST[where]'";
		$username = 'username';
		$uid = 'uid';
		$secq = 0;
		if(UC_CONNECT == 'mysql') {
			$rspw = 1;
		} else {
			$rspw = 0;
		}
	} elseif($whereis == 'is_ss' && $ss_version >= 70) {
		$sql_findadmin = "SELECT * FROM {$tablepre}members WHERE groupid = 1";
		$sql_select = "SELECT uid FROM {$tablepre}members WHERE $_POST[loginfield] = '$_POST[where]'";
		$sql_update = "UPDATE {$tablepre}members SET groupid='1' WHERE $loginfield = '$where'";
		$username = 'username';
		$uid = 'uid';
		$secq = 0;
		if(UC_CONNECT == 'mysql') {
			$rspw = 1;
		} else {
			$rspw = 0;
		}

	}
}

function datago_output($whereis){
	global $dbhost, $dbuser, $dbpw, $dbname, $dbcfg;
	$charsets=array('gbk','latin1','utf8');
	$options="<option value=''>";
	foreach($charsets as $value){
		$options.="<option value=\"$value\">$value";
	}
	echo '<h5>数据库编码转换</h5>';
	echo '<form method=get action=tools.php?action=datago><table>
		<tbody>
		<input name=action type=hidden value=datago>
		<tr><th width=20%>源数据库</th><td><input class=textinput name=fromdbname value='.$dbname.'></input>&nbsp;&nbsp;默认为tools所在程序的数据库,如果不知道其作用请不要修改</td></tr>
		<tr><th width=20%>目的编码</th><td><select name=todbcharset>'.$options.'</select>&nbsp;&nbsp;转换允许：\'latin1\'<=>\'gbk\',\'gbk\'<=>\'utf8\'</td></tr></tbody></table>
		<input name=submit type=submit value=转换></input>
		</form>';
}

function do_datago($mysql,$tableno,$do,$start,$limit){
	global $whereis, $dbhost, $dbuser, $dbpw, $tablepre,$fromdbname, $todbcharset, $dbcfg,$dbcharset;
	$allowcharset = array('latin1' => 'gbk','gbk' => 'utf8','utf8' => 'latin1');
	$tablename = 'Tables_in_'.strtolower($fromdbname).' ('.$tablepre.'%)';
	$mysql = mysql_connect($dbhost, $dbuser, $dbpw);
	mysql_select_db($fromdbname);
	mysql_query("SET sql_mode=''");
	$query = mysql_query('SHOW TABLES LIKE \''.$tablepre.'%\'');
	while($t = mysql_fetch_array($query,MYSQL_ASSOC)) {
		$tablearray[] = $t[$tablename];
	}
	$table = $tablearray["$tableno"];
	$query = mysql_query('SHOW TABLE STATUS LIKE '.'\''.$table.'\'');
	$tableinfo = array();
	
	while($t = mysql_fetch_array($query,MYSQL_ASSOC)) {
		$charset = explode('_',$t['Collation']);
		$t['Collation'] = $charset[0];
		$tableinfo = $t;
	}
	if($allowcharset[$tableinfo['Collation']] != $todbcharset && $allowcharset[$todbcharset] != $tableinfo['Collation']){
		if(strpos($tableinfo['Name'],$todbcharset) == 0) {
			$table = '';
		} else {
			echo "<h4>$title</h4><br><br><table><tr><th>提示信息</th></tr><tr><td>$tableinfo[Name] 表数据库编码出错</td></tr></table>";
			exit;
		}
	}
	mysql_query("SET NAMES '$tableinfo[Collation]'");
	
	if($do == 'create') {
		$tablecreate=array();
		foreach ($tablearray as $key => $value){
			$query=mysql_query("SHOW CREATE TABLE $value");
			while($t = mysql_fetch_array($query,MYSQL_ASSOC)){
				$t['Create Table'] = str_replace($tablepre,$whereis.'_',$t['Create Table']);
				$t['Create Table'] = str_replace("$tableinfo[Collation]","$todbcharset",$t['Create Table']);
				$t['Create Table'] = str_replace($whereis.'_',$todbcharset.$whereis.'_',$t['Create Table']);
				$t['Table'] = str_replace($tablepre,$todbcharset.$whereis.'_',$t['Table']);
				$tablecreate[]=$t;
			}
		}
		mysql_query('SET NAMES \''.$todbcharset.'\'');
		if(mysql_get_server_info() > '5.0'){
			mysql_query("SET sql_mode=''");
		}
		foreach ($tablecreate as $key => $value){
			mysql_query("DROP TABLE IF EXISTS `$value[Table]`");
			mysql_query($value['Create Table']);
			$count++;			
		}
		$toolstip .= '所有的表创建完成，数据库共有 '.$count.' 个表！<br>';
		show_tools_message($toolstip,"tools.php?action=datago&do=data&fromdbname=$fromdbname&todbcharset=$todbcharset&submit=%D7%AA%BB%BB");

	} elseif($do == 'data') {
		$count = 0;
		$data = array();
		$newtable = str_replace($tablepre,$todbcharset.$whereis.'_',$table);
		if($table) {
			mysql_query("SET NAMES '$tableinfo[Collation]'");
			$query = mysql_query("SELECT * FROM $table LIMIT $start,$limit");
			
			while($t = mysql_fetch_array($query,MYSQL_ASSOC)) {
				$data[] = $t;	
			}			
			unset($t);			
			$todbcharset2 = $todbcharset;
			if($tableinfo['Collation'] == 'utf8' || $todbcharset=='utf8'){
				$todbcharset2 = $tableinfo['Collation'];
			}
			mysql_query('SET NAMES \''.$todbcharset2.'\'');
			if(mysql_get_server_info() > '5.0'){
				mysql_query("SET sql_mode=''");
			}
			if($start == 0){
				mysql_query("TRUNCATE TABLE $newtable");
			}

			foreach($data as $key => $value){
				$sql='';
				foreach($value as $tokey => $tovalue){
					$tovalue = addslashes($tovalue);
					$sql = $sql ? $sql.",'".$tovalue."'" : "'".$tovalue."'";
				}
				mysql_query("INSERT INTO $newtable VALUES($sql)") or mysql_errno();
				$count++;
			}
			if($count == $limit) {
				$start += $count;
				show_tools_message("正在转移 $table 表的从 $start 条记录开始的后 $limit 条记录","tools.php?action=datago&do=data&fromdbname=$fromdbname&todbcharset=$todbcharset&tableno=$tableno&start=$start&submit=%D7%AA%BB%BB");
			} else {
				$tableno ++;
				show_tools_message("正在转移 $table 表的从 $start 条记录开始的后 $limit 条记录","tools.php?action=datago&do=data&fromdbname=$fromdbname&todbcharset=$todbcharset&tableno=$tableno&submit=%D7%AA%BB%BB",$time='1000');
			}
		} elseif($dbcharset == 'latin1' || $todbcharset == 'latin1') {
			echo "<div class=\"specialdiv2\" id=\"serialize\">转换提示：<ul>
				</ul></div>";
			echo '<script>$("serialize").innerHTML+="<li>转换完成！转换后的数据库前缀为：<font color=red>'.$todbcharset.$whereis.'_ </font></li>";
				$("serialize").scrollTop=$("serialize").scrollHeight;</script>';
		} else {
			$toolstip = '数据编码转换完毕，修复序列化数据。';
			show_tools_message($toolstip,"tools.php?action=datago&do=serialize&fromdbname=$fromdbname&todbcharset=$todbcharset&submit=%D7%AA%BB%BB");
		}
		
	} elseif($do == 'serialize' && $dbcharset!='latin1' && $todbcharset!='latin1') {
		if($whereis == 'is_ss') {
			$a = array('0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f');
			foreach($a as $num) {
				mysql_query("TRUNCATE TABLE ".$todbcharset.$whereis.'_'."cache_".$num);
			}
		}
		$arr = getlistarray($whereis,'datago');
		$limit = '3000';
		echo "<div class=\"specialdiv2\" id=\"serialize\">转换提示：<ul>
					</ul></div>";
		foreach($arr as $field) {
			$stable = $todbcharset.$whereis.'_'.$field[0];
			$sfield = $field[1];
			$sid	= $field[2];
			$query = mysql_query("SELECT $sid,$sfield FROM $stable ORDER BY $sid DESC LIMIT $limit");
			while($values = mysql_fetch_array($query,MYSQL_ASSOC)) {
				$data = $values[$sfield];
				$id   = $values[$sid];
				$data = preg_replace_callback('/s:([0-9]+?):"([\s\S]*?)";/','_serialize',$data);
				$data = taddslashes($data);
				if(mysql_query("update `$stable` set `$sfield`='$data' where `$sid`='$id'")) {
					$toolstip = $stable.' 表的 '.$sid.' 为 '.$id.' 的 '.$sfield.' 字段，修复成功<br/>';
				} else {
					$toolstip = $stable.' 表的 '.$sid.' 为 '.$id.' 的 '.$sfield.' 字段，<font color=red>修复失败</font><br/>';
				}
				echo '<script>$("serialize").innerHTML+="'.$toolstip.'";
					$("serialize").scrollTop=$("serialize").scrollHeight;</script>';
			}
		}
		mysql_close($mysql);
		echo '<script>$("serialize").innerHTML+="<li>转换完成！请检查修复记录。转换后的数据库前缀为：<font color=red>'.$todbcharset.$whereis.'_ </font></li>";
			$("serialize").scrollTop=$("serialize").scrollHeight;</script>';
	}
}

function _config_form($title = '',$varname = '',$end = '1') {
	global $$varname;
	$ucapi = UC_API;
	$ucip = UC_IP;
	$form = '';
	$form .= "<th width=20%>$title</th>";
	if($$varname  || isset($$varname)) {
		$form .= "<td><input class=textinput name=".$varname."2 value=".$$varname."></input></td>";
	} else {
		$form .= "<td></td>";	
	}
	if($end == '1') {
		$form .= '';		
	} elseif ($end == '2') {
		$form .= '</tr>';
	} elseif ($end == '3') {
		$form .= '</tr><tr>';	
	}
	echo $form;	
}
function all_doconfig_output($whereis){
	global $uc_dbhost, $uc_dbuser, $uc_dbpw, $uc_dbname,$uc_tablepre,$dbhost, $dbuser, $dbpw, $dbname, $dbcfg, $tablepre,$dbcharset,$uc_dbcharset;
	echo '<h5>配置文件</h5>';
	echo '<form method=post action=?action=all_config><table>
		<tbody>
		<tr>';
	if($whereis != 'is_uc') {
		_config_form($title = '数据库地址：',$varname = 'dbhost');
	}
	_config_form($title = 'UCenter 数据库地址：',$varname = 'uc_dbhost',$end = '3');

	if($whereis != 'is_uc') {
		_config_form($title = '数据库用户名：',$varname = 'dbuser');	
	}
	_config_form($title = 'UCenter 数据库用户名：',$varname = 'uc_dbuser',$end = '3');

	if($whereis != 'is_uc') {
		echo _config_form($title = '数据库密码：',$varname = 'dbpw');
	}
	_config_form($title = 'UCenter 数据库密码：',$varname = 'uc_dbpw',$end = '3');

	if($whereis != 'is_uc') {
		_config_form($title = '数据库名：',$varname = 'dbname');
	}
	_config_form($title = 'UCenter 数据库名：',$varname = 'uc_dbname',$end = '3');
	
	if($whereis != 'is_uc') {
		_config_form($title = '数据库前缀：',$varname = 'tablepre');
	}
	_config_form($title = 'UCenter 数据库前缀：',$varname = 'uc_tablepre',$end = '3');

	if($whereis != 'is_uc') {
		_config_form($title = '数据库编码：',$varname = 'dbcharset');
	}
	_config_form($title = 'UCenter 数据库编码：',$varname = 'uc_dbcharset',$end = '3');

	if($whereis != 'is_uc') {
		_config_form();
		_config_form($title = 'UCenter 地址：',$varname = 'ucapi',$end = '3');
	}
	
	if($whereis != 'is_uc') {
		_config_form();
		_config_form($title = 'UCenter IP：',$varname = 'ucip',$end = '2');
	}
	echo		'</tbody>
			</table>
			<input name=submit type=submit value=修改></input>
			</form>';
}

function all_doconfig_modify($whereis){
	global $dbhost2, $dbuser2, $dbpw2, $dbname2, $tablepre2,$dbcharset2;
	if($whereis == 'is_dz') {
		//  /\$dbhost.+;/i
		if(file_exists('./uc_server/data/config.inc.php')) {
			$config = file_get_contents('./uc_server/data/config.inc.php');
			writefile('./uc_server/data/config.bak.php.'.time(),$config);
			$config = uc_doconfig_modify($config);
			writefile('./uc_server/data/config.inc.php',$config);
		}
		$config = file_get_contents('./config.inc.php');
		writefile('./forumdata/config.bak.php.'.date(ymd,time()),$config);
		$config = preg_replace('/\$dbhost.+;/i','$dbhost = \''.$dbhost2.'\';',$config);
		$config = preg_replace('/\$dbuser.+;/i','$dbuser = \''.$dbuser2.'\';',$config);
		$config = preg_replace('/\$dbpw.+;/i','$dbpw = \''.$dbpw2.'\';',$config);
		$config = preg_replace('/\$dbname.+;/i','$dbname = \''.$dbname2.'\';',$config);
		$config = preg_replace('/\$tablepre.+;/i','$tablepre = \''.$tablepre2.'\';',$config);
		$config = preg_replace('/\$dbcharset.+;/i','$dbcharset = \''.$dbcharset2.'\';',$config);
		$config = uc_doconfig_modify($config);
		if(writefile('./config.inc.php',$config)) {
			show_tools_message('配置文件已经成功修改，原配置文件已经备份到forumdata目录下。','tools.php?action=all_config');
		}
	} elseif($whereis == 'is_uch' || $whereis == 'is_ss') {
		$config = file_get_contents('./config.php');
		writefile('./data/config.bak.php.'.date(ymd,time()),$config);
		$config = preg_replace('/\$_SC\[\'dbhost\'\].+;/i','$_SC[\'dbhost\'] = \''.$dbhost2.'\';',$config);
		$config = preg_replace('/\$_SC\[\'dbuser\'\].+;/i','$_SC[\'dbuser\'] = \''.$dbuser2.'\';',$config);
		$config = preg_replace('/\$_SC\[\'dbpw\'\].+;/i','$_SC[\'dbpw\'] = \''.$dbpw2.'\';',$config);
		$config = preg_replace('/\$_SC\[\'dbname\'\].+;/i','$_SC[\'dbname\'] = \''.$dbname2.'\';',$config);
		$config = preg_replace('/\$_SC\[\'tablepre\'\].+;/i','$_SC[\'tablepre\'] = \''.$tablepre2.'\';',$config);
		$config = preg_replace('/\$_SC\[\'dbcharset\'\].+;/i','$_SC[\'dbcharset\'] = \''.$dbcharset2.'\';',$config);
		$config = uc_doconfig_modify($config);
		if(writefile('./config.php',$config)) {
			show_tools_message('配置文件已经成功修改，原配置文件已经备份到data目录下。','tools.php?action=all_config');
		}
	} elseif($whereis == 'is_uc') {
		$config = file_get_contents('./data/config.inc.php');
		writefile('./data/config.bak.php.'.date(ymd,time()),$config);
		$config = uc_doconfig_modify($config);
		if(writefile('./data/config.inc.php',$config)) {
			show_tools_message('配置文件已经成功修改，原配置文件已经备份到data目录下。','tools.php?action=all_config');
		}
	}
}

function uc_doconfig_modify($config='') {
	global $uc_dbhost2, $uc_dbuser2, $uc_dbpw2, $uc_dbname2,$uc_tablepre2,$ucapi2,$ucip2,$uc_dbcharset2;
	$config = preg_replace('/define\(\'UC_DBHOST\'.+;/i','define(\'UC_DBHOST\', \''.$uc_dbhost2.'\');',$config);
	$config = preg_replace('/define\(\'UC_DBUSER\'.+;/i','define(\'UC_DBUSER\', \''.$uc_dbuser2.'\');',$config);
	$config = preg_replace('/define\(\'UC_DBPW\'.+;/i','define(\'UC_DBPW\', \''.$uc_dbpw2.'\');',$config);
	$config = preg_replace('/define\(\'UC_DBNAME\'.+;/i','define(\'UC_DBNAME\', \''.$uc_dbname2.'\');',$config);
	$config = preg_replace('/define\(\'UC_DBTABLEPRE\'.+;/i','define(\'UC_DBTABLEPRE\', \''.$uc_tablepre2.'\');',$config);
	$config = preg_replace('/define\(\'UC_DBCHARSET\'.+;/i','define(\'UC_DBCHARSET\', \''.$uc_dbcharset2.'\');',$config);
	$config = preg_replace('/define\(\'UC_API\'.+;/i','define(\'UC_API\', \''.$ucapi2.'\');',$config);
	$config = preg_replace('/define\(\'UC_IP\'.+;/i','define(\'UC_IP\', \''.$ucip2.'\');',$config);
	return $config;
}

function writefile($filename, $writetext, $openmod='w') {
	if(@$fp = fopen($filename, $openmod)) {
		flock($fp, 2);
		fwrite($fp, $writetext);
		fclose($fp);
		return true;
	} else {
		return false;
	}
}

function xml2array($xml) {
	$arr = xml_unserialize($xml, 1);
	preg_match('/<error errorCode="(\d+)" errorMessage="([^\/]+)" \/>/', $xml, $match);
	$arr['error'] = array('errorcode' => $match[1], 'errormessage' => $match[2]);
	return $arr;
}

function getbakurl($whereis,$action) {
	if ($whereis != 'is_uc') {
		require_once './uc_client/client.php';
		require_once './uc_client/model/base.php';
	} else {
		define('IN_UC',TRUE);
		define('UC_ROOT','./');
		require_once './model/base.php';	
	}

	$base = new base();
	$salt = substr(uniqid(rand()), -6);
	$action = !empty($action) ? $action : 'export';
	$url = 'http://'.$_SERVER['HTTP_HOST'].str_replace('tools.php', 'api/dbbak.php', $_SERVER['PHP_SELF']);
	if($whereis == 'is_dz') {
		$apptype = 'discuz';
	} elseif ($whereis == 'is_uc') {
		$apptype = 'ucenter';
	} elseif ($whereis == 'is_uch') {
		$apptype = 'uchome';
	} elseif ($whereis == 'is_ss') {
		$apptype = 'supesite';
	}
	$url .= '?apptype='.$apptype;
	$code = $base -> authcode('&method='.$action.'&time='.time(), 'ENCODE', UC_KEY);
	$url .= '&code='.urlencode($code);
	return $url;
}

function dobak($url,$num = '1') {
	global $whereis;
	$num = !empty($num) ? $num : '0';
	$return = file_get_contents($url);
	if($whereis != 'is_uc') {
		require_once './uc_client/lib/xml.class.php';
	} else {
		require_once './lib/xml.class.php';	
	}
	$arr = xml2array($return);
	
	if($arr['error']['errormessage'] == 'explor_success') {
		echo "<div class=\"specialdiv\">备份提示：<ul>
			<li>>>>>>>>>备份完成<<<<<<<<</li>
			<li>>>>>>>>>共：".$num."个文件<<<<<<<<</li>
			</ul></div>";
	} else {
		$num ++;
		echo "<div class=\"specialdiv\">备份提示：<ul>
			<li>".$arr['fileinfo']['file_name']."......".$arr['error']['errormessage']."</li>
			</ul></div>";
	}
	if($arr['nexturl']) {
		$url = './tools.php?action=all_backup&nexturl='.urlencode($arr['nexturl']).'&num='.$num;
		show_tools_message($arr['fileinfo']['file_name'],$url,$time = 2000);
	}
}
	
function getgpc($k, $var='G') {
	switch($var) {
		case 'G': $var = &$_GET; break;
		case 'P': $var = &$_POST; break;
		case 'C': $var = &$_COOKIE; break;
		case 'R': $var = &$_REQUEST; break;
	}
	return isset($var[$k]) ? $var[$k] : NULL;
}

function getlistarray($whereis,$type) {
	global $dz_version,$ss_version,$uch_version;
	if($whereis == 'is_dz' && $dz_version >= '710') {
		if($type == 'datago') {
			$list = array(
				array('advertisements','parameters','advid'),
				array('request','value','variable'),
				array('settings','value','variable'),
			);
		}
	} elseif($whereis == 'is_uch' && $uch_version >= '15') {
		if($type == 'datago') {
			$list = array(
				array('ad','adcode','adid'),
				array('blogfield','tag','blogid'),
				array('blogfield','related','blogid'),
				array('feed','title_data','feedid'),
				array('feed','body_data','feedid'),
				array('share','body_data','sid'),
			);	
		}
	} elseif($whereis == 'is_uc') {
		if($type == 'datago') {
			$list = array(
				array('feed','title_data','feedid'),
				array('feed','body_data','feedid'),
				array('settings','v','k'),
			);
		}
	} elseif($whereis == 'is_ss' && $ss_version >=70) {
		if($type == 'datago') {
			$list = array(
				array('ads','parameters','adid'),
				array('blocks','blocktext','blockid'),
			);
		}
	}
	return $list;
}

function _serialize($str) {
	global $dbcharset,$todbcharset;
	$charset = $dbcharset == 'utf8' ? 'utf-8':$dbcharset;
	$tempdbcharset = $todbcharset == 'utf8' ? 'utf-8':$todbcharset;
	$charset = strtoupper($charset);
	$tempdbcharset = strtoupper($tempdbcharset);
	$temp = iconv($charset,$tempdbcharset,$str[2]);
	$l = strlen($temp);
	return 's:'.$l.':"'.$str[2].'";';
}

function ping($whereis) {
	global $plustitle,$dbhost,$dbuser,$dbpw,$dbname,$uc_dbhost,$uc_dbuser,$uc_dbpw,$uc_dbname;
	if($whereis != 'is_uc')	{
		$ping = @mysql_connect($dbhost,$dbuser,$dbpw);
		if($ping) {
			$message = "数据库连接:<font color=green>[成功]</font>......";
			if (mysql_select_db($dbname,$ping)) {
				$message .= " $dbname 数据库<font color=green>[存在]</font>";
			} else {
				$message .= " $dbname 数据库<font color=red>[不存在]</font>";	
			}
			mysql_close($ping);
		} else {
			$message = "数据库连接:<font color=red>[失败]</font> ";	
		}
		$message .= '<br/>';
		if(file_get_contents(UC_API.'/index.php')) {
			$message .= 'UCenter <font color=green>[地址正确]</font>......';	
		} else {
			$message .= 'UCenter <font color=red>[地址错误]</font>......';
		}
	}
	$ping = @mysql_connect($uc_dbhost,$uc_dbuser,$uc_dbpw);
	if($ping) {
		$message .= "UCenter 数据库连接:<font color=green>[成功]</font>......";
		if (mysql_select_db($uc_dbname,$ping)) {
			$message .= " $uc_dbname 数据库<font color=green>[存在]</font>";
		} else {
			$message .= " $uc_dbname 数据库<font color=red>[不存在]</font>";	
		}
		mysql_close($ping);
	} else {
		$message .= "UCenter 数据库连接:<font color=red>[失败]</font> ";		
	}
	$message .= '<br/>';
	echo '<script>$(\'ping\').innerHTML += \''.$plustitle.' '.$message.'\'</script>';
}

function checkfilesoutput($modifylists,$deletedfiles,$unknownfiles) {
	$modifystats = (count($modifylists)) > 0 ? '<a href="?action=dz_filecheck&detail=modifytrue&begin=1">［查看］</a>': '';
	$delstats = (count($deletedfiles)) > 0 ? '<a href="?action=dz_filecheck&detail=deltrue&begin=1">［查看］</a>': '';
	$unknowtrue = (count($unknownfiles)) > 0 ? '<a href="?action=dz_filecheck&detail=unknowtrue&begin=1">［查看］</a>': '';
	echo '<pre>';
	echo "被修改文件: ".count($modifylists) .$modifystats."<br />丢失文件: ".count($deletedfiles).$delstats."<br />未知文件:".count($unknownfiles).$unknowtrue;
	echo '</pre>';
	echo '----------------------------------------------------------------------------<br>';
	if (!empty($_GET['detail'])){
		$predir = '';
		if ($_GET['detail'] == 'modifytrue'){
			echo'被修改文件:<br />';
			foreach ($modifylists as $value){
				$vdir = explode('/',$value);
				$vdir[0] = $vdir[0] == '.' ? '根' : $vdir[0]; 
				if($vdir[0] != $predir) {
					$predir = $vdir[0];
					echo "<span class='current'>".$predir."目录</span><br/>";
				}
				echo "&nbsp;&nbsp;&nbsp;".$value."<br/>";
			}
		}elseif($_GET['detail'] == 'deltrue'){
			echo '丢失文件:<br />';
			foreach ($deletedfiles as $value){
				$vdir = explode('/',$value);
				$vdir[0] = $vdir[0] == '.' ? '根' : $vdir[0]; 
				if($vdir[0] != $predir) {
					$predir = $vdir[0];
					echo "<span class='current'>".$predir."目录</span><br/>";
				}
				echo "&nbsp;&nbsp;&nbsp;".$value."<br/>";
			}
		}elseif($_GET['detail'] == 'unknowtrue'){
			echo '未知文件:<br />';
			foreach ($unknownfiles as $value){
				$vdir = explode('/',$value);
				$vdir[0] = $vdir[0] == '.' ? '根' : $vdir[0]; 
				if($vdir[0] != $predir) {
					$predir = $vdir[0];
					echo "<span class='current'>".$predir."目录</span><br/>";
				}
				echo "&nbsp;&nbsp;&nbsp;".$value."<br/>";
			}
		}
	}	
}

function docheckfiles($dz_files,$md5data) {
	global $modifylists,$deletedfiles,$unknownfiles;
	foreach($dz_files as $line) {
		$file = trim(substr($line, 34));
		$md5datanew[$file] = substr($line, 0, 32);
		$md5 = substr($line, 34);
		if (empty($md5data[$file]))
		{
			$deletedfiles[] = $file;
			$deltrue = 1;
			continue;
		}

		if($md5datanew[$file] != $md5data[$file]) {
			$modifylists[$file] = $file;
			$modifytrue = 1;
		}
		
	}

	$addlist = @array_diff_assoc($md5data, $md5datanew);

	if (empty($modifylists)) {
		foreach ($addlist as $file => $value){
			$unknownfiles[$file] = $file;
		}
	} else {
		foreach ($addlist as $file => $value){
			$dir = dirname($file);
			if (!array_key_exists($file, $modifylists)){
				$unknownfiles[$file] = $file;
				$unknowtrue = 1;
			}
		}
	}	
}

function infobox($str,$link) {
	if($link) {
		$button = "<input class='button' type='submit' onclick=\"location.href='".$link."'\" value='开始' name='submit'/>";	
	}
	echo "<div class='infobox'><p>$str</p>$button</div>";
}
?>