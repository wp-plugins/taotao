<?php
/*
Plugin Name: TaoTao
Plugin URI: http://www.gegewan.org
Description: 把你的滔滔消息(taotao.com)同步到你的 WordPress
Author: gegewan.org (weiqk@hotmail.com)
Author URI: http://www.gegewan.org
Version: 1.001
*/

register_activation_hook(__FILE__, 'taotao_activation');
function taotao_activation(){
	$taotao_account = intval(get_option('taotao_account'));
	$taotao_period = intval(get_option('taotao_period'));
	$taotao_cachetime = intval(get_option('taotao_cachetime'));
	$taotao_more = intval(get_option('taotao_more'));
	$taotao_link = intval(get_option('taotao_link'));
	$taotao_showtime = intval(get_option('taotao_showtime'));
	$taotao_showreply = intval(get_option('taotao_showreply'));
	$taotao_widgets = intval(get_option('taotao_widgets'));
	$taotao_items = intval(get_option('taotao_items'));
	
	if($taotao_account < 10000){add_option('taotao_account', '103614932', '', 'yes');}
	if(!$taotao_period) add_option('taotao_period', '3600', '', 'yes');
	if(!$taotao_cachetime) add_option('taotao_cachetime', '0', '', 'yes');	
	if(!$taotao_more) add_option('taotao_more', '1', '', 'yes');	
	if(!$taotao_link) add_option('taotao_link', '1', '', 'yes');	
	if(!$taotao_showtime) add_option('taotao_showtime', '1', '', 'yes');	
	if(!$taotao_showreply) add_option('taotao_showreply', '1', '', 'yes');	
	if(!$taotao_widgets) add_option('taotao_widgets', '5', '', 'yes');	
	if(!$taotao_items) add_option('taotao_items', '15', '', 'yes');	
}

add_filter('the_content', 'get_taitao_content', 25);
function get_taitao_content($content){
	if (!is_single() && !is_page()) return $content;
	if(stripos($content, '[taotao]') === flase) return $content;
	
	$taotao_account = intval(get_option('taotao_account'));
	$taotao_showtime = intval(get_option('taotao_showtime'));
	$taotao_showreply = intval(get_option('taotao_showreply'));
	$taotao_replylink = intval(get_option('taotao_replylink'));
	$taotao_more = intval(get_option('taotao_more'));
	$taotao_link = intval(get_option('taotao_link'));
	
	$html = '<div class="taotao"><p class="title">我的滔滔</p>';
	$html .= wp_get_taotao(0, $taotao_showtime, $taotao_showreply, $taotao_replylink, 0, 0);
	if($taotao_more || $taotao_link){
		$html .= "<p class='more'>";
		if($taotao_more) $html .= "<a style='float:left' class='morelink' target='_blank' href='http://www.taotao.com/v1/space/{$taotao_account}&invi=1' rel='nofollow'>更多唠叨。。。</a>";
		if($taotao_link) $html .= "<a style='float:right' class='ggwlink' target='_blank' href='http://www.gegewan.org/wplugin'>professional wordpress plugin</a>";
		$html .= "</p>";
	}	
	
	$html .= '</div>';
	
	//替换[taotao]标签为滔滔的内容
	return str_ireplace('[taotao]', $html, $content);
}

function get_taotao_rss(){
	static $rss = array();
	if(!empty($rss)) return $rss;
	$taotao_account = intval(get_option('taotao_account'));
	$taotao_period = intval(get_option('taotao_period'));
	$taotao_cachetime = intval(get_option('taotao_cachetime'));
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
		
		@file_put_contents($taotao_cachefile, serialize($rss));
		update_option('taotao_cachetime', $taotao_now);		
	}
	return $rss;
}

