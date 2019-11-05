<?php
namespace FirstWeb\MultiOrder\Classes;

use FirstWeb\MultiOrder\Classes\MultiOrder;
use FirstWeb\MultiOrder\Classes\DbHelper;
use RobinTheHood\PdfBill\Classes\PdfBill;

class Controller {

    const SESSION_PREFIX = 'fw_multi_order';

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

        $filter = [
            'orderId' => $this->getValue('filterOrderId'),
            'customer' => $this->getValue('filterCustomer'),
            'orderStatusId' => $this->getValue('filterOrderStatusId', -1),
            'orderType' => $this->getValue('filterOrderType', -1),
        ];

        $sql = $this->buildSql($filter);
        $split = new \splitPageResults($_GET['page'], $pageMaxDisplayResults, $sql, $orders_query_numrows);
        $query = xtc_db_query($sql);

        $orderDatas = [];
        while ($row = xtc_db_fetch_array($query)) {
            $orderDatas[] = array(
                'id' => $row['orders_id'],
                'customerName' => $row['customers_name'],
                'customersCompany' => $row['customers_company'],
                'orderNumber' => $row['orders_id'],
                'county' => $row['delivery_country'],
                'totalPrice' => format_price(get_order_total($row['orders_id']), 1, $row['currency'], 0, 0),
                'orderDate' => $row['date_purchased'],
                'paymentMethod' => $row['payment_class'],
                'status' => $orderStatus[$row['orders_status']],
                'type' => $this->getOrderType($row)
            );
        }

        require_once '../vendor-no-composer/firstweb/MultiOrder/Templates/MultiOrder.tmpl.php';
    }

    public function buildSql($filter)
    {
        $sql = "SELECT * FROM " . TABLE_ORDERS;
        $sql .= ' WHERE 1=1 ';

        if ($filter['orderId'] > 0) {
            $sql .= " AND orders_id = '" . $filter['orderId'] . "'";
        }

        if ($filter['orderStatusId'] >= 0) {
            $sql .= " AND orders_status = '" . $filter['orderStatusId'] . "'";
        }

        if ($filter['customer']) {
            $sql .= " AND (customers_name LIKE '%" . $filter['customer'] . "%' OR customers_company LIKE '%" . $filter['customer'] . "%' OR customers_id LIKE '%" . $filter['customer'] . "%')";
        }

        if ($filter['orderType'] == 100) {
            $sql .= " AND comments LIKE '%magnalister%' AND comments LIKE '%(Amazon)%' AND comments NOT LIKE '%BUSINESS ORDER%'";
        }

        if ($filter['orderType'] == 101) {
            $sql .= " AND comments LIKE '%magnalister%' AND comments LIKE '%(Amazon Prime)%'";
        }

        if ($filter['orderType'] == 102) {
            $sql .= " AND comments LIKE '%magnalister%' AND comments LIKE '%(Amazon)%' AND comments LIKE '%BUSINESS ORDER%'";
        }

        if ($filter['orderType'] == 200) {
            $sql .= " AND comments LIKE '%magnalister%' AND comments LIKE '%(eBay)%'";
        }

        if ($filter['orderType'] == 300) {
            $sql .= " AND comments LIKE '%magnalister%' AND comments LIKE '%(Rakuten)%'";
        }

        $sql .= ' ORDER BY orders_id DESC';

        return $sql;
    }

    public function getValue($name, $defaultValue = '')
    {
        $value = $defaultValue;
        $sessionName = self::SESSION_PREFIX . '_' . $name;

        if (isset($_GET[$name])) {
            $value = $_GET[$name];
            $_SESSION[$sessionName] = $value;
        } elseif ($_SESSION[$sessionName]) {
            $value = $_SESSION[$sessionName];
        }

        return $value;
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

    public function getOrderType($order)
    {
        $comment = $order['comments'];

        if (strpos($comment, 'magnalister') !== false && strpos($comment, '(Amazon)') !== false && strpos($comment, 'BUSINESS ORDER') === false) {
            return 'Amazon (Magnalister)';
        }

        if (strpos($comment, 'magnalister') !== false && strpos($comment, '(Amazon Prime)') !== false) {
            return 'Amazon Prime (Magnalister)';
        }

        if (strpos($comment, 'magnalister') !== false && strpos($comment, '(Amazon)') !== false && strpos($comment, 'BUSINESS ORDER') !== false) {
            return 'Amazon Business (Magnalister)';
        }

        if (strpos($comment, 'magnalister') !== false && strpos($comment, '(eBay)') !== false) {
            return 'eBay (Magnalister)';
        }

        if (strpos($comment, 'magnalister') !== false && strpos($comment, '(Rakuten)') !== false) {
            return 'Rakuten (Magnalister)';
        }

        return 'Shop';
    }
}
