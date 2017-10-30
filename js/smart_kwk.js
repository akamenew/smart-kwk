jQuery(document).ready(function ($) {

// jQuery on an empty object, we are going to use this as our Queue
    var ajaxQueue = $({});
    $.ajaxQueue = function (ajaxOpts) {
        var jqXHR,
                dfd = $.Deferred(),
                promise = dfd.promise();
        // queue our ajax request
        ajaxQueue.queue(doRequest);
        // add the abort method
        promise.abort = function (statusText) {

            // proxy abort to the jqXHR if it is active
            if (jqXHR) {
                return jqXHR.abort(statusText);
            }

            // if there wasn't already a jqXHR we need to remove from queue
            var queue = ajaxQueue.queue(),
                    index = $.inArray(doRequest, queue);
            if (index > -1) {
                queue.splice(index, 1);
            }

            // and then reject the deferred
            dfd.rejectWith(ajaxOpts.context || ajaxOpts,
                    [promise, statusText, ""]);
            return promise;
        };
        // run the actual query
        function doRequest(next) {
            jqXHR = $.ajax(ajaxOpts)
                    .then(next, next)
                    .done(dfd.resolve)
                    .fail(dfd.reject);
        }

        return promise;
    };
    $('#import_vouchers').click(function (e) {
        if (!$('#voucher_file').val().length) {
            e.preventDefault();
            alert('Bitte Datei auswählen');
        } else {
            $(this).parent().append('<img src="' + SmartKwk.img + '/wpspin_light.gif" alt="loading..."/>');
        }
    });
    $(".stkwk_editable").hover(
            function () {
                $(this).css('cursor', 'pointer');
                $(this).css('border', '2px dotted #cccccc');
            },
            function () {
                $(this).css('cursor', 'default');
                $(this).css('border', 'none');
            }
    );
    $("#vouchertable").on('dblclick', '.stkwk_editable', function () {

        var code = $(this).text();
        $(this).html('<input class="edited_field" type="text"  value="' + code + '"/>');
        $(this).find('input').focus();
        $(this).removeClass('stkwk_editable');
    });
    $("#vouchertable").on('blur', '.edited_field', function () {

        var el = $(this);
        var code = el.val();
        var vid = el.parent().attr('vid');
        el.parent().addClass('stkwk_editable');
        el.parent().append('<img style="float:right;" src="' + SmartKwk.img + '/wpspin_light.gif" alt="loading..."/>');
        var postData = {
            action: 'stkwk_save_voucher',
            vid: vid,
            code: code
        }
        //save
        $.ajax({
            type: "POST",
            data: postData,
            dataType: "json",
            url: SmartKwk.ajaxurl,
            //This fires when the ajax 'comes back' and it is valid json
            success: function (response) {

                if (response.status == 'success') {
                    if (response.code !== '') {
                        el.parent().text(response.code);
                    } else {
                        el.closest('tr').remove();
                    }

                } else {
                    el.parent().text(response.code);
                }

            }
            //This fires when the ajax 'comes back' and it isn't valid json
        }).fail(function (data) {
            alert(data);
        });
    });

    $('#overviewTable').on('click', '.sendVoucherButton', function () {

        if (!confirm('Gutschein jetzt versenden?')) {
            return;
        }

        var el = $(this);
        var statusCol = el.parent().parent().find('.newStatusCol').first();

        var refid = el.attr('rid');
        el.hide();
        el.parent().append('<img src="' + SmartKwk.img + '/wpspin_light.gif" alt="loading..."/>');
        var postData = {
            action: 'stkwk_send_voucher',
            ref: refid,
        };
        $.ajax({
            type: "POST",
            data: postData,
            dataType: "json",
            url: SmartKwk.ajaxurl,
            //This fires when the ajax 'comes back' and it is valid json
            success: function (response) {
                el.parent().text(response.message);
                statusCol.html('');
            }
            //This fires when the ajax 'comes back' and it isn't valid json
        }).fail(function (data) {
            alert(data);
        });
    });


    $('#overviewTable').on('click', '.changeStatusButton', function () {

        if (!confirm('Status wirklich ändern?')) {
            return;
        }

        var el = $(this);
        var statusCol = el.parent().parent().find('.status_col').first();
        var voucherCol = el.parent().parent().find('.voucher_col').first();
        var refid = el.attr('rid');
        var newStatus = el.attr('newstatus');
        el.hide();
        el.parent().append('<img src="' + SmartKwk.img + '/wpspin_light.gif" alt="loading..."/>');
        var postData = {
            action: 'stkwk_change_status',
            ref: refid,
            newstatus: newStatus,
        };
        $.ajax({
            type: "POST",
            data: postData,
            dataType: "json",
            url: SmartKwk.ajaxurl,
            //This fires when the ajax 'comes back' and it is valid json
            success: function (response) {
                //el.parent().text(response.message);
                if (response.newstatus != '') {
                    statusCol.text(response.newstatustext);

                    var nextstatus, nextstatustext, buttonstatus;

                    if (response.newstatus == 'accepted') {
                        nextstatus = 'denied';
                        nextstatustext = 'Ablehnen';
                        buttonstatus = 'secondary';
                        voucherCol.html('<button class="sendVoucherButton button button-large" rid="' + refid + '">Gutschein versenden</button>');
                    } else {
                        nextstatus = 'accepted';
                        nextstatustext = 'Bestätigen';
                        buttonstatus = 'primary';
                        voucherCol.html('');
                    }

                    el.parent().html('<button class="button button-' + buttonstatus + ' changeStatusButton" rid="' + refid + '" newstatus="' + nextstatus + '">' + nextstatustext + '</button>');
                }
            }
            //This fires when the ajax 'comes back' and it isn't valid json
        }).fail(function (data) {
            alert(data);
        });
    });
    $('#backofficeFormSubmit').click(function (e) {
        e.preventDefault();
        var btn = $(this);
        btn.attr('disabled', 'disabled');
        if ($('input[name="rid[]"]').length > 0) {
            $.each($('input[name="rid[]"]'), function () {

                var elem = $(this);
                elem.parent().append('<img src="' + SmartKwk.img + '/wpspin_light.gif" alt="loading..."/>');
                $.ajaxQueue({
                    url: SmartKwk.ajaxurl,
                    data: {'action': 'stkwk_api_request', 'rid': elem.val()},
                    type: 'POST',
                    success: function (data) {
                        elem.parent().text(data.message);
                    }
                });
            });
        } else {
            alert('Abfragen bereits durchgeführt.');
        }
    });
    //trigger all "gutschein senden" buttons
    $('#bulk_send_btn').click(function () {

        if (!confirm('Gutscheine jetzt an alle versenden?')) {
            return;
        }

        var btn = $(this);
        if ($('.sendVoucherButton').length > 0) {

            btn.attr('disabled', 'disabled');
            $.each($('.sendVoucherButton'), function () {

                var el = $(this);
                var refid = el.attr('rid');
                el.hide();
                el.parent().append('<img src="' + SmartKwk.img + '/wpspin_light.gif" alt="loading..."/>');
                var postData = {
                    action: 'stkwk_send_voucher',
                    ref: refid,
                };
                $.ajaxQueue({
                    type: "POST",
                    data: postData,
                    dataType: "json",
                    url: SmartKwk.ajaxurl,
                    success: function (response) {
                        el.parent().text(response.message);
                    }
                });
            });
        } else {
            alert('Keine Gutscheine zum Versenden.');
        }
    });
});

