<?php
/**
 * -----------------------------------------------------------------------------------------
 * PDFBill NEXT by Robert Hoppe by Robert Hoppe
 * Copyright 2011 Robert Hoppe - xtcm@katado.com - http://www.katado.com
 *
 * Please visit http://pdfnext.katado.com for newer Versions
 * -----------------------------------------------------------------------------------------
 *
 * Released under the GNU General Public License
 *
 */
function fw_pdf_bill($pdf, $oID, $deliverSlip = false)
{
    // Create Order object from $oID
    $order = new order($oID);

    // Set language for bill/slip

    $language = $order->info['language'];
    if ($language == '') {
        $language = $_SESSION['language'];
    }

    // get language file
    require_once(DIR_FS_CATALOG .'lang/' . $language . '/modules/contribution/pdfbill.php');

    // Get Customers ID
    // START - Innergemeinschaftliche Lieferungen
    /* ORIGINAL
        $sqlGetCustomer = "SELECT customers_id, customers_cid FROM " . TABLE_ORDERS . " WHERE orders_id = '" . (int)$oID . "'";
        $resGetCustomer = xtc_db_query($sqlGetCustomer);
        $rowGetCustomer = xtc_db_fetch_array($resGetCustomer);
    */
    $sqlGetCustomer = "SELECT customers_id, customers_cid, customers_status, customers_vat_id FROM " . TABLE_ORDERS . " WHERE orders_id = '" . (int)$oID . "'";
    $resGetCustomer = xtc_db_query($sqlGetCustomer);
    $rowGetCustomer = xtc_db_fetch_array($resGetCustomer);

    // set real customers_id
    $customers_id_real = $rowGetCustomer['customers_id'];
    $customers_status = $rowGetCustomer['customers_status'];

    // Kunden USTID
    $customers_vat_id = $rowGetCustomer['customers_vat_id'];
    // END - Innergemeinschaftliche Lieferungen

    // use customers_id as the real id?
    if (PDF_USE_CUSTOMER_ID == 'true') {
        $customers_id = $rowGetCustomer['customers_id'];
    } else {
        $customers_id = $rowGetCustomer['customers_cid'];
    }

    // Get customer gender
    $sqlGetGender = "SELECT customers_gender FROM " . TABLE_CUSTOMERS . " WHERE customers_id = '" . (int)$rowGetCustomer['customers_id'] . "'";
    $resGetGender = xtc_db_query($sqlGetGender);
    $rowGetGender = xtc_db_fetch_array($resGetGender);
    $customer_gender = $rowGetGender['customers_gender'];

    // Change Adress on Delivery Slip
    if ($deliverSlip === true) {
        $customer_address = xtc_address_format($order->customer['format_id'], $order->delivery, 1, '', '<br>');
    } else {
        $customer_address = xtc_address_format($order->customer['format_id'], $order->billing, 1, '', '<br>');
    }

    // PDF Address and Logo PDF-Output
    $pdf->Adresse(str_replace("<br>", "\n", $customer_address), TEXT_PDF_SHOPADRESSEKLEIN);
    $pdf->Logo(DIR_FS_CATALOG . 'templates/' . CURRENT_TEMPLATE . '/img/logo_invoice.png');

    // Convert Datum into  tt.mm.jj umwandeln
    //preg_match("/(\d{4})-(\d{2})-(\d{2})\s(\d{2}):(\d{2}):(\d{2})/", $order->info['date_purchased'], $dt);
    //$date_purchased = time(); // Current Date
    //$date_purchased = strtotime($order->info['date_purchased']);
    $date_purchased = isset($order->info['ibn_billdate']) && $order->info['ibn_billdate'] != '0000-00-00' ? xtc_date_short($order->info['ibn_billdate']) :  xtc_date_short($order->info['date_purchased']);

    // Get Payment method
    if ($order->info['payment_method'] != '' && $order->info['payment_method'] != 'no_payment') {
        $paymentFile = DIR_FS_CATALOG . 'lang/' . $language . '/modules/payment/' . $order->info['payment_method'] . '.php';
        include($paymentFile);

        $payment_method = constant(strtoupper('MODULE_PAYMENT_' . $order->info['payment_method'] . '_TEXT_TITLE'));

    }
    // Get ibn_billnr, ibn_billdate and customers vat_id
    $sqlOrder = "
    SELECT
        ibn_billnr,
        ibn_billdate,
        customers_vat_id
    FROM " . TABLE_ORDERS . "
    WHERE
        orders_id = '" . $oID . "'";
    $resOrder = xtc_db_query($sqlOrder);
    $rowOrder = xtc_db_fetch_array($resOrder);
    $order_bill = 'I-' . sprintf('%06d', $oID);; //$rowOrder['ibn_billnr'];
    //$order_billdate = $rowOrder['ibn_billdate'];
    $order_billdate = $date_purchased;
    $order_vat_id = $rowOrder['customers_vat_id'];

    $fwBillNo = 'I-' . sprintf('%06d', $oID);
    $fwOrderNo = 'B-' . sprintf('%06d', $oID);
    $fwCustomerNo = 'K-' . sprintf('%06d', $customers_id_real);

    // Create Bill Data
    if ($deliverSlip === true) {
        //$pdf->Rechnungsdaten($customers_id, $order_bill, $oID, date("d.m.Y"), $payment_method, $order_vat_id, $deliverSlip);
        $pdf->Rechnungsdaten($fwCustomerNo, $fwBillNo, $fwOrderNo, date("d.m.Y"), $payment_method, $order_vat_id, $deliverSlip);
    } else {
        //$pdf->Rechnungsdaten($customers_id, $order_bill, $oID, xtc_date_short($order_billdate), $payment_method, $order_vat_id, $deliverSlip);
        //$pdf->Rechnungsdaten($customers_id, $order_bill, $oID, $order_billdate, $payment_method, $order_vat_id, $deliverSlip);
        $pdf->Rechnungsdaten($fwCustomerNo, $fwBillNo, $oID, $order_billdate, $payment_method, $order_vat_id, $deliverSlip);
    }
    $pdf->RechnungStart($order->customer['lastname'], $customer_gender, $deliverSlip);

    // add BillPay Support
    if($order->info['payment_method'] == 'billpay' || $order->info['payment_method'] == 'billpaydebit') {
        // we need a workaround for billpay because its expecting $_GET['oid']
        if (!isset($_GET['oID']) || $_GET['oID'] != $oID) {
            // save for compatibility reasons oID
            if (isset($_GET['oID']) && is_numeric($_GET['oID'])) {
                $oldOID = $_GET['oID'];
            }

            // overwrite GET - shame on this
            $_GET['oID'] = $oID;
        }

        // get billpay stuff - uncomment the first line if you're having require-problems
        #require_once(DIR_FS_CATALOG . DIR_WS_INCLUDES . '/billpay/utils/billpay_display_pdf_data.php');
        require_once(DIR_FS_EXTERNAL . 'billpay/utils/billpay_display_pdf_data.php');

        // restore oID for compatibility reasons
        if (isset($oldOID)) {
            $_GET['oID'] = $oldOID;
            unset($oldOID);
        }
    }

    $pdf->ListeKopf($deliverSlip);

    // Product Informations
    $sqlProdInfos = "
    SELECT
        products_id,
        orders_products_id,
        products_model,
        products_name,
        products_order_description,
        products_price,
        final_price,
        products_quantity
    FROM " . TABLE_ORDERS_PRODUCTS."
    WHERE orders_id = '" . (int)$oID . "'";
    $resProdInfos = xtc_db_query($sqlProdInfos);

    // init order_data
    $order_data = array();

    // Add Products with attributes to PDF
    while ($order_data_values = xtc_db_fetch_array($resProdInfos)) {
        $sqlAttributes = "
        SELECT
            products_options,
            products_options_values,
            price_prefix,
            options_values_price
        FROM " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . "
        WHERE orders_products_id = '" . $order_data_values['orders_products_id'] . "'";
        $resAttributes = xtc_db_query($sqlAttributes);

        // init attribute strings
        $attributes_data = '';
        $attributes_model = '';

        // fetch attributes
        while ($attributes_data_values = xtc_db_fetch_array($resAttributes)) {
            $attributes_data .= $attributes_data_values['products_options'] . ': ' . $attributes_data_values['products_options_values'] . "\n";
            $attributes_model .= xtc_get_attributes_model (
                $order_data_values['products_id'],
                $attributes_data_values['products_options_values'],
                $attributes_data_values['products_options']
            )."\n";
            
        }

        // Deliverslip is without price
        if ($deliverSlip == true) {
            $pdf->ListeProduktHinzu (
                $order_data_values['products_quantity'],
                $order_data_values['products_name'],
                //trim(str_replace("■", "",(html_entity_decode(strip_tags($order_data_values['products_order_description']))))),
                trim(html_entity_decode($attributes_data)),
                $order_data_values['products_model'],
                trim(html_entity_decode($attributes_model)),
                '',
                ''
            );
        } else {
            // get truncate length of product_model
            if (is_numeric(PDF_PRODUCT_MODEL_LENGTH) && PDF_PRODUCT_MODEL_LENGTH > 0) {
                $truncAfter = PDF_PRODUCT_MODEL_LENGTH;
            } else {
                $truncAfter = 7;
            }

            $pdf->ListeProduktHinzu(
                $order_data_values['products_quantity'],
                $order_data_values['products_name'],
                trim(html_entity_decode($attributes_data)),
                // cut product_model to defined length
                substr($order_data_values['products_model'], 0, $truncAfter),
                trim(html_entity_decode($attributes_model)),
                xtc_format_price_order($order_data_values['products_price'], 1, $order->info['currency']),
                xtc_format_price_order($order_data_values['final_price'], 1, $order ->info['currency'])
            );
        }
    }

    // init order_data for order total
    $order_data = array();

    // dont show price on packaging slip
    if ($deliverSlip == false) {
        // Add Total to PDF
        $sqlOrderTotal = "
        SELECT
            title,
            text,
            class,
            value,
            sort_order
        FROM " . TABLE_ORDERS_TOTAL . "
        WHERE orders_id = '" . (int)$oID . "'
        ORDER BY sort_order ASC";
        $resOrderTotal = xtc_db_query($sqlOrderTotal);


        // fetch order data
        while ($oder_total_values = xtc_db_fetch_array($resOrderTotal)) {
            $order_data[] = array (
                'title' => xtc_utf8_decode(html_entity_decode($oder_total_values['title'])),
                'class'=> $oder_total_values['class'],
                'value'=> $oder_total_values['value'],
                'text' => xtc_utf8_decode($oder_total_values['text'])
            );
        }
    }

    // Generate PDF
    $pdf->Betrag($order_data);
    $pdf->RechnungEnde($deliverSlip);

    // START - Innergemeinschaftliche Lieferungen
    //BOC EU TEXT by customers groups - www.rpa-com.de
    if ($deliverSlip == false) {
      $eu_customer_groups_arr = array();
      if (defined('PDF_BILL_EU_CUSTOMERS_GROUP_ID')) {
        $eu_customer_groups_ids  = preg_replace("'[\r\n\s]+'",'',PDF_BILL_EU_CUSTOMERS_GROUP_ID);
        $eu_customer_groups_arr = explode(',',$eu_customer_groups_ids);
      }
      if (count($eu_customer_groups_arr) && in_array($customers_status,$eu_customer_groups_arr) && trim($customers_vat_id) != '') {
        $pdf->TextEU($customers_vat_id);
      }
    }
    //EOC EU TEXT  by customers groups - www.rpa-com.de
    // END - Innergemeinschaftliche Lieferungen

    $pdf->Kommentar(xtc_utf8_decode($order->info['comments']));

    // Generate into given Directory
    if (!$deliverSlip) {
        $filePrefix = PDF_FILENAME;
    } else {
        $filePrefix = PDF_FILENAME_SLIP;
    }

    // replace Variables for filePrefix
    $filePrefix = trim($filePrefix);
    $filePrefix = str_replace('{oID}', $oID, $filePrefix);
    $filePrefix = str_replace('{bill}', $order_bill, $filePrefix);
    $filePrefix = str_replace('{cID}', $customers_id, $filePrefix);
    $filePrefix = str_replace(' ', '_', $filePrefix);
    if ($filePrefix == '') $filePrefix = $oID;

    // Filename for BILL or SLIP
    $filename = DIR_FS_DOCUMENT_ROOT.DIR_ADMIN  . 'invoice/' . $filePrefix . '.pdf';

    return $pdf;
}

?>
