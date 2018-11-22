<?php
require ('includes/application_top.php');
require_once (DIR_FS_INC.'get_order_total.inc.php');
require_once (DIR_FS_INC.'xtc_utf8_decode.inc.php');
require_once (DIR_FS_INC.'xtc_format_price_order.inc.php');
require_once (DIR_FS_INC.'xtc_get_attributes_model.inc.php');


require DIR_WS_CLASSES . 'order.php';
require_once DIR_FS_DOCUMENT_ROOT . 'admin/fw_pdf_bill.php';
require_once DIR_FS_CATALOG . 'includes/classes/FPDF/PdfRechnung.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function fwCreateMultiBillOrSlip($orderIds, $mode = 0)
{
    // Create PDF Object
    $pdf = new PdfRechnung();
    $pdf->Init("Rechnung");

    if ($mode == 0) {
        $prefix = 'Rechnung_';

        $count = 0;
        foreach ($orderIds as $orderId) {
            fw_pdf_bill($pdf, $orderId, false);
            if (++$count < count($orderIds)) {
                $pdf->AddPage();
            }
        }

    } elseif ($mode == 1) {
        $prefix = 'Lieferschein_';

        $count = 0;
        foreach ($orderIds as $orderId) {
            fw_pdf_bill($pdf, $orderId, true);
            if (++$count < count($orderIds)) {
                $pdf->AddPage();
            }
        }

    } elseif ($mode == 2) {
        $prefix = 'Rechnung_Lieferschein_';

        //2x Lieferschein
        $count = 0;
        foreach ($orderIds as $orderId) {
            fw_pdf_bill($pdf, $orderId, true);
            $pdf->AddPage();
            fw_pdf_bill($pdf, $orderId, true);
            if (++$count < count($orderIds)) {
                $pdf->AddPage();
            }
        }
        $pdf->AddPage();

        //2x Rechnung
        $count = 0;
        foreach ($orderIds as $orderId) {
            fw_pdf_bill($pdf, $orderId, false);
            $pdf->AddPage();
            fw_pdf_bill($pdf, $orderId, false);
            if (++$count < count($orderIds)) {
                $pdf->AddPage();
            }
        }
    } elseif ($mode == 3) {
        $prefix = 'Rechnung_Lieferschein_Mixed_';

        //2x Lieferschein
        $count = 0;
        foreach ($orderIds as $orderId) {
            fw_pdf_bill($pdf, $orderId, true);
            $pdf->AddPage();
            fw_pdf_bill($pdf, $orderId, false);
            if (++$count < count($orderIds)) {
                $pdf->AddPage();
            }
        }
    }

    // Filename and output
    $firstOrderId = $orderIds[0];
    $lastOrderId  = array_pop($orderIds);
    $filename  = '/admin/invoice/' . $prefix . $firstOrderId . '-' . $lastOrderId . '.pdf';
    $pdf->Output(DIR_FS_DOCUMENT_ROOT . $filename , 'F');

    echo '
        <script>
            window.open("' . $filename . '", "Bill Window - OrderId: ' . $orderId . '", "width=380, height=550");
        </script>
    ';
}


function fwGetPaymentName($paymentMethod, $orderId)
{
    $filePath = DIR_FS_CATALOG . 'lang/' . $_SESSION['language'] . '/modules/payment/' . $paymentMethod . '.php';
    if (file_exists($filePath)) {
        include($filePath);
        $result = constant(strtoupper('MODULE_PAYMENT_' . $paymentMethod . '_TEXT_TITLE'));
    } else {
        $result = $paymentMethod;
    }

    $addOn = '';
    if ($payment_method == 'paypalplus' && (int) $orderId > 0) {
        require_once(DIR_FS_EXTERNAL.'paypal/classes/PayPalInfo.php');
        $paypal = new PayPalInfo($paymentMethod);
        $paymentArray = $paypal->get_payment_data($orderId);
        if (count($paymentArray) > 0 && $paymentArray['payment_method'] == 'pay_upon_invoice') {
            $addOn = ' - ' . MODULE_PAYMENT_PAYPALPLUS_INVOICE;
        }
    }

    return $result . $addOn;
}

function fwRenderJsPdfBills($billIds)
{
    foreach($billIds as $orderId) {
        echo '
            <script>
                window.open("/admin/print_order_pdf.php?oID=' . $orderId . '&download=1", "Bill Window - OrderId: ' . $orderId . '", "width=380, height=550");
            </script>
        ';
    }
}

