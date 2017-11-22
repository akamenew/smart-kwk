<div class="metabox-holder">
    <div class="postbox">
        <h3><?php echo esc_html(__('Expercash', 'smart-kwk')) ?></h3>
        <?php
        if (isset($_GET['formerror']) && $_GET['formerror'] === '1') {
            ?>
            <div id="message" class="error notice notice-error is-dismissible">
                <p>
                    <?php echo esc_html(__('Please enter username and password.', 'smart-kwk')) ?>
                </p>
                <button class="notice-dismiss" type="button">
                    <span class="screen-reader-text"><?php echo esc_html(__('Dismiss this notification', 'smart-kwk')) ?></span>
                </button>
            </div>
            <?php
        }
        if (isset($_GET['rubyerror']) && $_GET['rubyerror'] === '1') {
            ?>
            <div id="message" class="error notice notice-error is-dismissible">
                <p>
                    <?php echo esc_html(__('The transaction file could not be created.', 'smart-kwk')) ?>
                </p>
                <button class="notice-dismiss" type="button">
                    <span class="screen-reader-text"><?php echo esc_html(__('Dismiss this notification', 'smart-kwk')) ?></span>
                </button>
            </div>
            <?php
        }
        if (isset($_GET['updated'])) {
            ?>
            <div id="message" class="updated notice notice-success is-dismissible">
                <p>
                    <?php echo esc_html(sprintf(__('Entries marked as paid: %s', 'smart-kwk'), $_GET['updated'])) ?>
                </p>
                <button class="notice-dismiss" type="button">
                    <span class="screen-reader-text"><?php echo esc_html(__('Dismiss this notification', 'smart-kwk')) ?></span>
                </button>
            </div>
            <?php
        }
        ?>
        <div class="inside">
            <p><?php echo esc_html(__('Downloads transactions and set status to "paid"', 'smart-kwk')) ?></p>
            <form action="<?php echo admin_url('admin-post.php') ?>" method="post">
                <input type="text" name="u" placeholder="<?php echo esc_attr(__('Username', 'smart-kwk')) ?>"/>
                <input type="password" name="p" placeholder="<?php echo esc_attr(__('Password', 'smart-kwk')) ?>"/>
                <?php wp_nonce_field('getimport'); ?>
                <input type="hidden" name="action" value="getimport"/>
                <input class="button button-large" type="submit" value="<?php echo esc_attr(__('Request', 'smart-kwk')) ?>" name="smart_getimport"/>
            </form>
        </div>
    </div>
</div>