// 滔滔插件的主题函数 wp_get_taotao
function wp_get_taotao($items=0, $showtime=0, $showreply=1, $showmore=1, $showlink=1){
	$rss = wp_get_taotao_msg($items);
	$taotao_account = intval(get_option('taotao_account'));
	
	$html = '<ul>';

	foreach($rss as $taotao_msg){
		if($showreply){
			$html .= "<li><span class='msg'><a target='_blank' href='{$taotao_msg['reply']}' rel='nofollow'>{$taotao_msg['msg']}</a></span>";
			if($showtime) $html .= "&nbsp<span class='note'>@{$taotao_msg['pubDate']}</span>&nbsp";
			$html .= "</li>";
		}
		else{
			$html .= "<li><span class='msg'>{$taotao_msg['msg']}</span>";
			if($showtime) $html .= "&nbsp<span class='note'>@{$taotao_msg['pubDate']}</span>&nbsp";
			$html .= "<a class='reply' target='_blank' href='{$taotao_msg['reply']}' rel='nofollow'>回复</a>";
			$html .= "</li>";
		}
	}
	if($showmore) $html .= "<li><a target='_blank' href='http://www.taotao.com/v1/space/{$taotao_account}&invi=1' rel='nofollow'>更多唠叨。。。</a></li>";
	if($showlink) $html .= "<li><a target='_blank' href='http://www.gegewan.org/wplugin'>professional wordpress plugin</a></li>";
	
	$html .= '</ul>';
	
	return $html;
}

// 滔滔插件主题函数 wp_get_taotao_msg
function wp_get_taotao_msg($items=0){
	$rss = get_taotao_rss();
	if(0 == $items) $items = count($rss);
	$items = $items > count($rss) ? count($rss) : $items;
	return array_slice($rss, 0, $items);	
}

