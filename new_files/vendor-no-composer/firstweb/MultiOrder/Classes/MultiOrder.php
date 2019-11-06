<?php
namespace FirstWeb\MultiOrder\Classes;

use FirstWeb\MultiOrder\Classes\DbHelper;

class MultiOrder
{
    public function updateAllOrders($orderIds, $statusId, $notifyCustomer, $statusTemplateId)
    {
        $error = '';
        foreach($orderIds as $orderId) {

            if ($statusId > 0) {
                $notify = 'no';
                $sendTrackingLink = 'no';

                if ($notifyCustomer == 'yes') {
                    $notify = 'yes';
                    $sendTrackingLink = 'no';
                } elseif ($notifyCustomer == 'yes-code') {
                    $notify = 'yes';
                    $sendTrackingLink = 'yes';
                }

                if ($statusTemplateId) {
                    $dbHelper = new DbHelper();
                    $statusTemplate = $dbHelper->getStatusTemplate($statusTemplateId);
                    if ($statusTemplate['text']) {
                        $comments = $statusTemplate['text'];
                    }
                }

                $result = $this->updateOrderStatus($orderId, $statusId, $notify, 'yes', $sendTrackingLink, $comments);
                if (!$result) {
                    $error .= 'Status von Bestellung ' . $orderId . ' konnte nicht geändert werden.<br>';
                }
            }
        }
        return $error;
    }

    public function updateOrderStatus($orderId, $statusId, $notify, $sendComment, $sendTrackingLink, $comments = '')
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
            $dbHelper = new DbHelper();
            $trackingIds = $dbHelper->getTrackingIds($orderId);
            $data['tracking_id'] = $trackingIds;

            $ordersTracking = $dbHelper->getOrdersTracking($trackingIds[0]);
            $data['magna']['trackingcode'] = $ordersTracking['parcel_id'];
            $data['magna']['carriercode'] = 'DPD';
        }

        $data[$_SESSION['CSRFName']] = $_SESSION['CSRFToken'];
        $url = HTTP_SERVER . '/admin/orders.php?oID=' . $orderId . '&action=update_order&fw_multi_order=true';
        $result = $this->sendPostRequest($url, $data);
        return $result;
    }

    public function sendPostRequest($url, $data)
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

        $result = file_get_contents($url, false, $context);
        return $result;
    }

    public function getPaymentName($paymentMethod, $orderId)
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
            require_once(DIR_FS_EXTERNAL . 'paypal/classes/PayPalInfo.php');
            $paypal = new \PayPalInfo($paymentMethod);
            $paymentArray = $paypal->get_payment_data($orderId);
            if (count($paymentArray) > 0 && $paymentArray['payment_method'] == 'pay_upon_invoice') {
                $addOn = ' - ' . MODULE_PAYMENT_PAYPALPLUS_INVOICE;
            }
        }

        return $result . $addOn;
    }
}
