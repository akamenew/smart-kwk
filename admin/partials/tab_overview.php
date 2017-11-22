<div class="metabox-holder">
    <div class="postbox">
        <h3><?php echo esc_html(__('Overview', 'smart-kwk')) ?></h3>
        <div class="inside">
            <form action="<?php echo admin_url('admin-post.php') ?>" method="post">
                <input type="hidden" name="action" value="export_overview">
                <?php wp_nonce_field('export_overview'); ?>
                <input class="button button-large" type="submit" value="<?php echo esc_attr(__('Export Table', 'smart-kwk')) ?>" name="smart_export_overview"/>
            </form>
        </div>
        <div class="inside">
            <?php
            $pagenum = isset($_GET['pagenum']) ? absint($_GET['pagenum']) : 1;

            $total = $this->get_referrals_count();

            $num_of_pages = $total > 0 ? ceil($total / MAX_OVERVIEW_ROWS) : 1;

            $data = $this->get_referrals($pagenum);

            $page_links = paginate_links(array(
                'base' => add_query_arg('pagenum', '%#%'),
                'format' => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total' => $num_of_pages,
                'current' => $pagenum
            ));

            if ($data) {
                echo '<table id="overviewTable" class="wp-list-table widefat fixed striped posts dataTable">';
                echo '<thead>';
                echo '<tr>';
                echo '<th>' . esc_html(__('Affiliate', 'smart-kwk')) . '</th>'
                . '<th>' . esc_html(__('Referal', 'smart-kwk')) . '</th>'
                . '<th>' . esc_html(__('Order date', 'smart-kwk')) . '</th>'
                . '<th>' . esc_html(__('Status', 'smart-kwk')) . '</th>'
                . '<th>' . esc_html(__('Voucher', 'smart-kwk')) . '</th>'
                . '<th>' . esc_html(__('Change status', 'smart-kwk')) . '</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                foreach ($data as $ref) {

                    if ($ref['date_sent'] === NULL && (int) $ref['paid'] === 1) {
                        $button = '<button class="sendVoucherButton button button-large" rid="' . $ref['referral_id'] . '">' . __('Send voucher', 'smart-kwk') . '</button>';
                    } elseif ($ref['date_sent']) {
                        $button = __('sent on', 'smart-kwk') . ' ' . $ref['date_sent'];
                    } else {
                        $button = '';
                    }

                    if (!$ref['date_sent'] && $ref['api_status'] == 'denied') {
                        $statusButton = '<button class="button button-primary changeStatusButton" rid="' . $ref['referral_id'] . '" newstatus="accepted">' . __('Approve', 'smart-kwk') . '</buton>';
                    } elseif (!$ref['date_sent'] && $ref['api_status'] == 'accepted') {
                        $statusButton = '<button class="button button-secondary changeStatusButton" rid="' . $ref['referral_id'] . '" newstatus="denied">' . __('Deny', 'smart-kwk') . '</buton>';
                    } else {
                        $statusButton = '';
                    }

                    echo '<tr>';
                    echo '<td>' . $ref['user_email'] . '</td><td>' . $ref['description'] . '</td><td>' . $ref['order_date'] . '</td><td class="status_col">' . $ref['api_response'] . '</td><td class="voucher_col">' . $button . '</td>';
                    echo '<td class="newStatusCol">' . $statusButton . '</td>';
                    echo '</tr>';
                }
                echo '</tbody>';
                echo '</table>';

                if ($page_links) {
                    $page_links = '<span class="displaying-num">' . sprintf(_n('%s item', '%s items', $total), number_format_i18n($total)) . '</span>' . $page_links;
                    echo '<div class="tablenav"><div class="tablenav-pages">' . $page_links . '</div></div>';
                }

                echo '<p><button id="bulk_send_btn" class="button button-large">' . esc_html(__('Send to all', 'smart-kwk')) . '</button></p>';
            } else {
                echo '<p><strong>' . esc_html(__('Nothing found', 'smart-kwk')) . '</strong></p>';
            }
            ?>
        </div>
    </div>
</div>