function fwRenderJsPdfDeliveryNote($billIds)
{
    foreach($billIds as $orderId) {
        echo '
            <script>
                window.open("/admin/print_packingslip_pdf.php?oID=' . $orderId . '&download=1", "Packing Window - OrderId: ' . $orderId . '", "width=380, height=550");
                window.open("/admin/invoice/SomeSlip' . $orderId . '.pdf", "Packing Window - OrderId: ' . $orderId . '", "width=380, height=550");
            </script>
        ';
    }
}


function fwSendPostRequest($url, $data)
{
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n" .
                         "Cookie: MODsid=" . $_COOKIE['MODsid'] . "\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        )
    );

    $context  = stream_context_create($options);
    // echo '<pre>';
    // var_dump($data);
    // echo '<br>';
    // die('FIRST-WEB TEST');

    $result = file_get_contents($url, false, $context);
    return $result;
}

function fwGetTrackingIds($orderId)
{
    $sql = "SELECT * FROM orders_tracking WHERE orders_id='$orderId'";
    $query = xtc_db_query($sql);

    $trackingIds = array();
    while ($row = xtc_db_fetch_array($query)) {
        $trackingIds[] = $row['tracking_id'];
    }

    return $trackingIds;
}

function fwGetOrdersTracking($trackingId)
{
    $sql = "SELECT * FROM orders_tracking WHERE tracking_id='$trackingId'";
    $query = xtc_db_query($sql);

    $row = xtc_db_fetch_array($query);
    return $row;
}


function fwGetAllStatusTemplates()
{
    $sql = "SELECT * FROM fw_status_template";
    $query = xtc_db_query($sql);
    $statusTemplates = [];
    while ($row = xtc_db_fetch_array($query)) {
        $statusTemplates[] = $row;
    }
    return $statusTemplates;
}

function fwGetStatusTemplate($id)
{
    $id = (int) $id;
    $sql = "SELECT * FROM fw_status_template WHERE id=" . $id;
    $query = xtc_db_query($sql);
    $row = xtc_db_fetch_array($query);
    return $row;

}

function fwUpdateOrderStatus($orderId, $statusId, $notify, $sendComment, $sendTrackingLink, $comments = '')
{
    if ($statusId >= 0) {
        $data['status'] = $statusId;
    }

    // Soll der Kunde über die Statusänderung informiert werden.
    if ($notify == 'yes') {
        $data['notify'] = 'on';
    }

    // Soll bei einer Benachrichtigung an den Kunden der Kommentar mitgesendet
    // werden?
    if ($sendComment == 'yes') {
        if ($comments) {
            $data['notify_comments'] = 'on';
            $data['comments'] = $comments;
        }
    }

    //Soll der Tracking-Code mitgesendet werden. Eine bestellung kann theoretisch mehere Tracking-Codes
    //haben, es werden immer alle Codes pro Bestellung versendet.
    if ($sendTrackingLink == 'yes') {
        $trackingIds = fwGetTrackingIds($orderId);
        $data['tracking_id'] = $trackingIds;

        $ordersTracking = fwGetOrdersTracking($trackingIds[0]);
        $data['magna']['trackingcode'] = $ordersTracking['parcel_id'];
        $data['magna']['carriercode'] = 'DPD';
    }

    $url = HTTPS_SERVER . '/admin/orders.php?oID=' . $orderId . '&action=update_order';

    //var_dump($data);
    //die('Nichts geändert');

    $result = fwSendPostRequest($url, $data);
    return $result;
}

function fwUpdateOrdersStatus()
{
    $error = '';
    foreach($_POST['orderIds'] as $orderId) {
        $statusId = $_POST['orderStatus'];

        if ($statusId > 0) {
            $notifyCustomer = 'no';
            $sendTrackingLink = 'no';

            if ($_POST['notifyCustomer'] == 'yes') {
                $notifyCustomer = 'yes';
                $sendTrackingLink = 'no';
            } elseif ($_POST['notifyCustomer'] == 'yes-code') {
                $notifyCustomer = 'yes';
                $sendTrackingLink = 'yes';
            }

            $statusTemplate = fwGetStatusTemplate($_POST['status-template']);
            if ($statusTemplate['text']) {
                $comments = $statusTemplate['text'];
            }

            $result = fwUpdateOrderStatus($orderId, $statusId, $notifyCustomer, 'yes', $sendTrackingLink, $comments);
            if (!$result) {
                $error .= 'Status von Bestellung ' . $orderId . ' konnte nicht geändert werden.<br>';
            }
        }
    }
    return $error;
}

$fwMaxDisplayDpdExportResults = 'FW_MAX_DISPLAY_MULTI_ORDER_RESULTS';
$pageMaxDisplayResults = xtc_cfg_save_max_display_results($fwMaxDisplayDpdExportResults);

