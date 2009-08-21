<?php global $wptouch_settings; ?>
<script type="text/javascript">
	setTimeout(function() { 	
		jQuery.ajax({
			url: "<?php get_bloginfo('siteurl'); ?>/?wptouch=news",
			success: function(data) {$j("#wptouch-news-content").html(data).fadeIn(350);}
		});
	}, 750);

	setTimeout(function() { 	
		jQuery.ajax({
			url: "<?php get_bloginfo('siteurl'); ?>/?wptouch=beta",
			success: function(data) {$j("#wptouch-beta-content").html(data).fadeIn(350);}
		});
	}, 1250);
</script>
<div class="metabox-holder" id="wptouch-head">
	<div class="postbox">
		<div id="wptouch-head-colour">
			<div id="wptouch-head-title">
				<?php WPtouch(); ?>
				<img class="ajax-load" src="<?php echo compat_get_plugin_url('wptouch'); ?>/images/admin-ajax-loader.gif" alt="ajax"/>
			</div>
				<div id="wptouch-head-links">
					<ul>
						<li><?php echo sprintf(__( "%sSupport Forums%s", "wptouch" ), '<a href="http://support.bravenewcode.com" target="_blank">','</a>'); ?> | </li>
						<li><?php echo sprintf(__( "%sWPtouch Homepage%s", "wptouch" ), '<a href="http://www.bravenewcode.com/wptouch" target="_blank">','</a>'); ?> | </li>
						<li><?php echo sprintf(__( "%sDonate%s", "wptouch" ), '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=paypal%40bravenewcode%2ecom&amp;item_name=WPtouch%20Beer%20Fund&amp;no_shipping=1&amp;tax=0&amp;currency_code=CAD&amp;lc=CA&amp;bn=PP%2dDonationsBF&amp;charset=UTF%2d8" target="_blank">','</a>'); ?></li>
					</ul>
				</div>
	<div class="wptouch-clearer"></div>
			</div>	
	
		<div id="wptouch-news-beta">
			<div id="wptouch-news-wrap">
			<h3>WPtouch Wire</h3>
				<div id="wptouch-news-content" style="display:none"></div>
			</div>
			<div id="wptouch-beta-wrap">		
			<h3>Downloads</h3>
				<div id="wptouch-beta-content" style="display:none"></div>
			</div>
			
		</div><!-- wptouch-news-beta -->

	<div class="wptouch-clearer"></div>
	</div><!-- postbox -->
</div><!-- wptouch-head -->