// 滔滔的widget
class TaoTaoWidget extends WP_Widget {
    /** 构造函数 */
    function TaoTaoWidget() {
		$widget_ops = array('description' => '滔滔消息小工具');
		$this->WP_Widget(false, $name = '我的滔滔', $widget_ops);
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {		
        extract( $args );
		$title = $instance['title'] ? esc_attr($instance['title']) : $widget_name;
		
		echo $before_widget.$before_title.$title.$after_title;
		echo wp_get_taotao($instance['items'], $instance['showtime'], $instance['showreply'], $instance['showmore'], $instance['showlink']);
		echo $after_widget;
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {				
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['items'] = intval($new_instance['items']);
		$instance['showtime'] = intval($new_instance['showtime']);
		$instance['showreply'] = intval($new_instance['showreply']);
		$instance['showmore'] = intval($new_instance['showmore']);
		$instance['showlink'] = intval($new_instance['showlink']);
		return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {
		$instance = wp_parse_args(
			(array)$instance,
			array(
				'title' => __('Views', 'wp-postviews'),
				'items' => 5,
				'showtime' => 0,
				'showreply' => 1,
				'showmore' => 1,
				'showlink' => 1
			)
		);
        $title = esc_attr($instance['title']);
        $items = intval($instance['items']) > 0 ? intval($instance['items']) : 5;
        $showtime = intval($instance['showtime']) > 0 ? 'checked="checked"' : '';
        $showreply0 = intval($instance['showreply']) > 0 ? '' : 'checked="checked"';
        $showreply1 = intval($instance['showreply']) > 0 ? 'checked="checked"' : '';
        $showmore = intval($instance['showmore']) > 0 ? 'checked="checked"' : '';
        $showlink = intval($instance['showlink']) > 0 ? 'checked="checked"' : '';
        ?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>">
			<?php _e('Title:'); ?>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('items'); ?>">
			消息数目:</label>
			<input size="3" id="<?php echo $this->get_field_id('items'); ?>" name="<?php echo $this->get_field_name('items'); ?>" type="text" value="<?php echo $items; ?>" />
			
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('showtime'); ?>">
			<input id="<?php echo $this->get_field_id('showtime'); ?>" name="<?php echo $this->get_field_name('showtime'); ?>" type="checkbox" value="1" <?php echo $showtime; ?>/>
	        显示消息发布时间
			</label>
		</p>
		<p>
			<label>
			<input name="<?php echo $this->get_field_name('showreply'); ?>" type="radio" value="1" <?php echo $showreply1; ?>/>
	        在消息中显示回复链接
			</label>
		</p>
		<p>
			<label>
			<input name="<?php echo $this->get_field_name('showreply'); ?>" type="radio" value="0" <?php echo $showreply0; ?>/>
	        显示单独的回复链接
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('showmore'); ?>">
			<input id="<?php echo $this->get_field_id('showmore'); ?>" name="<?php echo $this->get_field_name('showmore'); ?>" type="checkbox" value="1" <?php echo $showmore; ?>/>
	        显示更多滔滔消息链接
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('showlink'); ?>">
			<input id="<?php echo $this->get_field_id('showlink'); ?>" name="<?php echo $this->get_field_name('showlink'); ?>" type="checkbox" value="1" <?php echo $showlink; ?>/>
	        赞助作者一个链接
			</label>
		</p>
        <?php 
    }

}
add_action('widgets_init', create_function('', 'return register_widget("TaoTaoWidget");'));

function taotao_admin_settings()
{
   add_options_page("TaoTao Sync", "TaoTao Sync", 1, "TaoTao Sync", "taotao_display_settings");
}

function taotao_display_settings(){
	$taotao_account = intval(get_option('taotao_account'));
	$taotao_period = intval(get_option('taotao_period'));
	$taotao_cachetime = intval(get_option('taotao_cachetime'));
	$taotao_showtime = intval(get_option('taotao_showtime'));
	$taotao_showreply = intval(get_option('taotao_showreply'));
	$taotao_more = intval(get_option('taotao_more'));
	$taotao_link = intval(get_option('taotao_link'));
	$taotao_items = intval(get_option('taotao_items'));	
	
	if($_SERVER['REQUEST_METHOD'] == 'POST') {
		//Form data sent
		$taotao_account = isset($_POST['taotao_account']) ? (int)$_POST['taotao_account'] : $taotao_account;
		update_option('taotao_account', $taotao_account);
		
		$taotao_items = isset($_POST['taotao_items']) ? (int)$_POST['taotao_items'] : $taotao_items;
		update_option('taotao_items', $taotao_items);
		
		$taotao_showtime = isset($_POST['taotao_showtime']) ? (int)$_POST['taotao_showtime'] : $taotao_showtime;
		update_option('taotao_showtime', $taotao_showtime);
		
		$taotao_showreply = isset($_POST['taotao_showreply']) ? (int)$_POST['taotao_showreply'] : $taotao_showreply;
		update_option('taotao_showreply', $taotao_showreply);
		
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
	
	$showreply1 = $taotao_showreply > 0 ? ' checked="checked"' : '';
	$showreply0 = $taotao_showreply > 0 ? '' : ' checked="checked"';
   
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
	<p><strong>以下是全局设置</strong></p>
	<hr />
	 <p><label>滔滔账号，也就是你的QQ号码: <input type="text" name="taotao_account" value="<?php echo $taotao_account ?> " /> </label></p>
	 <br />
	 <p><label>缓存时间:
	 <select name="taotao_period">
		<option <?php echo $p300 ?> value="300">5分钟</option>
		<option <?php echo $p900 ?> value="900">15分钟</option>
		<option <?php echo $p3600 ?> value="3600">1小时</option>
		<option <?php echo $p43200 ?> value="43200">12小时</option>
		<option <?php echo $p86400 ?> value="86400">24小时</option>
	 </select></label>
	 </p>
	 <br />
	 
	<p><strong>以设置仅影响在页面里面加入[taotao]标签调用滔滔消息的情况</strong></p>
	<hr />
	<p><label>滔滔消息数量: <input type="text" name="taotao_items" size="3" value="<?php echo $taotao_items ?>" /></label></p>
	 <p><label><input type="checkbox" name="taotao_showtime" value="1" <?php echo $taotao_show_more ?>" />显示消息发布时间</label></p>
	 <p><label><input type="radio" name="taotao_showreply" value="1" <?php echo $showreply1 ?>" />在消息中显示回复链接 </label></p>
	 <p><label><input type="radio" name="taotao_showreply" value="0" <?php echo $showreply0 ?>" />显示单独的回复链接 </label></p>
	 <p><label><input type="checkbox" name="taotao_more" value="1" <?php echo $taotao_show_more ?>" />显示"<b>更多</b>"链接 </label></p>
	 <p><label><input type="checkbox" name="taotao_link " value="1" <?php echo $taotao_show_link ?>" />赞助作者一个连接 </label></p>
	 <p class="submit"><input type="submit" name="Submit" value="Update Options" /></p>
	 <br>
	 <p>在要显示滔滔消息的页面里面加入[taotao]标签即可显示滔滔消息。</p>
	 <img width="0" height="0" src="http://img.tongji.linezing.com/737000/tongji.gif"/>
   </form>
   </div>
<?php
}

add_action('admin_menu', 'taotao_admin_settings');



