<?php
/*
Plugin Name: What's My Status?
Plugin URI: http://roadha.us/portfolio/whats-my-status/
Description: Provides a feed of a given user's posts on <a href="http://identi.ca">identi.ca</a>, <a href="http://twitter.com">Twitter</a>, etc.
Version: 1.2
Author: haliphax
Author URI: http://roadha.us/
License: GPLv3
*/

/*
Copyright 2011 Todd Boyd

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

# widget class =================================================================
class WP_Widget_WhatsMyStatus extends WP_Widget
{
	# constructor ==============================================================
	function WP_Widget_WhatsMyStatus()
	{
		# set options
		$widget_opts = array(
			'classname' => 'widget_whats_my_status',
			'description' => __('Your status feed', 'whats_my_status_widget')
			);
		# instantiate widget
		$this->WP_Widget('whats-my-status', __("What's My Status?", 'whats_my_status_widget'), $widget_opts);
	}
	
	# render widget ============================================================
	function widget($args, $instance)
	{
		extract($args);
		# default API URL
		$api_url = empty($instance['api_url'])
			? 'https://api.twitter.com/1/' : $instance['api_url'];
		if(preg_match('#/$#', $api_url) <= 0)
			$api_url .= '/';
		# default #hashtag URL
		$hashtag_url = empty($instance['hashtag_url'])
			? 'http://twitter.com/search?q=%23' : $instance['hashtag_url'];
		# default @mention URL
		$mention_url = empty($instance['mention_url'])
			? 'http://twitter.com/' : $instance['mention_url'];
		$screen_name = $instance['screen_name'];
		# default title - @screen_name
		$title = empty($instance['title'])
			? __("@{$screen_name}", 'whats_my_status_widget')
			: apply_filters('widget_title', $instance['title']);
		# default offset
		$offset = empty($instance['gmt_offset'])
			? 0 : (int) $instance['gmt_offset'];
		# default post count
		$post_count = empty($instance['post_count'])
			? 5 : (int) $instance['post_count'];
		# exclude replies?
		$exclude_replies = empty($instance['exclude_replies'])
			? false : (bool) $instance['exclude_replies'];
		# exclude reposts?
		$exclude_reposts = empty($instance['exclude_reposts'])
			? false : (bool) $instance['exclude_reposts'];
		# expiration
		$expiration = empty($instance['post_count'])
			? 3600 : (int) $instance['expiration'];
		# pull from cache
		$mydata = get_transient($instance['cache_id']);
		$title = "<a href='{$mention_url}{$screen_name}' target='_blank' title='Follow {$screen_name}'>$title</a>";
		
		# we have a cached copy of the data; serve that instead
		if($mydata !== false)
		{
			echo $before_widget
				. $before_title
				. $title
				. $after_title
				. $mydata
				. $after_widget;
			return;
		}
		
		# pull feed XML
		$xml_url = "{$api_url}statuses/user_timeline.xml?screen_name={$screen_name}";
		$xml = false;

		# use either curl or fopen
		if(function_exists('curl_init'))
		{
			$ch = curl_init($xml_url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$result = curl_exec($ch);
			curl_close($ch);
			$xml = simplexml_load_string($result);
		}
		else
			$xml = simplexml_load_file($xml_url);

		$widget_content = '<ul>';
		$count = 0;
		
		# pull posts from feed
		foreach($xml as $status)
		{
			$ns_statusnet = $status->children("http://status.net/schema/api/1/");
			$text = '';
			if($ns_statusnet)
				$text = $ns_statusnet->html;
			else
				$text = $status->text;

			# exclude reposts
			if($exclude_reposts && preg_match('#^rt\s#i', $text))
				continue;

			if(! $ns_statusnet)
			{
				# exclude replies
				if($exclude_replies && preg_match('#^@\w+#', $text))
					continue;
				$text = $this->twitter_links($instance, $text);
			}
			# special handling for statusnet-schema response
			else
			{
				# exclude replies
				if($exclude_replies && $ns_statusnet->in_reply_to_screen_name)
					continue;
			}

			$stamp = date('D M j @ g:i A', strtotime($status->created_at)
				+ $offset * 60);
			$widget_content .= "<li>{$text} <span class='whats-my-status_stamp'>{$stamp}</span></li>";
			if(++$count >= $post_count)
				break;
		}
		
		$widget_content .= "</ul>";
		# add to cache
		set_transient($cache_id, $widget_content, $expiration);
		# render widget
		echo $before_widget
			. $before_title
			. $title
			. $after_title
			. $widget_content
			. $after_widget;
	}
	
	# update configuration values ==============================================
	function update($new_instance, $old_instance)
	{
		if($_POST['reset'] == 1)
		{
			delete_transient($old_instance['cache_id']);
			return $old_instance;
		}

		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['screen_name'] = strip_tags($new_instance['screen_name']);
		$instance['gmt_offset'] = (int) $new_instance['gmt_offset'];
		$instance['expiration'] = max(1, (int) $new_instance['expiration']);
		$instance['post_count'] = max(1, (int) $new_instance['post_count']);
		$instance['service'] = $new_instance['service'];
		$instance['exclude_replies'] = (bool) $new_instance['exclude_replies'];
		$instance['exclude_reposts'] = (bool) $new_instance['exclude_reposts'];
		$instance['api_url'] = $new_instance['api_url'];
		if(! preg_match('#/$#', $instance['api_url']))
			$instance['api_url'] .= '/';
		$instance['hashtag_url'] = $new_instance['hashtag_url'];
		$instance['mention_url'] = $new_instance['mention_url'];
		$instance['cache_id'] = $new_instance['cache_id'];
		return $instance;
	}
	
	# configuration form =======================================================
	function form($instance)
	{
		# default values
		$title = attribute_escape($instance['title']);
		$screen_name = attribute_escape($instance['screen_name']);
		$offset = (int) $instance['gmt_offset'];
		$expiration = (int) $instance['expiration'];
		if($expiration == 0)
			$expiration = 1;
		$post_count = empty($instance['post_count'])
			? 5 : (int) $instance['post_count'];
		$exclude_replies = empty($instance['exclude_replies'])
			? false : (bool) $instance['exclude_replies'];
		$exclude_reposts = empty($instance['exclude_reposts'])
			? false : (bool) $instance['exclude_reposts'];
		if(preg_match('#/$#', $service_url) <= 0)
			$service_url .= '/';
		$api_url = empty($instance['api_url'])
			? 'https://api.twitter.com/1/' : $instance['api_url'];
		$hashtag_url = empty($instance['hashtag_url'])
			? 'http://twitter.com/search?q=%23' : $instance['hashtag_url'];
		$mention_url = empty($instance['mention_url'])
			? 'http://twitter.com/' : $instance['mention_url'];
		$service = empty($instance['service'])
			? 'twitter' : $instance['service'];
		$mytitleid = $this->get_field_id('title');
		$cache_id = empty($instance['cache_id'])
			? uniqid() : $instance['cache_id'];
		?>
			<p>
				<label for="<?php echo $mytitleid?>"><?php _e('Title:')?></label>
				<input class="widefat" id="<?php echo $mytitleid?>" name="<?php echo $this->get_field_name('title')?>" type="text" value="<?php echo $title?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('screen_name')?>"><?php _e('Screen name:')?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('screen_name')?>" name="<?php echo $this->get_field_name('screen_name')?>" type="text" value="<?php echo $screen_name?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('gmt_offset')?>"><?php _e('GMT offset (in minutes):')?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('gmt_offset')?>" name="<?php echo $this->get_field_name('gmt_offset')?>" type="text" value="<?php echo $offset?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('post_count')?>"><?php _e('Post count:')?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('post_count')?>" name="<?php echo $this->get_field_name('post_count')?>" type="text" value="<?php echo $post_count?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('expiration')?>"><?php _e('Cache timer (in seconds):')?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('expiration')?>" name="<?php echo $this->get_field_name('expiration')?>" type="text" value="<?php echo $expiration?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('service')?>"><?php _e('Service:')?></label>
				<select class="widefat" id="<?php echo $this->get_field_id('service')?>" name="<?php echo $this->get_field_name('service')?>">
					<option value="twitter"<?php if($service == 'twitter'): ?> selected="selected"<?php endif; ?>>Twitter (default)</option>
					<option value="identi.ca"<?php if($service == 'identi.ca'): ?> selected="selected"<?php endif; ?>>identi.ca</option>
					<option value="custom"<?php if($service == 'custom'): ?> selected="selected"<?php endif; ?>>[Custom]</option>
				</select>
			</p>
			<div id="<?php echo $mytitleid?>_options" style="display:none">
				<p>
					<label for="<?php echo $this->get_field_id('api_url')?>"><?php _e('API URL (with trailing slash):')?></label>
					<input class="widefat" id="<?php echo $this->get_field_id('api_url')?>" name="<?php echo $this->get_field_name('api_url')?>" type="text" value="<?php echo $api_url?>" />
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('hashtag_url')?>"><?php _e('Hashtag URL:')?></label>
					<input class="widefat" id="<?php echo $this->get_field_id('hashtag_url')?>" name="<?php echo $this->get_field_name('hashtag_url')?>" type="text" value="<?php echo $hashtag_url?>" />
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('mention_url')?>"><?php _e('Mention URL:')?></label>
					<input class="widefat" id="<?php echo $this->get_field_id('mention_url')?>" name="<?php echo $this->get_field_name('mention_url')?>" type="text" value="<?php echo $mention_url?>" />
				</p>
			</div>
			<p>
				<input id="<?php echo $this->get_field_id('exclude_replies')?>" name="<?php echo $this->get_field_name('exclude_replies')?>" type="checkbox" <?php if($exclude_replies): ?>checked="checked" <?php endif; ?>/>
				<label for="<?php echo $this->get_field_id('exclude_replies')?>"><?php _e('Exclude replies')?></label>
			</p>
			<p>
				<input id="<?php echo $this->get_field_id('exclude_reposts')?>" name="<?php echo $this->get_field_name('exclude_reposts')?>" type="checkbox" <?php if($exclude_reposts): ?>checked="checked" <?php endif; ?>/>
				<label for="<?php echo $this->get_field_id('exclude_reposts')?>"><?php _e('Exclude reposts')?></label>
			</p>
			<p><small><a href="#" id="<?php echo $mytitleid?>_resetbutton">Reset cache</a></small></p>
			<input type="hidden" id="<?php echo $this->get_field_id('cache_id')?>" name="<?$this->get_field_name('cache_id')?>" value="<?php echo $cache_id?>" />
			<input type="hidden" id="<?php echo $mytitleid?>_reset" value="0" name="reset" />
			<script type="text/javascript">
			(function() {
				var whatsmystatus_reset = document.getElementById('<?php echo $mytitleid?>_reset');
				document.getElementById('<?php echo $mytitleid?>_resetbutton').onclick = function()
				{
					whatsmystatus_reset.value = 1;
					document.getElementById('<?php echo preg_replace('#-title$#', '', $mytitleid)?>-savewidget').click();
					whatsmystatus_reset.value = 0;
				}

				var whatsmystatus_service =
					document.getElementById('<?php echo $this->get_field_id('service')?>');
				var whatsmystatus_options =
					document.getElementById('<?php echo $mytitleid?>_options');
				var whatsmystatus_api_url =
					document.getElementById('<?php echo $this->get_field_id('api_url')?>');
				var whatsmystatus_hashtag_url =
					document.getElementById('<?php echo $this->get_field_id('hashtag_url')?>');
				var whatsmystatus_mention_url =
					document.getElementById('<?php echo $this->get_field_id('mention_url')?>');
				if(whatsmystatus_service.value == 'custom')
					whatsmystatus_options.style.display = 'block';
				
				whatsmystatus_service.onchange = function()
				{
					switch(this.value)
					{
						case 'twitter':
							whatsmystatus_options.style.display = 'none';
							whatsmystatus_api_url.value = 'https://api.twitter.com/1/';
							whatsmystatus_hashtag_url.value = 'http://twitter.com/search?q=%23';
							whatsmystatus_mention_url.value = 'http://twitter.com/';
							break;
						case 'identi.ca':
							whatsmystatus_options.style.display = 'none';
							whatsmystatus_api_url.value = 'http://identi.ca/api/';
							whatsmystatus_hashtag_url.value = 'Not applicable';
							whatsmystatus_mention_url.value = 'http://identi.ca/';
							break;
						case 'custom':
							whatsmystatus_options.style.display = 'block';
							whatsmystatus_api_url.value = '';
							whatsmystatus_hashtag_url.value = '';
							whatsmystatus_mention_url.value = '';
							break;
					}
				};
			})();
			</script>
		<?php
	}
	
	# convert @mentions, #hashtags, and URLs (w/ or w/o protocol) into links
	private function twitter_links($instance, $text)
	{
		# convert URLs into links
		$text = preg_replace(
			"#(https?://([-a-z0-9]+\.)+[a-z]{2,5}([/?][-a-z0-9!\#()/?&+]*)?)#i", "<a href='$1' target='_blank'>$1</a>",
			$text);
		# convert protocol-less URLs into links
		$text = preg_replace(
			"#(?!https?://|<a[^>]+>)(^|\s)(([-a-z0-9]+\.)+[a-z]{2,5}([/?][-a-z0-9!\#()/?&+.]*)?)\b#i", "$1<a href='http://$2'>$2</a>",
			$text);
		# convert @mentions into follow links
		$text = preg_replace(
			"#(?!https?://|<a[^>]+>)(^|\s)(@([_a-z0-9\-]+))#i", "$1<a href=\"{$instance['mention_url']}$3\" title=\"Follow $3\" target=\"_blank\">@$3</a>",
			$text);
		# convert #hashtags into tag search links
		$text = preg_replace(
			"#(?!https?://|<a[^>]+>)(^|\s)(\#([_a-z0-9\-]+))#i", "$1<a href='{$instance['hashtag_url']}$3' title='Search tag: $3' target='_blank'>#$3</a>",
			$text);	
		return $text;
	}
}

# register widget ==============================================================
function WhatsMyStatus_register()
{
	register_widget('WP_Widget_WhatsMyStatus');
}

# hook for stylesheets =========================================================
function WhatsMyStatus_styles()
{
	wp_register_style('whatsmystatus-css',
		WHATSMYSTATUS_PLUGIN_URL . '/styles.css');
	wp_enqueue_style('whatsmystatus-css');
}

# action links - donate link, settings link, etc. ==============================
function WhatsMyStatus_plugin_links($links, $file)
{
	static $this_plugin;
	if(! $this_plugin)
		$this_plugin = plugin_basename(__FILE__);

	if($file == $this_plugin)
	{
		$donate_link = <<<HTML
			<a href="#" onclick="whatsmystatus_donate();return false;">Donate</a>
			<script type="text/javascript">
			function whatsmystatus_donate()
			{
				var form = document.createElement('form');
				form.action = "https://www.paypal.com/cgi-bin/webscr";
				form.method = "POST";
				form.target = "_blank";
				form.innerHTML = '<input type="hidden" name="cmd" value="_s-xclick" /> \
					<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----" /> \
					<img alt="" border="0" src="https://www.paypalobjects.com/WEBSCR-640-20110306-1/en_US/i/scr/pixel.gif" width="1" height="1" />';
				document.body.appendChild(form);
				form.submit();
			}
			</script>
HTML;
		array_push($links, $donate_link);
	}

	return $links;
}

# activate =====================================================================
function WhatsMyStatus_activate()
{
	$old = get_option(WHATSMYSTATUS_VERSION_KEY);
	
	if($old != WHATSMYSTATUS_VERSION_NUM)
	{
		// do update stuff here if necessary
	}

	add_option(WHATSMYSTATUS_VERSION_KEY, WHATSMYSTATUS_VERSION_NUM);
}

# deactivate ===================================================================
function WhatsMyStatus_deactivate()
{
	delete_option(WHATSMYSTATUS_VERSION_KEY);
}

# hooks - only fire if WordPress is loaded =====================================
if(defined('ABSPATH') && defined('WPINC'))
{
	if (!defined('WHATSMYSTATUS_PLUGIN_NAME'))
    	define('WHATSMYSTATUS_PLUGIN_NAME', trim(dirname(plugin_basename(__FILE__)), '/'));	
	if (!defined('WHATSMYSTATUS_PLUGIN_DIR'))
    	define('WHATSMYSTATUS_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . WHATSMYSTATUS_PLUGIN_NAME);	
	if (!defined('WHATSMYSTATUS_PLUGIN_URL'))
    	define('WHATSMYSTATUS_PLUGIN_URL', WP_PLUGIN_URL . '/' . WHATSMYSTATUS_PLUGIN_NAME);	
	if (!defined('WHATSMYSTATUS_VERSION_KEY'))
    	define('WHATSMYSTATUS_VERSION_KEY', 'whatsmystatus_version');	
	if (!defined('WHATSMYSTATUS_VERSION_NUM'))
    	define('WHATSMYSTATUS_VERSION_NUM', '1.2');	
	register_activation_hook(__FILE__, 'WhatsMyStatus_activate');
	register_deactivation_hook(__FILE__, 'WhatsMyStatus_deactivate');
	add_action('widgets_init', 'WhatsMyStatus_register');
	add_action('wp_print_styles', 'WhatsMyStatus_styles');
	add_filter('plugin_row_meta', 'WhatsMyStatus_plugin_links', 10, 2);
}
