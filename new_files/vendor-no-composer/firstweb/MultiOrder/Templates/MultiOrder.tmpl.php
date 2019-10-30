<?php require (DIR_WS_INCLUDES . 'head.php'); ?>
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

                <div style="clear: both"></div>

                <div class="main flt-l pdg2 mrg5" style="margin-left: 2px;">
                    <?php echo xtc_draw_form('status', 'fw_multi_order.php', '', 'get'); ?>
                        <?php echo 'Kundenfilter: ' . xtc_draw_input_field('customerFilter', $orderCustomerFilter, 'size="12"') ?>
                    </form>
                </div>

                <div class="main flt-l pdg2 mrg5" style="margin-left:20px;">
                    <?php echo xtc_draw_form('status', 'fw_multi_order.php', '', 'get'); ?>
                        <?php echo 'Artikelnr.: ' . xtc_draw_input_field('productFilter', $orderProductFilter, 'size="12"') ?>
                    </form>
                </div>

                <div class="main flt-l pdg2 mrg5" style="margin-left:-8px; margin-top: 10px;">
                    <?php echo xtc_draw_form('status', 'fw_multi_order.php', '', 'get'); ?>
                        <?php echo xtc_draw_pull_down_menu (
                            'productModeFilter',
                            [['id' => '1','text' => 'Produkt auch enthalten'], ['id' => '2', 'text' => 'nur dieses Produkt enthalten']],
                            $orderProductModeSelected,
                            'onchange="this.form.submit();"'
                        ); ?>
                    </form>
                </div>

                <div class="main flt-l pdg2 mrg5" style="margin-left:20px; margin-top: 10px;">
                    <?php echo xtc_draw_form('status', 'fw_multi_order.php', '', 'get'); ?>
                        <div style="margin-right: 3px; margin-top: 3px; float: left">Status:</div>
                        <?php echo xtc_draw_pull_down_menu (
                            'statusIdFilter',
                            $orderStatusForPullDown,
                            $orderStatusIdSelected,
                            'onchange="this.form.submit();"'
                        ); ?>
                    </form>
                </div>

                <div class="main flt-l pdg2 mrg5" style="margin-left:-8px; margin-top: 10px;">
                    <?php echo xtc_draw_form('status', 'fw_multi_order.php', '', 'get'); ?>
                    <div style="margin-left: 10px; margin-right: 3px; margin-top: 3px; float: left">Typ:</div>
                        <?php echo xtc_draw_pull_down_menu (
                            'orderTypeFilter',
                            [['id' => '-1','text' => 'nicht gefiltert'],
                            ['id' => '100', 'text' => 'Amazon (Magnalister)'],
                            ['id' => '101', 'text' => 'Amazon Prime (Magnalister)'],
                            ['id' => '102', 'text' => 'Amazon Business (Magnalister)'],
                            ['id' => '200', 'text' => 'eBay (Magnalister)'],
                            ['id' => '300', 'text' => 'Rakuten (Magnalister)']
                            ],
                            $orderTypeSelected,
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
                                        <td class="dataTableHeadingContent" align="right"><?php echo 'Typ'; ?></td>
                                        <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_STATUS; ?></td>
                                        <td class="dataTableHeadingContent" align="right"><?php echo 'Aktion'; ?></td>
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
                                                $fwSelected = '';
                                                if (is_array($_POST['orderIds'])) {
                                                    if (in_array($orderData['id'], $_POST['orderIds'])) {
                                                        $fwSelected = 'checked';
                                                    }
                                                }
                                                ?>
                                                <input class="selectCheckbox" name="orderIds[]" type="checkbox" value="<?php echo $orderData['id'] ?>" <?php echo $fwSelected; ?> >
                                            </td>
                                            <td class="dataTableContent"><?php echo $name ?></td>
                                            <td class="dataTableContent" align="right"><?php echo $orderData['orderNumber'] ?></td>
                                            <td class="dataTableContent" align="right"><?php echo $orderData['county'] ?></td>
                                            <td class="dataTableContent" align="right"><?php echo $orderData['totalPrice'] ?></td>
                                            <td class="dataTableContent" align="center"><?php echo xtc_datetime_short($orderData['orderDate']) ?></td>
                                            <td class="dataTableContent" align="center"><?php echo $multiOrder->getPaymentName($orderData['paymentMethod'], $orderData['id']) ?></td>
                                            <td class="dataTableContent" align="right"><?php echo $orderData['type'] ?></td>
                                            <td class="dataTableContent" align="right"><?php echo $orderData['status'] ?></td>

                                            <td class="dataTableContent" align="right">
                                                <a href="/admin/orders.php?oID=<?php echo $orderData['id'] ?>&action=edit">
                                                    <img src="images/icons/icon_edit.gif" alt="Bearbeiten" title="Bearbeiten" style="border:0;">
                                                </a>
                                            </td>
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

                                <?php echo draw_input_per_page('/admin/fw_multi_order.php', 'FW_MAX_DISPLAY_MULTI_ORDER_RESULTS', $pageMaxDisplayResults); ?>

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

                                                <input type="submit" class="button fw-input" onclick="document.getElementById('fwAction').value='deliveryNotes'; this.blur();" value="Lieferschein PDF ...">


                                                <input type="submit" class="button fw-input" onclick="document.getElementById('fwAction').value='billsAndDeliveryNotes'; this.blur();" value="Lieferschein & Rechnung PDF ...">

                                                <input type="submit" class="button fw-input" onclick="document.getElementById('fwAction').value='billsAndDeliveryNotesMixed'; this.blur();" value="Lieferschein & Rechnung PDF Mixed ...">

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

                                                <!--
                                                <select name="status-template" class="fw-input">
                                                    <option value="0">Kommentar nicht mitsenden</option>
                                                    <?php foreach($dbHelper->getAllStatusTemplates() as $statusTemplate) {
                                                        echo '<option value="' . $statusTemplate['id'] . '">' . $statusTemplate['title'] . '</option>';
                                                    } ?>
                                                </select>
                                                <br>
                                                !-->

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
