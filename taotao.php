<?php
/*
Plugin Name: TaoTao
Plugin URI: http://www.gegewan.org
Description: 把你的滔滔消息(taotao.com)同步到你的 WordPress
Author: gegewan.org (weiqk@hotmail.com)
Author URI: http://www.gegewan.org
Version: 1.000
*/

register_activation_hook(__FILE__, 'taotao_activation');
function taotao_activation(){
	$taotao_account = intval(get_option('taotao_account'));
	$taotao_period = intval(get_option('taotao_period'));
	$taotao_cachetime = intval(get_option('taotao_cachetime'));
	$taotao_more = intval(get_option('taotao_more'));
	$taotao_link = intval(get_option('taotao_link'));
	$taotao_items = intval(get_option('taotao_items'));
	if($taotao_account < 10000){add_option('taotao_account', '103614932', '', 'yes');}
	if(!$taotao_period) add_option('taotao_period', '3600', '', 'yes');
	if(!$taotao_cachetime) add_option('taotao_cachetime', '0', '', 'yes');	
	if(!$taotao_more) add_option('taotao_more', '1', '', 'yes');	
	if(!$taotao_link) add_option('taotao_link', '1', '', 'yes');	
	if(!$taotao_items) add_option('taotao_items', '15', '', 'yes');	
}

add_filter('the_content', 'get_taotao_rss', 25);
function get_taotao_rss($content){
	if(stripos($content, '[taotao]') === flase) return $content;
	if (!is_single() && !is_page()) return $content;
	$taotao_account = intval(get_option('taotao_account'));
	$taotao_period = intval(get_option('taotao_period'));
	$taotao_cachetime = intval(get_option('taotao_cachetime'));
	$taotao_more = intval(get_option('taotao_more'));
	$taotao_link = intval(get_option('taotao_link'));
	$taotao_items = intval(get_option('taotao_items'));
	
	$taotao_now = time();
	
	$taotao_cachefile = dirname(__FILE__) . '/cache/taotao.php';
	$rss = array();
	
	if(is_readable($taotao_cachefile) && $taotao_cachetime + $taotao_period > $taotao_now){
		$rss = unserialize(file_get_contents($taotao_cachefile));
	}
	else{
		$rss_file = "http://pipes.yahoo.com/pipes/pipe.run?_id=deadbfd8e9a0ce354e0ed2ae7c7d7c18&_render=rss&num={$taotao_items}&qq={$taotao_account}";
		$rss_object = simplexml_load_string(file_get_contents($rss_file));
		foreach($rss_object->channel->item as $rss_item){
			$rss[] = array(
				'msg'=> (string)$rss_item->description,
				'reply'=> (string)$rss_item->link,
				'pubDate'=> date('Y-m-d H:i', strtotime($rss_item->pubDate))
			);
		}
		
		//if(is_writable($taotao_cachefile)){
			@file_put_contents($taotao_cachefile, serialize($rss));
			update_option('taotao_cachetime', $taotao_now);
		//}		
	}
	$html = '<div class="taotao"><p class="title">我的滔滔</p><ul>';
	//$html = print_r($rss, true);
	foreach($rss as $taotao_msg){
	$html .= "<li><span class='msg'>{$taotao_msg['msg']}</span>&nbsp
	<span class='note'>@{$taotao_msg['pubDate']}</span>&nbsp<a class='reply' target='_blank' href='{$taotao_msg['reply']}' rel='nofollow'>回复</a>
{$$taotao_msg['msg']}</li>";
	}
	$html .= '</ul>';
	if($taotao_more || $taotao_link){
		$html .= "<p class='more'>";
		if($taotao_more) $html .= "<a style='float:left' class='morelink' target='_blank' href='http://www.taotao.com/v1/space/{$taotao_account}' rel='nofollow'>更多唠叨。。。</a>";
		if($taotao_link) $html .= "<a style='float:right' class='ggwlink' target='_blank' href='http://www.gegewan.org/wplugin'>gegewan wordpress plugin</a>";
		$html .= "</p>";
	}
	$html .= '</div>';
	
	//替换[taotao]标签为滔滔的内容
	return str_ireplace('[taotao]', $html, $content);
}

