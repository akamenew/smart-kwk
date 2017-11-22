<div class="metabox-holder">
    <div class="postbox">
        <h3><?php echo esc_html(__('Voucher management', 'smart-kwk')) ?></h3>
        <?php
        if (isset($_GET['updated'])) {
            ?>
            <div id="message" class="updated notice notice-success is-dismissible">
                <p>
                    <?php echo esc_html(sprintf(__('Vouchers imported: %s', 'smart-kwk'), $_GET['updated'])) ?>
                </p>
                <button class="notice-dismiss" type="button">
                    <span class="screen-reader-text"><?php echo esc_html(__('Dismis this notification', 'smart-kwk')) ?></span>
                </button>
            </div>
            <?php
        }
        ?>
        <div class="inside">
            <form action="<?php echo admin_url('admin-post.php') ?>" method="post">
                <input type="text" name="code" placeholder="<?php echo esc_attr(__('Voucher code', 'smart-kwk')) ?>"/>
                <?php wp_nonce_field('addvoucher'); ?>
                <input type="hidden" name="action" value="addvoucher"/>
                <input class="button button-large" type="submit" value="<?php echo esc_attr(__('Add', 'smart-kwk')) ?>" name="smart_addvoucher"/>
            </form>
        </div>

        <div class="inside">
            <form action="<?php echo admin_url('admin-post.php') ?>" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('importvouchers'); ?>
                <input type="hidden" name="action" value="importvouchers">
                <input id="import_vouchers" class="button button-large" type="submit" value="<?php echo esc_attr(__('Import vouchers', 'smart-kwk')) ?>" name="smart_importvouchers"/>
                <input id="voucher_file" type="file" name="voucherfile" accept="text/*"/>
            </form>
        </div>
        <div class="inside">
            <?php
            $vouchers = $this->get_all_vouchers();

            if ($vouchers) {

                //count unused
                $unused = 0;
                foreach ($vouchers as $v) {
                    if (!$v->get_date_sent()) {
                        $unused++;
                    }
                }

                echo '<p>' . esc_html(sprintf('%s of %s vouchers unused', $unused, count($vouchers))) . '</p>';
                echo '<table id="vouchertable" class="wp-list-table widefat fixed striped posts dataTable">';
                echo '<thead>';
                echo '<tr>';
                echo '<th>' . esc_html(__('Voucher', 'smart-kwk')) . '</th>'
                . '<th>' . esc_html(__('Created on', 'smart-kwk')) . '</th>'
                . '<th>' . esc_html(__('Sent on', 'smart-kwk')) . '</th>'
                . '<th>' . esc_html(__('Affiliate', 'smart-kwk')) . '</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                foreach ($vouchers as $ref) {

                    if ($ref->get_affiliate_id()) {
                        $recipient = $ref->get_affiliate_email();

                        if (!$recipient) {
                            $recipient = sprintf(__('Affiliate ID %s unknown'), $ref->get_affiliate_id());
                        }
                        $editable = "";
                    } else {
                        $recipient = '';
                        $editable = "stkwk_editable";
                    }

                    echo '<tr>';
                    echo '<td class="' . $editable . '" vid="' . $ref->get_voucher_id() . '">' . $ref->get_voucher_code() . '</td><td>' . $ref->get_date_inserted() . '</td><td>' . $ref->get_date_sent() . '</td><td>' . esc_html($recipient) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody>';
                echo '</table>';
            } else {
                echo '<p><strong>' . esc_html(__('No vouchers found', 'smart-kwk')) . '</strong></p>';
            }
            ?>
        </div>
    </div>
</div>
