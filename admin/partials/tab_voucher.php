<?php
global $wpdb;

echo '<div class="metabox-holder">';
echo '<div class="postbox">';
echo "<h3>" . esc_html(__('Voucher management', 'smart-kwk')) . "</h3>";
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

<?php
echo '<div class="inside">';

$sql = "SELECT * FROM {$wpdb->prefix}smart_kwk_vouchers WHERE 1 ORDER BY date_sent DESC, date_inserted DESC";

$vouchers = $wpdb->get_results($sql, ARRAY_A);

if ($vouchers) {

    //count unused
    $unused = 0;
    foreach ($vouchers as $v) {
        if (!$v['date_sent']) {
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

        if ($ref['affiliate_id']) {
            $recipient = $wpdb->get_var($wpdb->prepare("SELECT u.user_email FROM {$wpdb->prefix}affiliate_wp_affiliates a LEFT JOIN {$wpdb->prefix}users u ON a.user_id=u.ID WHERE a.affiliate_id = %d", (int) $ref['affiliate_id']));

            if (!$recipient) {
                $recipient = sprintf(__('Affiliate ID %s unknown'), $ref['affiliate_id']);
            }
            $editable = "";
        } else {
            $recipient = '';
            $editable = "stkwk_editable";
        }

        echo '<tr>';
        echo '<td class="' . $editable . '" vid="' . $ref['id'] . '">' . $ref['voucher_code'] . '</td><td>' . $ref['date_inserted'] . '</td><td>' . $ref['date_sent'] . '</td><td>' . esc_html($recipient) . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
} else {
    echo '<p><strong>' . esc_html(__('No vouchers found', 'smart-kwk')) . '</strong></p>';
}
echo '</div>';

echo '</div>';
echo '</div>';
