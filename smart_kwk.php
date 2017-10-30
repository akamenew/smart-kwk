<?php
/**
 * Plugin Name: Smartsteuer KwK
 * Version: 1.3
 * Plugin URI: https://smartsteuer.de
 * Description: Automatisierung Kunden werben Kunden Programm. Erfordert Plugin <a href="http://affiliatewp.com/">AffiliateWP</a>
 * Author: Artur Kamenew
 * Author URI: https://kamenew.com/
 * Text Domain: smart-kwk
 */
$logFile = stwk_get_logfile();

ini_set("log_errors", 1);
ini_set("error_log", $logFile);

if (!function_exists('add_filter')) {
    file_put_contents($logFile, "function 'add_filter' does not exist" . PHP_EOL, FILE_APPEND);
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

require_once 'config.php';

function stwk_get_logfile() {
    return dirname(__FILE__) . '/log.txt';
}

function stkwk_add_pages() {
    add_menu_page(__('Smart KwK', 'smart-kwk'), __('Smart KwK', 'smart-kwk'), 'manage_options', 'smartkwk', 'st_main_page');
}

function stkwk_get_tabs() {

    $tabs = array();
    $tabs['overview'] = __('Übersicht', 'smart-kwk');
    //$tabs['import'] = __('Import', 'smart-kwk');
    //$tabs['export'] = __('Export', 'smart-kwk');
    $tabs['backoffice'] = __('Backoffice', 'smart-kwk');
    $tabs['expercash'] = __('Expercash', 'smart-kwk');
    $tabs['voucher'] = __('Gutscheinverwaltung', 'smart-kwk');
    $tabs['emailtpl'] = __('E-Mail Template', 'smart-kwk');
    return $tabs;
}

function st_main_page() {
    $tabs = $active_tab = isset($_GET['tab']) && array_key_exists($_GET['tab'], stkwk_get_tabs()) ? $_GET['tab'] : DEFAULT_TAB;
    ?>
    <div class="wrap">
        <h2 class="nav-tab-wrapper">
            <?php
            foreach (stkwk_get_tabs() as $tab_id => $tab_name) {

                $tab_url = add_query_arg(array(
                    'tab' => $tab_id
                ));

                $active = $active_tab == $tab_id ? ' nav-tab-active' : '';

                echo '<a href="' . esc_url($tab_url) . '" title="' . esc_attr($tab_name) . '" class="nav-tab' . $active . '">';
                echo esc_html($tab_name);
                echo '</a>';
            }
            ?>
        </h2>
        <div id="tab_container">
            <?php do_action('stkwk_tab_' . $active_tab); ?>
        </div>
    </div>
    <?php
}

function stkwk_tab_overview() {
    global $wpdb;

    $pagenum = isset($_GET['pagenum']) ? absint($_GET['pagenum']) : 1;

    $limit = MAX_OVERVIEW_ROWS;
    $offset = ( $pagenum - 1 ) * $limit;

    echo '<div class="metabox-holder">';
    echo '<div class="postbox">';
    echo "<h3>" . __('Übersicht', 'smart-kwk') . "</h3>";
    ?>
    <div class="inside">
        <form action="<?= admin_url('admin-post.php') ?>" method="post">
            <input type="hidden" name="action" value="export_overview">
            <input class="button button-large" type="submit" value="<?= __('Tabelle exportieren', 'smart-kwk') ?>" name="smart_export_overview"/>
        </form>
    </div>
    <?php
    echo '<div class="inside">';

    $countSql = "SELECT count(r.description) "
            . " FROM {$wpdb->prefix}affiliate_wp_referrals r "
            . " LEFT JOIN {$wpdb->prefix}affiliate_wp_affiliates a ON r.affiliate_id=a.affiliate_id "
            . " LEFT JOIN {$wpdb->prefix}users u ON u.ID=a.user_id "
            . " LEFT JOIN {$wpdb->prefix}smart_kwk k ON k.referral_id=r.referral_id "
            . " LEFT JOIN {$wpdb->prefix}smart_kwk_vouchers v ON v.referral_id=r.referral_id "
            . " WHERE 1 ORDER BY k.date_inserted DESC";

    $sql = "SELECT u.user_email,r.description,k.order_date,k.api_status,k.api_response,k.paid,a.affiliate_id, r.referral_id, v.date_sent "
            . " FROM {$wpdb->prefix}affiliate_wp_referrals r "
            . " LEFT JOIN {$wpdb->prefix}affiliate_wp_affiliates a ON r.affiliate_id=a.affiliate_id "
            . " LEFT JOIN {$wpdb->prefix}users u ON u.ID=a.user_id "
            . " LEFT JOIN {$wpdb->prefix}smart_kwk k ON k.referral_id=r.referral_id "
            . " LEFT JOIN {$wpdb->prefix}smart_kwk_vouchers v ON v.referral_id=r.referral_id "
            . " WHERE 1 ORDER BY k.date_inserted DESC LIMIT $offset, $limit";

    $total = (int) $wpdb->get_var($countSql);

    $num_of_pages = $total > 0 ? ceil($total / $limit) : 1;

    $data = $wpdb->get_results($sql, ARRAY_A);

    $page_links = paginate_links(array(
        'base' => add_query_arg('pagenum', '%#%'),
        'format' => '',
        'prev_text' => __('&laquo;', 'smart-kwk'),
        'next_text' => __('&raquo;', 'smart-kwk'),
        'total' => $num_of_pages,
        'current' => $pagenum
    ));

    if ($data) {
        echo '<table id="overviewTable" class="wp-list-table widefat fixed striped posts dataTable">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Werber</th><th>Geworbener</th><th>Bestelldatum</th><th>Status</th><th>Gutschein</th><th>Status ändern</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        foreach ($data as $ref) {

            if ($ref['date_sent'] === NULL && (int) $ref['paid'] === 1) {
                $button = '<button class="sendVoucherButton button button-large" rid="' . $ref['referral_id'] . '">Gutschein versenden</button>';
            } elseif ($ref['date_sent']) {
                $button = 'versendet am ' . $ref['date_sent'];
            } else {
                $button = '';
            }

            if (!$ref['date_sent'] && $ref['api_status'] == 'denied') {
                $statusButton = '<button class="button button-primary changeStatusButton" rid="' . $ref['referral_id'] . '" newstatus="accepted">Bestätigen</buton>';
            } elseif (!$ref['date_sent'] && $ref['api_status'] == 'accepted') {
                $statusButton = '<button class="button button-secondary changeStatusButton" rid="' . $ref['referral_id'] . '" newstatus="denied">Ablehnen</buton>';
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
            echo '<div class="tablenav"><div class="tablenav-pages" style="margin: 1em 0">' . $page_links . '</div></div>';
        }

        echo '<p><button id="bulk_send_btn" class="button button-large">An Alle senden</button></p>';
    } else {
        echo '<p><strong>Keine Daten gefunden</strong></p>';
    }
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

function stkwk_tab_emailtpl() {
    echo '<div class="metabox-holder">';
    echo '<div class="postbox">';
    echo "<h3>" . __('E-Mail Template', 'smart-kwk') . "</h3>";

    $targetfile = stkwk_get_mail_template();

    if (file_exists($targetfile)) {
        $content = file_get_contents($targetfile);
    } else {
        $content = '';
    }

    if (isset($_GET['updated'])) {
        ?>
        <div id="message" class="updated notice notice-success is-dismissible">
            <p>
                <?= __('E-Mail Template aktualisiert', 'smart-kwk') ?>
            </p>
            <button class="notice-dismiss" type="button">
                <span class="screen-reader-text"><?= __('Diese Meldung verwerfen.', 'smart-kwk') ?></span>
            </button>
        </div>
        <?php
    }
    ?>
    <div class="inside">
        <?php
        if (file_exists($targetfile)) {
            echo '<p><a target="_blank" href="' . plugins_url('smart_kwk/templates/' . basename($targetfile)) . '">Vorschau</a></p>';
        }
        ?>
        <p> <?= __('Unterstützte Platzhalter:', 'smart-kwk') ?> <pre><strong><?= PLACEHOLDER_VOUCHER ?></strong></pre></p>
    <p>
    <form action="<?= admin_url('admin-post.php') ?>" method="post">
        <div style="float:left;width: 48%;"><textarea style="width:100%;height:305px;" name="content"><?= $content ?></textarea></div>
        <div style="float:left;width: 48%;margin-left: 2%;border: 1px solid #ccc;">
            <iframe src="<?= plugins_url('smart_kwk/templates/' . basename($targetfile)) ?>" style="width: 100%; height: 300px"
                    scrolling="yes" marginwidth="0" marginheight="0" frameborder="0" vspace="0" hspace="0">
            </iframe>
        </div>
        <div class="clear"></div>
        <input type="hidden" name="action" value="save_emailtemplate"/>
        <p><input class="button button-large" type="submit" value="<?= __('Speichern', 'smart-kwk') ?>" name="smart_save_emailtemplate"/></p>
    </form>
    </p>
    </div>
    <?php
}

function stkwk_save_emailtemplate() {

    $content = trim($_POST['content']);

    $content = stripslashes($content);

    $targetfile = stkwk_get_mail_template();

    if (file_put_contents($targetfile, $content)) {
        $ok = 1;
    } else {
        $ok = 0;
    }

    $location = admin_url("admin.php?page=smartkwk&tab=emailtpl&updated=$ok");
    wp_redirect($location);
    exit;
}

function stkwk_tab_voucher() {

    global $wpdb;

    echo '<div class="metabox-holder">';
    echo '<div class="postbox">';
    echo "<h3>" . __('Gutscheinverwaltung', 'smart-kwk') . "</h3>";
    if (isset($_GET['updated'])) {
        ?>
        <div id="message" class="updated notice notice-success is-dismissible">
            <p>
                <?= __('Gutscheine importiert:', 'smart-kwk') . ' ' . $_GET['updated'] ?>
            </p>
            <button class="notice-dismiss" type="button">
                <span class="screen-reader-text"><?= __('Diese Meldung verwerfen.', 'smart-kwk') ?></span>
            </button>
        </div>
        <?php
    }
    ?>
    <div class="inside">
        <form action="<?= admin_url('admin-post.php') ?>" method="post">
            <input type="text" name="code" placeholder="<?= __('Gutscheincode', 'smart-kwk') ?>"/>
            <input type="hidden" name="action" value="addvoucher"/>
            <input class="button button-large" type="submit" value="<?= __('Hinzufügen', 'smart-kwk') ?>" name="smart_addvoucher"/>
        </form>
    </div>

    <div class="inside">
        <form action="<?= admin_url('admin-post.php') ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="importvouchers">
            <input id="import_vouchers" class="button button-large" type="submit" value="<?= __('Gutscheine importieren', 'smart-kwk') ?>" name="smart_importvouchers"/>
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

        echo '<p>' . $unused . ' von ' . count($vouchers) . ' Gutscheinen unbenutzt</p>';
        echo '<table id="vouchertable" class="wp-list-table widefat fixed striped posts dataTable">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Gutschein</th><th>Erstellt am</th><th>Versendet am</th><th>Werber</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        foreach ($vouchers as $ref) {

            if ($ref['affiliate_id']) {
                $recipient = $wpdb->get_var($wpdb->prepare("SELECT u.user_email FROM {$wpdb->prefix}affiliate_wp_affiliates a LEFT JOIN {$wpdb->prefix}users u ON a.user_id=u.ID WHERE a.affiliate_id = %d", (int) $ref['affiliate_id']));

                if (!$recipient) {
                    $recipient = 'Affiliate ID ' . $ref['affiliate_id'] . ' unbekannt';
                }
                $editable = "";
            } else {
                $recipient = '';
                $editable = "stkwk_editable";
            }

            echo '<tr>';
            echo '<td class="' . $editable . '" vid="' . $ref['id'] . '">' . $ref['voucher_code'] . '</td><td>' . $ref['date_inserted'] . '</td><td>' . $ref['date_sent'] . '</td><td>' . $recipient . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p><strong>Keine Gutscheine gefunden.</strong></p>';
    }
    echo '</div>';

    echo '</div>';
    echo '</div>';
}

function stkwk_tab_backoffice() {

    global $wpdb;

    echo '<div class="metabox-holder">';
    echo '<div class="postbox">';
    echo "<h3>" . __('Backoffice', 'smart-kwk') . "</h3>";
    if (isset($_GET['updated'])) {
        ?>
        <div id="message" class="updated notice notice-success is-dismissible">
            <p>
                <?= __('Datensätze aktualisiert:', 'smart-kwk') . ' ' . $_GET['updated'] ?>
            </p>
            <button class="notice-dismiss" type="button">
                <span class="screen-reader-text"><?= __('Diese Meldung verwerfen.', 'smart-kwk') ?></span>
            </button>
        </div>
        <?php
    }

    echo '<div class="inside">';
    echo '<p>Offene Prämien:</p>';

    $status = 'unpaid';

    $sql = "SELECT r.*, k.api_response, k.paid FROM {$wpdb->prefix}affiliate_wp_referrals r LEFT JOIN {$wpdb->prefix}smart_kwk k ON r.referral_id=k.referral_id WHERE r.status='$status'";

    $referrals = $wpdb->get_results($sql, ARRAY_A);

    if ($referrals) {
        echo '<table class="wp-list-table widefat fixed striped posts">';
        echo '<thead>';
        echo '<tr>';
        echo '<td>E-Mail</td><td>Bestelldatum</td><td>K-Nummer</td><td>Expercash</td>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        foreach ($referrals as $ref) {

            $paid = $ref['paid'] == 1 ? 'Bezahlt' : '';

            echo '<tr>';
            if ($ref['api_response']) {
                echo '<td>' . $ref['description'] . '</td><td>' . date('Y-m-d H:i:s', strtotime($ref['date'])) . '</td><td>' . $ref['api_response'] . '</td><td>' . $paid . '</td>';
            } else {
                echo '<td>' . $ref['description'] . '</td><td>' . $ref['date'] . '</td><td><input type="hidden" name="rid[]" value="' . $ref['referral_id'] . '"/>' . $ref['api_response'] . '</td><td>' . $paid . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p><strong>Keine Referrals gefunden.</strong></p>';
    }
    echo '</div>';
    ?>
    <div class="inside">
        <form action="<?= admin_url('admin-post.php') ?>" method="post">
            <input type="hidden" name="action" value="callbackoffice">
            <input id="backofficeFormSubmit" class="button button-large" type="submit" value="<?= __('Backoffice abfragen', 'smart-kwk') ?>" name="smart_callbackoffice"/>
        </form>
    </div>
    <div class="inside">
        <form action="<?= admin_url('admin-post.php') ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="importexpercash">
            <input type="file" name="expercashfile" accept="text/*"/>
            <input class="button button-large" type="submit" value="<?= __('Import K-Nummern', 'smart-kwk') ?>" name="smart_importexpercash"/>
        </form>
    </div>
    <?php
    echo '</div>';
    echo '</div>';
}

function stkwk_change_status($ref_id = false, $new_status = false) {
    if (!$ref_id && isset($_POST['ref'])) {
        $ref_id = $_POST['ref']; //coming from ajax
    }
    if (!$new_status && isset($_POST['newstatus'])) {
        $new_status = $_POST['newstatus']; //coming from ajax
    }

    if (!(is_numeric($ref_id) && in_array($new_status, array('denied', 'accepted')))) {
        $return = array('status' => 'error', 'message' => __('Parameter ungültig', 'smart-kwk'));
    } else {
        global $wpdb;

        $sql = "SELECT id FROM {$wpdb->prefix}smart_kwk k WHERE k.referral_id=$ref_id";
        $id = $wpdb->get_var($sql);

        if (!$id) {
            //referral not found
            header('Content-Type: application/json');
            $return = array('status' => 'error', 'message' => __('Referral nicht gefunden', 'smart-kwk'));
        } else {
            //change status
            $save = array();
            $save['api_status'] = $new_status;
            $save['api_response'] = $save['api_status'] == 'denied' ? 'Manuell abgelehnt' : 'Manuell bestätigt';
            $save['date_exported'] = null;
            if ($save['api_status'] == 'accepted') {
                $save['paid'] = 1;
            } else {
                $save['paid'] = 0;
            }

            $ok = $wpdb->update($wpdb->prefix . 'smart_kwk', $save, array('id' => $id));

            if (!$ok) {
                $return = array('status' => 'error', 'message' => __('Fehler beim Speichern', 'smart-kwk'));
            } else {
                $return = array('status' => 'success', 'message' => __('Status aktualisiert', 'smart-kwk'), 'newstatus' => $save['api_status'], 'newstatustext' => $save['api_response']);
            }
        }
    }

    header('Content-Type: application/json');
    echo json_encode($return);
    exit;
}

function stkwk_send_voucher($ref_id = false) {

    if (!$ref_id && isset($_POST['ref'])) {
        $ref_id = $_POST['ref']; //coming from ajax
    }

    global $wpdb;

    $ref_id = (int) $ref_id;

    //check if this referral entitled for voucher
    $sql = "SELECT r.description,r.amount,r.affiliate_id,r.referral_id as ref, v.date_sent FROM {$wpdb->prefix}affiliate_wp_referrals r "
            . " LEFT JOIN {$wpdb->prefix}smart_kwk k ON r.referral_id=k.referral_id"
            . " LEFT JOIN {$wpdb->prefix}smart_kwk_vouchers v ON r.referral_id=v.referral_id"
            . " WHERE k.paid=1 "
            . " AND r.referral_id=$ref_id";

    $data = $wpdb->get_results($sql, ARRAY_A);

    if (!$data) {
        header('Content-Type: application/json');
        echo json_encode(array('status' => 'error', 'message' => __('Referral nicht gefunden', 'smart-kwk')));
        exit;
    } elseif (count($data) > 1) {
        header('Content-Type: application/json');
        echo json_encode(array('status' => 'error', 'message' => __('Mehr als 1 Referral gefunden', 'smart-kwk')));
        exit;
    }

    $row = $data[0];

    $affiliate = $wpdb->get_row("SELECT u.user_email,a.* FROM {$wpdb->prefix}affiliate_wp_affiliates a LEFT JOIN {$wpdb->prefix}users u ON a.user_id=u.ID WHERE a.affiliate_id = {$row['affiliate_id']}", ARRAY_A);

    if (!$affiliate) {
        header('Content-Type: application/json');
        echo json_encode(array('status' => 'error', 'message' => __('Affiliate nicht gefunden', 'smart-kwk')));
        exit;
    }

    $affiliateEmail = $affiliate['user_email'];

    //check if already sent
    if ($row['date_sent']) {
        header('Content-Type: application/json');
        echo json_encode(array('status' => 'error', 'message' => __('Gutschein bereits versendet', 'smart-kwk')));
        exit;
    }

    //grab a new unused voucher
    $voucher = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}smart_kwk_vouchers WHERE date_sent IS NULL ORDER BY date_inserted ASC", ARRAY_A);

    if (!$voucher) {
        header('Content-Type: application/json');
        echo json_encode(array('status' => 'error', 'message' => __('Kein unbenutzter Gutschein vorhanden', 'smart-kwk')));
        exit;
    }

    $subject = __('Ihr Amazon Gutschein', 'smart-kwk');

    if (file_exists(stkwk_get_mail_template())) {
        $content = file_get_contents(stkwk_get_mail_template());
    } else {
        $content = '';
    }

    //check if placeholder for voucher is set
    if (!stripos($content, PLACEHOLDER_VOUCHER)) {
        header('Content-Type: application/json');
        echo json_encode(array('status' => 'error', 'message' => __('Platzhalter für Gutschein nicht gefunden', 'smart-kwk')));
        exit;
    }

    //replace placeholder with actual voucher
    $content = str_replace(PLACEHOLDER_VOUCHER, $voucher['voucher_code'], $content);

    $addError = '';
    $sent = false;

    sleep(1); //wait a second (too many mails in too short timespan can increase risk of spam perception)
    //sending using smtp
    if (USE_SMTP) {

        require_once ABSPATH . WPINC . '/class-phpmailer.php';
        require_once ABSPATH . WPINC . '/class-smtp.php';
        $phpmailer = new PHPMailer(true);

        $phpmailer->IsSMTP();
        $phpmailer->Host = SMTP_HOST;

        //remove this in production mode!
//        $phpmailer->SMTPOptions = array(
//            'ssl' => array(
//                'verify_peer' => false,
//                'verify_peer_name' => false,
//                'allow_self_signed' => true
//            )
//        );

        $phpmailer->SMTPAuth = true;
        $phpmailer->Username = SMTP_USERNAME;
        $phpmailer->Password = SMTP_PASSWORD;
        $phpmailer->Port = SMTP_PORT;
        $phpmailer->SMTPSecure = SMTP_ENCRYPTION;
        $phpmailer->IsHTML();
        $phpmailer->From = SMTP_FROM_EMAIL;
        $phpmailer->FromName = SMTP_FROM_NAME;
        $phpmailer->AddAddress($affiliateEmail);
        $phpmailer->addBCC(BCC_EMAIL);
        $phpmailer->Subject = utf8_decode($subject);
        $phpmailer->Body = $content;

        try {
            $sent = $phpmailer->Send();

            if ($phpmailer->ErrorInfo) {
                $addError = $phpmailer->ErrorInfo;
            }
        } catch (Exception $ex) {
            $addError = $ex->getMessage();
        }
    }

    if (!$sent) {
//        $headers = array();
//        $headers['From'] = SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>';
//        $headers['BCC'] = BCC_EMAIL;
//        $headers['Content-Type'] = 'text/html';

        $headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
        $headers .= "BCC: " . BCC_EMAIL . "\r\n";
        $headers .= "Content-Type: text/html";

        try {
            $sent = wp_mail($affiliateEmail, utf8_decode($subject), $content, $headers);
        } catch (Exception $ex) {
            $addError = $ex->getMessage();
        }
    }

    header('Content-Type: application/json');
    if ($sent) {
        //mark voucher as "used"
        $ok = $wpdb->update($wpdb->prefix . 'smart_kwk_vouchers', array('date_sent' => date('c'), 'date_inserted' => $voucher['date_inserted'], 'referral_id' => $row['ref'], 'affiliate_id' => $row['affiliate_id']), array('id' => $voucher['id']));

        //mark referral as paid (affiliate stats are updated also)
        if (function_exists('affwp_set_referral_status')) {
            affwp_set_referral_status($ref_id, 'paid');
        }


        if (!$ok) {
            echo json_encode(array('status' => 'success', 'message' => __('Gutschein versendet, aber nicht in DB entwertet!', 'smart-kwk')));
        } else {
            echo json_encode(array('status' => 'success', 'message' => __('Gutschein versendet', 'smart-kwk')));
        }
    } else {
        echo json_encode(array('status' => 'error', 'message' => __('Gutschein konnte nicht versendet werden. ' . $addError, 'smart-kwk')));
    }
    exit;
}

function stkwk_get_mail_template() {
    return dirname(__FILE__) . '/templates/sendVoucher.html';
}

function stkwk_save_voucher($vid = false, $voucher = false) {
    if (!$vid && isset($_POST['vid'])) {
        $vid = trim($_POST['vid']);
    }

    if (!$voucher && isset($_POST['code'])) {
        $voucher = trim($_POST['code']);
    }

    $return = array('status' => 'error', 'message' => __('Gutschein konnte nicht gespeichert werden!', 'smart-kwk'));

    global $wpdb;

    if ($voucher && $vid) {
        $ok = $wpdb->update($wpdb->prefix . 'smart_kwk_vouchers', array('voucher_code' => $voucher), array('id' => $vid));
    } elseif ($vid) {
        $ok = $wpdb->delete($wpdb->prefix . 'smart_kwk_vouchers', array('id' => $vid), array('%s'));
    }

    if ($ok) {
        $return = array('status' => 'success', 'message' => __('Gutschein gespeichert!', 'smart-kwk'));
    } elseif (strlen($vid) === 32) {
        $voucher = $wpdb->get_var($wpdb->prepare("SELECT voucher_code FROM {$wpdb->prefix}smart_kwk_vouchers WHERE id = %s", $vid));
    }

    $return['code'] = $voucher ? $voucher : '';

    header('Content-Type: application/json');
    echo json_encode($return);
    exit;
}

function stkwk_api_request($rid = null) {

    if (!$rid && isset($_POST['rid'])) {
        $rid = trim($_POST['rid']);
    }

    if (!($rid && is_numeric($rid))) {
        header('Content-Type: application/json');
        echo json_encode(array('status' => 'error', 'message' => __('Parameter referral_id fehlt oder fehlerhaft', 'smart-kwk')));
        exit;
    }

    global $wpdb;

    $ref = $wpdb->get_row("SELECT r.*,k.date_inserted FROM {$wpdb->prefix}affiliate_wp_referrals r LEFT JOIN {$wpdb->prefix}smart_kwk k ON r.referral_id=k.referral_id WHERE r.referral_id='$rid'", ARRAY_A);

    if (!($ref && $ref['description'] && $ref['date'])) {
        header('Content-Type: application/json');
        echo json_encode(array('status' => 'error', 'message' => __('Referal konnte nicht gefunden werden oder E-Mail/Bestelldatum nicht vorhanden', 'smart-kwk')));
        exit;
    }

    if (!defined('BACKOFFICE_REQUEST_URL')) {
        header('Content-Type: application/json');
        echo json_encode(array('status' => 'error', 'message' => __('BACKOFFICE_REQUEST_URL ist in der config.php nicht definiert.', 'smart-kwk')));
        exit;
    }

    $timestamp = strtotime($ref['date']);
    $date = date('Y-m-d', $timestamp);

    $email = $ref['description'];

    $serviceURL = BACKOFFICE_REQUEST_URL . "&email=" . $ref['description'];

    $curl = curl_init($serviceURL);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($curl);

    if ($result) {
        $result = json_decode($result);
    }

    $error = curl_error($curl);
    curl_close($curl);

    if ($error) {
        $return = array('status' => 'error', 'message' => $error);
    } else {
        //kunde nicht vorhanden
        $return = array('status' => 'denied', 'message' => __('Abgelehnt - Kein Bestandskunde', 'smart-kwk'));

        //parsing response
        $backoffice = array();

        if ($result) {
            foreach ($result as $r) {
                $backoffice[$email][$r->date]['ordernumber'] = $r->ordernumber;
                $backoffice[$email][$r->date]['invoicenumber'] = $r->invoicenumber;
                $backoffice[$email][$r->date]['timestamp'] = $r->timestamp / 1000;

                //prüfe, ob backofficedatum vom wp affiliate datum abweicht, berücksichtige puffer
                if (ALLOWED_ORDERTIME_OFFSET > 0 && $date != $r->date) {

                    $datediff = $backoffice[$email][$r->date]['timestamp'] - strtotime("$date");

                    $diff = abs(floor($datediff / (60 * 60 * 24))); //zeitunterschied in tagen

                    if ($diff <= ALLOWED_ORDERTIME_OFFSET) {
                        $date = $r->date; //setze wp affiliate datum = backoffice datum, um bestätigung zu ermöglichen
                    }
                }
            }
        }

        //kunde vorhanden?
        if ($backoffice[$email]) {

            //bestellung vorhanden?
            if ($backoffice[$email][$date]['ordernumber']) {

                //mehrere bestellungen?
                if (count($backoffice[$email]) > 1) {
                    $return = array('status' => 'denied', 'message' => __('Abgelehnt - Bestandskunde', 'smart-kwk'));
                } elseif (!$backoffice[$email][$date]['invoicenumber']) {
                    $return = array('status' => 'error', 'message' => __('Fehler: Kundenrechnungsnummer fehlt', 'smart-kwk'));
                } elseif (!$backoffice[$email][$date]['timestamp']) {
                    $return = array('status' => 'error', 'message' => __('Fehler: Timestamp fehlt', 'smart-kwk'));
                } else {
                    //nur eine bestellung zum datum vorhanden = OK
                    //K Nummer vor bestelldatum angelegt?
                    //ablehnen, wenn bestellzeitpunkt der backofficeerfassung VOR dem bestellzeitpunkt im plugin
                    if ($backoffice[$email][$date]['timestamp'] < $timestamp) {
                        $return = array('status' => 'denied', 'message' => __('Abgelehnt - Bestandskunde', 'smart-kwk'));
                    } else {
                        //bestätigt nur, wenn backofficeerfassung NACH bestellzeitpunkt im plugin
                        $return = array('status' => 'accepted', 'message' => __('Bestätigt: ', 'smart-kwk') . $backoffice[$email][$date]['ordernumber'], 'businesscase' => $backoffice[$email][$date]['ordernumber']);
                    }
                }
            } else {
                //bestellung zum datum nicht vorhanden
                $return = array('status' => 'denied', 'message' => __('Abgelehnt - Anderes Datum', 'smart-kwk'));
            }
        }

        //save data in db
        $save = array();
        $save['referral_id'] = $ref['referral_id'];
        $save['order_date'] = date('Y-m-d H:i:s', $timestamp); //Y-m-d
        $save['business_case'] = $return['businesscase'] ? $return['businesscase'] : NULL;
        $save['api_status'] = $return['status'];
        $save['api_response'] = $return['message'];

        //save if no error occurred
        if ($return['status'] != 'error') {

            //update if referral exists in smart_kwk
            if (isset($ref['date_inserted'])) {
                $ok = $wpdb->update("{$wpdb->prefix}smart_kwk", $save, array('referral_id' => $ref['referral_id']));
            } else {
                $ok = $wpdb->insert("{$wpdb->prefix}smart_kwk", $save);
            }


            if (!$ok) {
                $return = array('status' => 'error', 'message' => $wpdb->last_error);
            }
        }
    }

    header('Content-Type: application/json');
    echo json_encode($return);
    exit;
}

function stkwk_tab_import() {
    echo '<div class="metabox-holder">';
    echo '<div class="postbox">';
    echo "<h3>" . __('Import', 'smart-kwk') . "</h3>";
    if (isset($_GET['updated'])) {
        ?>
        <div id="message" class="updated notice notice-success is-dismissible">
            <p>
                <?= __('Datensätze aktualisiert:', 'smart-kwk') . ' ' . $_GET['updated'] ?>
            </p>
            <button class="notice-dismiss" type="button">
                <span class="screen-reader-text"><?= __('Diese Meldung verwerfen.', 'smart-kwk') ?></span>
            </button>
        </div>
        <?php
    }
    ?>
    <div class="inside">
        <p><?= __('Satzaufbau wie Exportdatei<br>Spalte 4: E-Mail des Geworbenen<br>Spalte 7: Status (paid, unpaid, rejected, pending)', 'smart-kwk') ?></p>
        <form action="<?= admin_url('admin-post.php') ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="importkwk">
            <input type="file" name="importkwkfile" accept="text/*"/>
            <input class="button button-large" type="submit" value="<?= __('Import', 'smart-kwk') ?>" name="smart_importkwk"/>
        </form>
    </div>
    <?php
    echo '</div>';
    echo '</div>';
}

function stkwk_tab_export() {
    echo '<div class="metabox-holder">';
    echo '<div class="postbox">';
    echo "<h3>" . __('Export', 'smart-kwk') . "</h3>";
    ?>
    <div class="inside">
        <p><?= __('Es werden alle Referrals mit zugeordneten Affiliates exportiert, bei denen die Provision größer Null ist.', 'smart-kwk') ?></p>
        <form action="<?= admin_url('admin-post.php') ?>" method="post">
            <input type="hidden" name="action" value="exportkwk">
            <input class="button button-large" type="submit" value="<?= __('Export', 'smart-kwk') ?>" name="smart_exportkwk"/>
        </form>
    </div>
    <?php
    echo '</div>';
    echo '</div>';
}

function stkwk_tab_expercash() {
    echo '<div class="metabox-holder">';
    echo '<div class="postbox">';
    echo "<h3>" . __('Expercash', 'smart-kwk') . "</h3>";
    if (isset($_GET['formerror']) && $_GET['formerror'] === '1') {
        ?>
        <div id="message" class="error notice notice-error is-dismissible">
            <p>
                <?= __('Bitte Username und Passwort eingeben.', 'smart-kwk') ?>
            </p>
            <button class="notice-dismiss" type="button">
                <span class="screen-reader-text"><?= __('Diese Meldung verwerfen.', 'smart-kwk') ?></span>
            </button>
        </div>
        <?php
    }
    if (isset($_GET['rubyerror']) && $_GET['rubyerror'] === '1') {
        ?>
        <div id="message" class="error notice notice-error is-dismissible">
            <p>
                <?= __('Die Transaktionsdatei konnte nicht erstellt werden.', 'smart-kwk') ?>
            </p>
            <button class="notice-dismiss" type="button">
                <span class="screen-reader-text"><?= __('Diese Meldung verwerfen.', 'smart-kwk') ?></span>
            </button>
        </div>
        <?php
    }
    if (isset($_GET['updated'])) {
        ?>
        <div id="message" class="updated notice notice-success is-dismissible">
            <p>
                <?= __('Datensätze als bezahlt markiert:', 'smart-kwk') . ' ' . $_GET['updated'] ?>
            </p>
            <button class="notice-dismiss" type="button">
                <span class="screen-reader-text"><?= __('Diese Meldung verwerfen.', 'smart-kwk') ?></span>
            </button>
        </div>
        <?php
    }
    ?>
    <div class="inside">
        <p><?= __('Lädt Transaktionen runter und setzt den Status auf "Bezahlt"', 'smart-kwk') ?></p>
        <form action="<?= admin_url('admin-post.php') ?>" method="post">
            <input type="text" name="u" placeholder="<?= __('Username', 'smart-kwk') ?>"/>
            <input type="password" name="p" placeholder="<?= __('Password', 'smart-kwk') ?>"/>
            <input type="hidden" name="action" value="getimport"/>
            <input class="button button-large" type="submit" value="<?= __('Abfragen', 'smart-kwk') ?>" name="smart_getimport"/>
        </form>
    </div>
    <?php
    echo '</div>';
    echo '</div>';
}

function stkwk_admin_addvoucher() {
    $voucher = trim($_POST['code']);

    $updated = 0;

    if ($voucher) {
        global $wpdb;
        $save = array();
        $save['id'] = md5($voucher);
        $save['voucher_code'] = $voucher;
        $updated = (int) $wpdb->insert($wpdb->prefix . 'smart_kwk_vouchers', $save);
    }

    $location = admin_url("admin.php?page=smartkwk&tab=voucher&updated=$updated");
    wp_redirect($location);
    exit;
}

function stkwk_admin_getimport() {
    $username = trim($_POST['u']);
    $password = trim($_POST['p']);
    if (!($username && $password)) {
        $location = admin_url("admin.php?page=smartkwk&tab=expercash&formerror=1");
        wp_redirect($location);
        exit;
    }

    $targetFile = dirname(__FILE__) . '/expercash/valid_transactions.csv';

    @unlink($targetFile); //delete previous file

    $dateRange = date('d m Y', strtotime("-30 days")) . ' ' . date('d m Y');

// Change directory
    chdir(dirname(__FILE__) . '/expercash');

    $cmd = "ruby expercash.rb $username $password $dateRange";
    $result = system($cmd);

    file_put_contents(stwk_get_logfile(), date('d.m.Y H:i:s') . ' ' . $cmd . PHP_EOL, FILE_APPEND);

    if (!file_exists($targetFile)) {
        //var_dump($result); die; //delete this line if error from ruby file not needed
        $location = admin_url("admin.php?page=smartkwk&tab=expercash&rubyerror=1");
        wp_redirect($location);
        exit;
    }

    //import targetfile
    $file = fopen($targetFile, "r");

    global $wpdb;
    $updated = 0;
    while (($row = fgetcsv($file, 0, ';'))) {

        $businessCaseNumber = $row[1];

        //update by business_case
        $ok = $wpdb->update($wpdb->prefix . 'smart_kwk', array('paid' => 1), array('business_case' => $businessCaseNumber));
        if ($ok) {
            $updated++;
        }
    }

    fclose($file);
    $location = admin_url("admin.php?page=smartkwk&tab=expercash&updated=$updated");
    wp_redirect($location);
    exit;
}

function stkwk_admin_importexpercash() {
    $file = fopen($_FILES['expercashfile']['tmp_name'], "r");

    global $wpdb;
    $updated = 0;
    while (($row = fgetcsv($file, 0, ';'))) {

        if (stripos(trim($row[5]), 'Referenz') !== false) {
            continue; //skip header row
        }

        $businessCaseNumber = trim($row[5]);

        //update by business_case (alternatively by referal id)
        $ok = $wpdb->update($wpdb->prefix . 'smart_kwk', array('paid' => 1), array('business_case' => $businessCaseNumber));
        if ($ok) {
            $updated++;
        }
    }

    fclose($file);
    @unlink($_FILES['expercashfile']['tmp_name']);
    $location = admin_url("admin.php?page=smartkwk&tab=backoffice&updated=$updated");
    wp_redirect($location);
    exit;
}

function stkwk_admin_importvouchers() {

    $file = fopen($_FILES['voucherfile']['tmp_name'], "r");

    global $wpdb;
    $inserted = 0;
    while (($row = fgetcsv($file, 0, ';'))) {

        //update by email (alternatively by referal id)
        $save = array();
        $save['id'] = md5($row[0]);
        $save['voucher_code'] = $row[0];
        $inserted += (int) $wpdb->insert($wpdb->prefix . 'smart_kwk_vouchers', $save);
    }

    fclose($file);
    @unlink($_FILES['voucherfile']['tmp_name']);
    $location = admin_url("admin.php?page=smartkwk&tab=voucher&updated=$inserted");
    wp_redirect($location);
    exit;
}

function stkwk_admin_importkwk() {

    $file = fopen($_FILES['importkwkfile']['tmp_name'], "r");
    $allowedStatus = array('paid', 'unpaid', 'rejected', 'pending');

    global $wpdb;
    $updated = 0;
    while (($row = fgetcsv($file, 0, ';'))) {
        if (in_array(strtolower(trim($row[6])), $allowedStatus)) {
            //update by email (alternatively by referal id)
            $updated = $wpdb->update($wpdb->prefix . 'affiliate_wp_referrals', array('status' => $row[6]), array('description' => trim($row[3])));
        }
    }

    fclose($file);
    @unlink($_FILES['importkwkfile']['tmp_name']);
    $location = admin_url("admin.php?page=smartkwk&tab=import&updated=$updated");
    wp_redirect($location);
    exit;
}

function stkwk_admin_export_overview() {

    global $wpdb;

    $sql = "SELECT k.id,u.user_email,r.description,k.api_response,k.paid,a.affiliate_id, r.referral_id, r.date, v.date_sent "
            . " FROM {$wpdb->prefix}affiliate_wp_referrals r "
            . " LEFT JOIN {$wpdb->prefix}affiliate_wp_affiliates a ON r.affiliate_id=a.affiliate_id "
            . " LEFT JOIN {$wpdb->prefix}users u ON u.ID=a.user_id "
            . " LEFT JOIN {$wpdb->prefix}smart_kwk k ON k.referral_id=r.referral_id "
            . " LEFT JOIN {$wpdb->prefix}smart_kwk_vouchers v ON v.referral_id=r.referral_id "
            . " WHERE k.date_exported IS NULL ORDER BY k.order_date DESC";

    $results = $wpdb->get_results($sql, ARRAY_A);

    $filename = 'Kwk_Overview_' . date('Y-m-d') . '.csv';

    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header('Content-Description: File Transfer');
    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename={$filename}");
    header("Expires: 0");
    header("Pragma: public");

    $fh = @fopen('php://output', 'w');

    $headerDisplayed = false;

    $header = array('Werber', 'Werber ID', 'Bestelldatum', 'Geworbener', 'Status', 'Gutschein verschickt');

    $delimiter = ';';

    if ($results) {
        foreach ($results as $r) {

            if (!$headerDisplayed) {
                fputcsv($fh, $header, $delimiter);
                $headerDisplayed = true;
            }

            $data = array($r['user_email'], $r['affiliate_id'], $r['date'], $r['description'], utf8_decode($r['api_response']), $r['date_sent']);

            fputcsv($fh, $data, $delimiter);

            //flag as exported
            $wpdb->update($wpdb->prefix . 'smart_kwk', array('date_exported' => date('c')), array('id' => $r['id']));
        }
    }

    fclose($fh);
    exit;
}

function stkwk_admin_exportkwk() {

    global $wpdb;

    $period = 14; //revocable period

    $results = $wpdb->get_results("SELECT r.description, r.referral_id, r.affiliate_id, r.status,r.date,r.amount,a.user_id,a.user_id "
            . "FROM {$wpdb->prefix}affiliate_wp_referrals r "
            . "LEFT JOIN {$wpdb->prefix}affiliate_wp_affiliates a ON r.affiliate_id=a.affiliate_id "
            . "WHERE r.amount>0", ARRAY_A);

    $filename = 'KwK_' . date('Y-m-d') . '.csv';

    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header('Content-Description: File Transfer');
    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename={$filename}");
    header("Expires: 0");
    header("Pragma: public");

    $fh = @fopen('php://output', 'w');

    $headerDisplayed = false;

    $header = array('Werbedatum', 'Empfehler ID', 'Empfehler', 'Geworbener', 'Identisch', 'Provision', 'Status', 'Backoffice Status', 'BusinessCase', 'Expercash Status');

    $delimiter = ';';

    if ($results) {
        foreach ($results as $r) {

            if (!$headerDisplayed) {
                fputcsv($fh, $header, $delimiter);
                $headerDisplayed = true;
            }

            $email = $wpdb->get_var("SELECT user_email FROM {$wpdb->users} WHERE id={$r['user_id']}");

            $paid = '';

            if ($r['referral_id'] && $r['date']) {

                $d = date('Y-m-d', strtotime($r['date'])); //dont consider time of day

                $response = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}smart_kwk WHERE referral_id={$r['referral_id']} AND order_date LIKE '$d%'", ARRAY_A)[0];
                if ($response) {
                    $paid = $response['paid'] == '1' ? 'Bezahlt' : '';
                }
            }

            $same = trim($email) == trim($r['description']) ? 'Y' : 'N';

            $data = array(date('d.m.Y', strtotime($r['date'])), $r['affiliate_id'], $email, $r['description'], $same, $r['amount'], $r['status'], utf8_decode($response['api_response']), $response['business_case'], $paid);

            fputcsv($fh, $data, $delimiter);
        }
    }

    fclose($fh);
    exit;
}

