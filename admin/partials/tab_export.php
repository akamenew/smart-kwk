<div class="metabox-holder">
    <div class="postbox">
        <h3><?php echo esc_html(__('Export', 'smart-kwk')) ?></h3>

        <div class="inside">
            <p><?php echo esc_html(__('Only referals with assigned affiliates and a positive provision will be exported.', 'smart-kwk')) ?></p>
            <form action="<?php echo admin_url('admin-post.php') ?>" method="post">
                <?php wp_nonce_field('exportkwk'); ?>
                <input type="hidden" name="action" value="exportkwk">
                <input class="button button-large" type="submit" value="<?php echo esc_attr(__('Export', 'smart-kwk')) ?>" name="smart_exportkwk"/>
            </form>
        </div>
    </div>
</div>