if (!empty($_POST['page'])) {
    $_GET['page'] = $_POST['page'];
}

$orderStatusIdSelected = -1;
if (isset($_GET['statusIdFilter'])) {
    $orderStatusIdSelected = $_GET['statusIdFilter'];
    $_SESSION['fw_multi_order_status_id_filter'] = $orderStatusIdSelected;
} elseif ($_SESSION['fw_multi_order_status_id_filter']) {
    $orderStatusIdSelected = $_SESSION['fw_multi_order_status_id_filter'];
}

$orderCustomerFilter = '';
if (isset($_GET['customerFilter'])) {
    $orderCustomerFilter = $_GET['customerFilter'];
    $_SESSION['fw_multi_order_customer_filter'] = $orderCustomerFilter;
} elseif ($_SESSION['fw_multi_order_customer_filter']) {
    $orderCustomerFilter = $_SESSION['fw_multi_order_customer_filter'];
}


if ($_POST['fwAction'] == 'bills') {
    fwCreateMultiBillOrSlip($_POST['orderIds'], 0);
} elseif ($_POST['fwAction'] == 'deliveryNote') {
    fwCreateMultiBillOrSlip($_POST['orderIds'], 1);
} elseif ($_POST['fwAction'] == 'bills_notes') {
    fwCreateMultiBillOrSlip($_POST['orderIds'], 2);
} elseif ($_POST['fwAction'] == 'bills_notes_mixed') {
    fwCreateMultiBillOrSlip($_POST['orderIds'], 3);
} elseif ($_POST['fwAction'] == 'changeOrderStatus') {
    $error = fwUpdateOrdersStatus();
}



// Alle Bestellstatus-Moeglichkeiten aus der Datebank abfragen
$orderStatusQueryRaw = "SELECT * FROM " . TABLE_ORDERS_STATUS . " WHERE language_id = '" . (int) $_SESSION['languages_id'] . "'";
$orderStatusQuery = xtc_db_query($orderStatusQueryRaw);
$orderStatus = array();
while ($orderStatusRow = xtc_db_fetch_array($orderStatusQuery)) {
    $orderStatus[$orderStatusRow['orders_status_id']] = $orderStatusRow['orders_status_name'];
}

$orderStatusForPullDown = [];
$orderStatusForPullDown[] = [
    'id' => -1,
    'text' => 'nicht gefiltert'
];

foreach($orderStatus as $id => $text) {
    $orderStatusForPullDown[] = [
        'id' => $id,
        'text' => $text
    ];
}

// Orders / Bestellungen ermittlen
$ordersQueryRaw = "SELECT * FROM " . TABLE_ORDERS;
$ordersQueryRaw .= ' WHERE 1=1 ';
if ($orderStatusIdSelected >= 0) {
    $ordersQueryRaw .= " AND orders_status = '$orderStatusIdSelected'";
}

if ($orderCustomerFilter) {
    $ordersQueryRaw .= " AND customers_name LIKE '%$orderCustomerFilter%' OR customers_company LIKE '%$orderCustomerFilter%' OR customers_id LIKE '%$orderCustomerFilter%'";
}

$ordersQueryRaw .= ' ORDER BY orders_id DESC';

echo $ordersQueryRaw;
$split = new splitPageResults($_GET['page'], $pageMaxDisplayResults, $ordersQueryRaw, $orders_query_numrows);
$ordersQuery = xtc_db_query($ordersQueryRaw);

$orderDatas = array();
while ($order = xtc_db_fetch_array($ordersQuery)) {
    $orderDatas[] = array(
        'id' => $order['orders_id'],
        'customerName' => $order['customers_name'],
        'customersCompany' => $order['customers_company'],
        'orderNumber' => $order['orders_id'],
        'county' => $order['delivery_country'],
        'totalPrice' => format_price(get_order_total($order['orders_id']), 1, $order['currency'], 0, 0),
        'orderDate' => $order['date_purchased'],
        'paymentMethod' => $order['payment_class'],
        'status' => $orderStatus[$order['orders_status']]
    );
}


require (DIR_WS_INCLUDES . 'head.php');
?>
    <style type="text/css">
        .table {
            width: 100%;
            border: 1px solid #a3a3a3;
            margin-bottom:20px;
            background: #f3f3f3;
            padding:2px;
        }

        .heading {
            font-family: Verdana, Arial, sans-serif;
            font-size: 12px;
            font-weight: bold;
            padding:2px;
        }

        .last_row {
            background-color: #ffdead;
        }

        .error-message {
            margin: 10px 5px 10px 5px;
            padding: 10px;
            border: 2px solid red;
        }

        textarea#comments {
            width:99%;
        }
    </style>