function stkwk_add_scripts() {

    wp_register_script('smart_kwk', plugins_url('smart_kwk') . '/js/smart_kwk.js', array('jquery'));
    wp_enqueue_script('smart_kwk');
    wp_localize_script('smart_kwk', 'SmartKwk', array('ajaxurl' => admin_url('admin-ajax.php'), 'img' => admin_url('images')));
}

function stkwk_install_plugin() {

    if (!is_plugin_active('affiliate-wp/affiliate-wp.php')) {
        exit('Required plugin "AffiliateWP" is not installed/activated!');
    }

    global $wpdb;

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    $table_name = $wpdb->prefix . 'smart_kwk';

    $sql = "CREATE TABLE $table_name (
            id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            referral_id BIGINT(20) NOT NULL,
            order_date TIMESTAMP NULL DEFAULT NULL,
            business_case VARCHAR(255) NULL DEFAULT NULL,
            api_status CHAR(20) NULL DEFAULT NULL,
            api_response TEXT NULL,
            paid TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
            date_exported TIMESTAMP NULL DEFAULT NULL,
            date_inserted TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY referral_id (referral_id)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";

    dbDelta($sql);

    $table_name = $wpdb->prefix . 'smart_kwk_vouchers';

    $sql = "CREATE TABLE $table_name (
            id VARCHAR(32) NOT NULL,
            voucher_code VARCHAR(255) NOT NULL,
            date_sent TIMESTAMP NULL DEFAULT NULL,
            referral_id BIGINT(20) NULL DEFAULT NULL,
            affiliate_id BIGINT(20) NULL DEFAULT NULL,
            date_inserted TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY voucher_code (voucher_code)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";

    dbDelta($sql);
}

