<div class="metabox-holder">
    <div class="postbox">
        <h3><?php echo esc_html(__('Import', 'smart-kwk')) ?></h3>
        <?php if (isset($_GET['updated'])): ?>
            <div id="message" class="updated notice notice-success is-dismissible">
                <p>
                    <?php echo esc_html(sprintf(__('Entries updated: %s', 'smart-kwk'), $_GET['updated'])) ?>
                </p>
                <button class="notice-dismiss" type="button">
                    <span class="screen-reader-text"><?php echo esc_html(__('Dismiss this notification', 'smart-kwk')) ?></span>
                </button>
            </div>
        <?php endif; ?>
        <div class="inside">
            <p><?php echo esc_html(__('Columns same as in exportfile<br/>Column 4: Email of referal<br>Column 7: Status (paid, unpaid, rejected, pending)', 'smart-kwk')) ?></p>
            <form action="<?php echo admin_url('admin-post.php') ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="importkwk">
                <?php wp_nonce_field('importkwk'); ?>
                <input type="file" name="importkwkfile" accept="text/*"/>
                <input class="button button-large" type="submit" value="<?php echo esc_attr(__('Import', 'smart-kwk')) ?>" name="smart_importkwk"/>
            </form>
        </div>
    </div>
</div>