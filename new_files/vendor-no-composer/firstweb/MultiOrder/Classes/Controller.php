<?php
namespace FirstWeb\MultiOrder\Classes;

use FirstWeb\MultiOrder\Classes\MultiOrder;
use FirstWeb\MultiOrder\Classes\DbHelper;
use RobinTheHood\PdfBill\Classes\PdfBill;

class Controller {
    public function invoke()
    {
        if ($_POST['fwAction'] == 'bills') {
            $this->invokeCreateBills();

        } elseif ($_POST['fwAction'] == 'deliveryNotes') {
            $this->invokeCreateDeliveryNotes();

        } elseif ($_POST['fwAction'] == 'billsAndDeliveryNotes') {
            $this->invokeCreateBillsAndDeliveryNotes();

        } elseif ($_POST['fwAction'] == 'billsAndDeliveryNotesMixed') {
            $this->invokeCreateBillsAndDeliveryNotesMixed();

        } elseif ($_POST['fwAction'] == 'changeOrderStatus') {
            $this->invokeUpdateOrders();

        } else {
            $this->invokeIndex();
        }
    }

    public function invokeIndex()
    {
        $this->showOrders();
    }

    public function invokeCreateBills()
    {
        $orderIds = is_array($_POST['orderIds']) ? $_POST['orderIds'] : [];

        $pdfBill = new PdfBill();
        foreach ($orderIds as $orderId) {
            $pdfBill->addBill($orderId);
        }

        $pdf = $pdfBill->getPdf();
        $this->showOrders();
        $this->outputPdf('Rechnung_', $pdf, $orderIds);
    }

    public function invokeCreateDeliveryNotes()
    {
        $orderIds = is_array($_POST['orderIds']) ? $_POST['orderIds'] : [];

        $pdfBill = new PdfBill();
        foreach ($orderIds as $orderId) {
            $pdfBill->addDeliveryNote($orderId);
        }

        $pdf = $pdfBill->getPdf();
        $this->showOrders();
        $this->outputPdf('Lieferschein_', $pdf, $orderIds);
    }

    public function invokeCreateBillsAndDeliveryNotes()
    {
        $orderIds = is_array($_POST['orderIds']) ? $_POST['orderIds'] : [];

        $pdfBill = new PdfBill();
        foreach ($orderIds as $orderId) {
            $pdfBill->addBill($orderId);
        }

        foreach ($orderIds as $orderId) {
            $pdfBill->addDeliveryNote($orderId);

        }

        $pdf = $pdfBill->getPdf();
        $this->showOrders();
        $this->outputPdf('Rechnung_und_Lieferschein_', $pdf, $orderIds);
    }

    public function invokeCreateBillsAndDeliveryNotesMixed()
    {
        $orderIds = is_array($_POST['orderIds']) ? $_POST['orderIds'] : [];

        $pdfBill = new PdfBill();
        foreach ($orderIds as $orderId) {
            $pdfBill->addBill($orderId);
            $pdfBill->addDeliveryNote($orderId);
        }

        $pdf = $pdfBill->getPdf();
        $this->showOrders();
        $this->outputPdf('Rechnung_und_Lieferschein_', $pdf, $orderIds);
    }

    public function outputPdf($fileName, $pdf, $orderIds)
    {
        $prefix = $fileName;
        $firstOrderId = $orderIds[0];
        $lastOrderId  = array_pop($orderIds);
        $filename  = '/admin/rth_letters/' . $prefix . $firstOrderId . '-' . $lastOrderId . '.pdf';
        $pdf->Output(DIR_FS_DOCUMENT_ROOT . $filename , 'F');

        echo '
            <script>
                window.open("' . $filename . '", "Bill Window - OrderId: ' . $orderId . '", "width=380, height=550");
            </script>
        ';
    }

    public function invokeUpdateOrders()
    {

        $orderIds = is_array($_POST['orderIds']) ? $_POST['orderIds'] : [];
        $statusId = $_POST['orderStatus'];
        $notifyCustomer = $_POST['notifyCustomer'];
        $statusTemplate = $_POST['status-template'];

        $multiOrder = new MultiOrder();
        $multiOrder->updateAllOrders($orderIds, $statusId, $notifyCustomer, $statusTemplate);

        $this->showOrders();
    }

    public function showOrders()
    {
        $multiOrder = new MultiOrder();
        $dbHelper = new DbHelper();

        $pageMaxDisplayResults = xtc_cfg_save_max_display_results('FW_MAX_DISPLAY_MULTI_ORDER_RESULTS');

        $orderStatus = $dbHelper->getAllOrderStati();
        $orderStatusForPullDown = $this->getOrderStatusForPullDown($orderStatus);

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

        $ordersQueryRaw = $this->buildQuery($orderStatusIdSelected, $orderCustomerFilter);

        $split = new \splitPageResults($_GET['page'], $pageMaxDisplayResults, $ordersQueryRaw, $orders_query_numrows);
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

        require_once '../vendor-no-composer/firstweb/MultiOrder/Templates/MultiOrder.tmpl.php';
    }

    public function buildQuery($orderStatusIdSelected, $orderCustomerFilter)
    {
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

        return $ordersQueryRaw;
    }

    public function getOrderStatusForPullDown($orderStatus)
    {
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

        return $orderStatusForPullDown;
    }
}