</head>

<body>
    <!-- header //-->
    <?php require(DIR_WS_INCLUDES . 'header.php'); ?>
    <!-- header_eof //-->

    <!-- body //-->
    <?php if ($error) { ?>
        <div class="error-message">
            Fehler: <?php echo $error; ?>
        </div>
    <?php } ?>

    <script>
        function fwToggleSelection(element)
        {
            checkboxes = document.getElementsByClassName('selectCheckbox');

            //console.log(checkboxes);

            for (var i = 0; i<checkboxes.length; i++) {
                if (element.checked) {
                    checkboxes[i].checked = true;
                } else {
                    checkboxes[i].checked = false;
                }
            }
        }
    </script>

    <table class="tableBody">
        <tr>
            <!-- body_text //-->
            <td class="boxCenter">
                <div class="pageHeadingImage">
                    <?php echo xtc_image(DIR_WS_ICONS . 'heading/fw_multi_order.png'); ?>
                </div>

                <div class="pageHeading flt-l">
                    <?php echo HEADING_TITLE; ?>
                    <div class="main pdg2">
                        <?php echo TABLE_HEADING_CUSTOMERS ?>
                    </div>
                </div>

                <div class="main flt-l pdg2 mrg5" style="margin-left:20px;">
                    <?php echo xtc_draw_form('status', 'fw_multi_order.php', '', 'get'); ?>
                        <?php echo 'Kundenfilter: ' . xtc_draw_input_field('customerFilter', $orderCustomerFilter, 'size="12"') ?>
                    </form>
                </div>

                <div class="main flt-l pdg2 mrg5" style="margin-left:20px;">
                    <?php echo xtc_draw_form('status', 'fw_multi_order.php', '', 'get'); ?>
                        <?php echo 'Statusfilter:'; ?>
                        <?php echo xtc_draw_pull_down_menu (
                            'statusIdFilter',
                            $orderStatusForPullDown,
                            $orderStatusIdSelected,
                            'onchange="this.form.submit();"'
                        ); ?>
                    </form>
                </div>

                <!-- <form method="post" action=""> -->
                <?php echo xtc_draw_form('orders', 'fw_multi_order.php', '', 'post'); ?>
                    <input id="fwAction" type="hidden" name="fwAction" value="">
                    <table class="tableCenter">
                        <tr>
                            <td class="boxCenterLeft">
                                <table class="tableBoxCenter collapse">
                                     <tr class="dataTableHeadingRow">
                                        <td class="dataTableHeadingContent"><input type="checkbox" onclick="fwToggleSelection(this);"></td>
                                        <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_CUSTOMERS; ?></td>
                                        <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ORDERS_ID; ?></td>
                                        <td class="dataTableHeadingContent" align="right" style="width:120px"><?php echo TEXT_SHIPPING_TO; ?></td>
                                        <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ORDER_TOTAL; ?></td>
                                        <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_DATE_PURCHASED; ?></td>
                                        <td class="dataTableHeadingContent" align="center"><?php echo str_replace(':','',TEXT_INFO_PAYMENT_METHOD); ?></td>
                                        <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_STATUS; ?></td>
                                    </tr>

                                    <?php foreach($orderDatas as $orderData) {
                                        $name = $orderData['customerName'];
                                        if ($orderData['customersCompany']) {
                                            $name .= ' - ' . $orderData['customersCompany'];
                                        }
                                        ?>
                                        <tr class="dataTableRow">
                                            <td class="dataTableContent">
                                                <?php
                                                // First-Web-Ticket: #0000279 - start
                                                $fwSelected = '';
                                                if (in_array($orderData['id'], $_POST['orderIds'])) {
                                                    $fwSelected = 'checked';
                                                }
                                                // First-Web-Ticket: #0000279 - end
                                                ?>
                                                <input class="selectCheckbox" name="orderIds[]" type="checkbox" value="<?php echo $orderData['id'] ?>" <?php echo $fwSelected; ?> >
                                            </td>
                                            <td class="dataTableContent"><?php echo $name ?></td>
                                            <td class="dataTableContent" align="right"><?php echo $orderData['orderNumber'] ?></td>
                                            <td class="dataTableContent" align="right"><?php echo $orderData['county'] ?></td>
                                            <td class="dataTableContent" align="right"><?php echo $orderData['totalPrice'] ?></td>
                                            <td class="dataTableContent" align="center"><?php echo xtc_datetime_short($orderData['orderDate']) ?></td>
                                            <td class="dataTableContent" align="center"><?php echo fwGetPaymentName($orderData['paymentMethod'], $orderData['id']) ?></td>
                                            <td class="dataTableContent" align="right"><?php echo $orderData['status'] ?></td>
                                        </tr>
                                    <?php } ?>
                                </table>

                                <div class="smallText pdg2 flt-l">
                                    <?php
                                        echo $split->display_count(
                                            $orders_query_numrows,
                                            $pageMaxDisplayResults,
                                            $_GET['page'],
                                            TEXT_DISPLAY_NUMBER_OF_ORDERS
                                        );
                                    ?>
                                </div>


                                <div class="smallText pdg2 flt-r">
                                    <?php
                                        echo $split->display_links(
                                            $orders_query_numrows,
                                            $pageMaxDisplayResults,
                                            MAX_DISPLAY_PAGE_LINKS,
                                            $_GET['page'],
                                            xtc_get_all_get_params(array('page', 'oID', 'action'))
                                        );
                                    ?>
                                </div>

                                <?php echo draw_input_per_page($PHP_SELF, $fwMaxDisplayDpdExportResults, $pageMaxDisplayResults); ?>

                            </td>

                            <td class="boxRight">
                                <table class="contentTable">
                                    <tbody>
                                        <tr class="infoBoxHeading">
                                            <td class="infoBoxHeading">
                                                <div class="infoBoxHeadingTitle">
                                                    <b>Aktion</b>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <style>
                                    .action-separator {
                                        margin-top: 18px;
                                        border-bottom: 2px solid #B3417B;
                                    }

                                    .fw-input {
                                        width: 100%;
                                    }
                                </style>

                                <table class="contentTable">
                                    <tbody>
                                        <tr class="infoBoxContent">
                                            <td style="text-align:center;" class="infoBoxContent">
                                                <input type="submit" class="button fw-input" onclick="document.getElementById('fwAction').value='bills'; this.blur();" value="Rechnungen PDF ...">

                                                <input type="submit" class="button fw-input" onclick="document.getElementById('fwAction').value='deliveryNote'; this.blur();" value="Lieferschein PDF ...">

                                                <input type="submit" class="button fw-input" onclick="document.getElementById('fwAction').value='bills_notes'; this.blur();" value="Lieferschein & Rechnung PDF ...">

                                                <input type="submit" class="button fw-input" onclick="document.getElementById('fwAction').value='bills_notes_mixed'; this.blur();" value="Lieferschein & Rechnung PDF Mixed ...">
                                                <div class="action-separator"></div>
                                            </td>
                                        </tr>

                                        <tr class="infoBoxContent">
                                            <td style="text-align:center;" class="infoBoxContent">
                                                <select name="orderStatus" class="fw-input">
                                                    <option value="-1">Status nicht ändern</option>
                                                    <?php foreach($orderStatus as $id => $name) {
                                                        echo '<option value="' . $id . '">' . $name . '</option>';
                                                    }?>
                                                </select>
                                                <br>

                                                <select name="notifyCustomer" class="fw-input">
                                                    <option value="no">Kunde nicht benachrichtigen</option>
                                                    <option value="yes">Kunde benachrichtigen ohne Trackingcode</option>
                                                    <option value="yes-code">Kunde benachrichtigen inkl. Trackingcode</option>
                                                </select>
                                                <br>

                                                <select name="status-template" class="fw-input">
                                                    <option value="0">Kommentar nicht mitsenden</option>
                                                    <?php foreach(fwGetAllStatusTemplates() as $statusTemplate) {
                                                        echo '<option value="' . $statusTemplate['id'] . '">' . $statusTemplate['title'] . '</option>';
                                                    } ?>
                                                </select>
                                                <br>

                                                <input type="submit" class="button fw-input" onclick="document.getElementById('fwAction').value='changeOrderStatus'; this.blur();" value="Status ändern">
                                                <div class="action-separator"></div>
                                            </td>
                                        </tr>

                                        <tr class="infoBoxContent">
                                            <td class="infoBoxContent"><br>Mit diesem Modul von First-Web können Sie bei allen ausgewählten Bestellungen gleichzeitig den Status ändern oder die Rechnungen drucken lassen.</td>
                                        </tr>


                                    </tbody>
                                </table>
                            </td>

                        </tr>
                    </table>
                </form>
            </td>
            <!-- body_text_eof //-->
        </tr>
    </table>



    <!-- body_eof //-->

    <!-- footer //-->
    <?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
    <!-- footer_eof //-->
    <br />
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
