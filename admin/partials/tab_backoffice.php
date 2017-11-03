<?php
global $wpdb;

echo '<div class="metabox-holder">';
echo '<div class="postbox">';
echo "<h3>" . esc_html(__('Backoffice', 'smart-kwk')) . "</h3>";
if (isset($_GET['updated'])) {
    ?>
    <div id="message" class="updated notice notice-success is-dismissible">
        <p>
            <?php echo esc_html(sprintf(__('Updated entries %s', 'smart-kwk'), $_GET['updated'])) ?>
        </p>
        <button class="notice-dismiss" type="button">
            <span class="screen-reader-text"><?php echo esc_html(__('Dismiss this notification', 'smart-kwk')) ?></span>
        </button>
    </div>
    <?php
}

echo '<div class="inside">';
echo '<p>' . esc_html(__('Open premium:', 'smart-kwk')) . '</p>';

$status = 'unpaid';

$sql = "SELECT r.*, k.api_response, k.paid FROM {$wpdb->prefix}affiliate_wp_referrals r LEFT JOIN {$wpdb->prefix}smart_kwk k ON r.referral_id=k.referral_id WHERE r.status='%s'";

$referrals = $wpdb->get_results($wpdb->prepare($sql, $status), ARRAY_A);

if ($referrals) {
    echo '<table class="wp-list-table widefat fixed striped posts">';
    echo '<thead>';
    echo '<tr>';
    echo '<td>' . esc_html(__('E-Mail', 'smart-kwk')) . '</td>'
    . '<td>' . esc_html(__('Order date', 'smart-kwk')) . '</td>'
    . '<td>' . esc_html(__('K-Nummer', 'smart-kwk')) . '</td>'
    . '<td>' . esc_html(__('Expercash', 'smart-kwk')) . '</td>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    foreach ($referrals as $ref) {

        $paid = $ref['paid'] == 1 ? __('Paid', 'smart-kwk') : '';

        echo '<tr>';
        if ($ref['api_response']) {
            echo '<td>' . $ref['description'] . '</td><td>' . date('Y-m-d H:i:s', strtotime($ref['date'])) . '</td><td>' . $ref['api_response'] . '</td><td>' . esc_html($paid) . '</td>';
        } else {
            echo '<td>' . $ref['description'] . '</td><td>' . $ref['date'] . '</td><td><input type="hidden" name="rid[]" value="' . $ref['referral_id'] . '"/>' . $ref['api_response'] . '</td><td>' . esc_html($paid) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
} else {
    echo '<p><strong>' . esc_html(__('No referals found', 'smart-kwk')) . '</strong></p>';
}
echo '</div>';
?>
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
<?php
echo '</div>';
echo '</div>';
