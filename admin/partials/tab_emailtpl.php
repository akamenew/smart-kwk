<?php
echo '<div class="metabox-holder">';
echo '<div class="postbox">';
echo "<h3>" . esc_html(__('E-Mail Template', 'smart-kwk')) . "</h3>";

$targetfile = $this->get_mail_template();

if (file_exists($targetfile)) {
    $content = file_get_contents($targetfile);
} else {
    $content = '';
}

if (isset($_GET['updated'])) {
    ?>
    <div id="message" class="updated notice notice-success is-dismissible">
        <p>
            <?php echo esc_html(__('E-Mail template updated!', 'smart-kwk')) ?>
        </p>
        <button class="notice-dismiss" type="button">
            <span class="screen-reader-text"><?php echo esc_html(__('Dismiss this notification', 'smart-kwk')) ?></span>
        </button>
    </div>
    <?php
}
?>
<div class="inside">
    <?php
    if (file_exists($targetfile)) {
        echo '<p><a target="_blank" href="' . esc_url(SMARTKWK_PLUGIN_URL . '/admin/templates/' . basename($targetfile)) . '">' . esc_html(__('Preview', 'smart-kwk')) . '</a></p>';
    }
    ?>
    <p> <?php echo esc_html(__('Supported placeholder:', 'smart-kwk')) ?> <pre><strong><?php echo esc_html(PLACEHOLDER_VOUCHER) ?></strong></pre></p>
<p>
<form action="<?php echo admin_url('admin-post.php') ?>" method="post">
    <div class="input_left_emailtpl"><textarea name="content"><?php echo $content ?></textarea></div>
    <div class="emailtpl_iframe_wrap">
        <iframe src="<?php echo esc_url(SMARTKWK_PLUGIN_URL . '/admin/templates/' . basename($targetfile)) ?>"
                scrolling="yes" marginwidth="0" marginheight="0" frameborder="0" vspace="0" hspace="0">
        </iframe>
    </div>
    <div class="clear"></div>
    <?php wp_nonce_field('save_emailtemplate'); ?>
    <input type="hidden" name="action" value="save_emailtemplate"/>
    <p><input class="button button-large" type="submit" value="<?php echo esc_attr(__('Save', 'smart-kwk')) ?>" name="smart_save_emailtemplate"/></p>
</form>
</p>
</div>
<?php
echo '</div>';
echo '</div>';