function stkwk_uninstall_plugin() {

    global $wpdb;
    $table_name = $wpdb->prefix . 'smart_kwk';
    $sql = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($sql);

    $table_name = $wpdb->prefix . 'smart_kwk_vouchers';
    $sql = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($sql);
}

// Hook for adding admin menus
register_activation_hook(__FILE__, 'stkwk_install_plugin');
register_uninstall_hook(__FILE__, 'stkwk_uninstall_plugin');

add_action('admin_menu', 'stkwk_add_pages');
add_action('admin_post_exportkwk', 'stkwk_admin_exportkwk');
add_action('admin_post_export_overview', 'stkwk_admin_export_overview');
add_action('admin_post_importkwk', 'stkwk_admin_importkwk');
add_action('admin_post_getimport', 'stkwk_admin_getimport');
add_action('admin_post_addvoucher', 'stkwk_admin_addvoucher');
add_action('admin_post_importvouchers', 'stkwk_admin_importvouchers');
add_action('admin_post_importexpercash', 'stkwk_admin_importexpercash');
add_action('admin_post_save_emailtemplate', 'stkwk_save_emailtemplate');
add_action('stkwk_tab_import', 'stkwk_tab_import');
add_action('stkwk_tab_export', 'stkwk_tab_export');
add_action('stkwk_tab_backoffice', 'stkwk_tab_backoffice');
add_action('stkwk_tab_expercash', 'stkwk_tab_expercash');
add_action('stkwk_tab_voucher', 'stkwk_tab_voucher');
add_action('stkwk_tab_emailtpl', 'stkwk_tab_emailtpl');
add_action('stkwk_tab_overview', 'stkwk_tab_overview');
add_action('wp_ajax_stkwk_api_request', 'stkwk_api_request');
add_action('wp_ajax_stkwk_save_voucher', 'stkwk_save_voucher');
add_action('wp_ajax_stkwk_send_voucher', 'stkwk_send_voucher');
add_action('wp_ajax_stkwk_change_status', 'stkwk_change_status');

add_action('admin_init', 'stkwk_add_scripts');
