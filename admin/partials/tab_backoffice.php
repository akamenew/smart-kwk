<?php
$referrals = $this->get_unpaid_referrals();
?>
<div class="metabox-holder">
    <div class="postbox">
        <h3><?php echo esc_html(__('Backoffice', 'smart-kwk')) ?></h3>
        <?php if (isset($_GET['updated'])): ?>
            <div id="message" class="updated notice notice-success is-dismissible">
                <p>
                    <?php echo esc_html(sprintf(__('Updated entries %s', 'smart-kwk'), $_GET['updated'])) ?>
                </p>
                <button class="notice-dismiss" type="button">
                    <span class="screen-reader-text"><?php echo esc_html(__('Dismiss this notification', 'smart-kwk')) ?></span>
                </button>
            </div>
        <?php endif; ?>

        <div class="inside">
            <p><?php echo esc_html(__('Open premium:', 'smart-kwk')) ?></p>

            <?php if ($referrals): ?>
                <table class="wp-list-table widefat fixed striped posts">
                    <thead>
                        <tr>
                            <td><?php echo esc_html(__('E-Mail', 'smart-kwk')) ?></td>
                            <td><?php echo esc_html(__('Order date', 'smart-kwk')) ?></td>
                            <td><?php echo esc_html(__('K-Nummer', 'smart-kwk')) ?></td>
                            <td><?php echo esc_html(__('Expercash', 'smart-kwk')) ?></td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($referrals as $ref):
                            $paid = $ref['paid'] == 1 ? __('Paid', 'smart-kwk') : '';
                            ?>
                            <tr>
                                <?php if ($ref['api_response']): ?>
                                    <td><?php echo $ref['description'] ?></td>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($ref['date'])) ?></td>
                                    <td><?php echo $ref['api_response'] ?></td>
                                    <td><?php echo esc_html($paid) ?></td>
                                <?php else: ?>
                                    <td><?php echo $ref['description'] ?></td>
                                    <td><?php echo $ref['date'] ?></td>
                                    <td><input type="hidden" name="rid[]" value="<?php echo esc_attr($ref['referral_id']) ?>"/>
                                        <?php echo $ref['api_response'] ?></td>
                                    <td><?php echo esc_html($paid) ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><strong><?php echo esc_html(__('No referals found', 'smart-kwk')) ?></strong></p>
            <?php endif; ?>
        </div>
        <div class="inside">
            <form action="<?php echo admin_url('admin-post.php') ?>" method="post">
                <input type="hidden" name="action" value="callbackoffice">
                <input id="backofficeFormSubmit" class="button button-large" type="submit" value="<?php echo esc_attr(__('Send backoffice request', 'smart-kwk')) ?>" name="smart_callbackoffice"/>
            </form>
        </div>
        <div class="inside">
            <form action="<?php echo admin_url('admin-post.php') ?>" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('importexpercash'); ?>
                <input type="hidden" name="action" value="importexpercash">
                <input type="file" name="expercashfile" accept="text/*"/>
                <input class="button button-large" type="submit" value="<?php echo esc_attr(__('Import K-Nummern', 'smart-kwk')) ?>" name="smart_importexpercash"/>
            </form>
        </div>
    </div>
</div>