function taotao_admin_settings()
{
   add_options_page("TaoTao Sync", "TaoTao Sync", 1, "TaoTao Sync", "taotao_display_settings");
}

function taotao_display_settings(){
	$taotao_account = intval(get_option('taotao_account'));
	$taotao_period = intval(get_option('taotao_period'));
	$taotao_cachetime = intval(get_option('taotao_cachetime'));
	$taotao_more = intval(get_option('taotao_more'));
	$taotao_link = intval(get_option('taotao_link'));
	$taotao_items = intval(get_option('taotao_items'));	
	
	if($_SERVER['REQUEST_METHOD'] == 'POST') {
		//Form data sent
		$taotao_account = isset($_POST['taotao_account']) ? (int)$_POST['taotao_account'] : $taotao_account;
		update_option('taotao_account', $taotao_account);
		
		$taotao_items = isset($_POST['taotao_items']) ? (int)$_POST['taotao_items'] : $taotao_items;
		update_option('taotao_items', $taotao_items);
		
		$taotao_more = isset($_POST['taotao_more']) ? (int)$_POST['taotao_more'] : $taotao_more;
		update_option('taotao_more', $taotao_more);
		
		$taotao_link = isset($_POST['taotao_link']) ? (int)$_POST['taotao_link'] : $taotao_link;
		update_option('taotao_link', $taotao_link);
		
		$taotao_period = isset($_POST['taotao_period']) ? (int)$_POST['taotao_period'] : $taotao_period;
		update_option('taotao_period', $taotao_period);
		
		?>
		<div class="updated"><p><strong><?php _e('Options saved.' ); ?></strong></p></div>
		<?php
	}
   
	if($taotao_link) $taotao_show_link =' checked="checked"';
	if($taotao_more) $taotao_show_more =' checked="checked"';
	if($taotao_period == 300){
		$p300 =' selected="selected"';
	}
	elseif($taotao_period == 900){
		$p900 =' selected="selected"';
	}
	elseif($taotao_period == 3600){
		$p3600 =' selected="selected"';
	}
	elseif($taotao_period == 43200){
		$p43200 =' selected="selected"';
	}
	elseif($taotao_period == 86400){
		$p86400 =' selected="selected"';
	}
	else{
		$p3600 =' selected="selected"';
	}
?>

   <div class="wrap">
      <h2>滔滔同步设置</h2>
      <form name="taotao_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
	 <p>滔滔账号，也就是你的QQ号码: 
	 <input type="text" name="taotao_account" value="<?php echo $taotao_account ?> " /> </p>
	 <br />
	 <p>缓存时间:
	 <select name="taotao_period">
		<option <?php echo $p300 ?> value="300">5分钟</option>
		<option <?php echo $p900 ?> value="900">15分钟</option>
		<option <?php echo $p3600 ?> value="3600">1小时</option>
		<option <?php echo $p43200 ?> value="43200">12小时</option>
		<option <?php echo $p86400 ?> value="86400">24小时</option>
	 </select>
	 </p>
	 <br />
	 <p>滔滔消息数量: <input type="text" name="taotao_items" size="3" value="<?php echo $taotao_items ?>" /></p>
	 <br />
	 <p>显示"<b>更多</b>"链接 <input type="checkbox" name="taotao_more" value="1" <?php echo $taotao_show_more ?>" /></p>
	 <p>赞助作者一个连接 <input type="checkbox" name="taotao_link " value="1" <?php echo $taotao_show_link ?>" /></p>
	 <p class="submit"><input type="submit" name="Submit" value="Update Options" /></p>
	 <br>
	 <p>在要显示滔滔消息的页面里面加入[taotao]标签即可显示滔滔消息。</p>
	 <img width="0" height="0" src="http://img.tongji.linezing.com/737000/tongji.gif"/>
   </form>
   </div>
<?php
}

add_action('admin_menu', 'taotao_admin_settings');



