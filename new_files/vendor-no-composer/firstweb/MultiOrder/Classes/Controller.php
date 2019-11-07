<?php
namespace FirstWeb\MultiOrder\Classes;

use FirstWeb\MultiOrder\Classes\MultiOrder;
use FirstWeb\MultiOrder\Classes\DbHelper;
use RobinTheHood\PdfBill\Classes\PdfBill;

class Controller {
    const FILE_NAME = 'fw_multi_order.php';
    const SESSION_PREFIX = 'fw_multi_order';
    const TEMPLATE_PATH = '../vendor-no-composer/firstweb/MultiOrder/Templates/';
    const HOOK_TEMPLATE_ACTION_END = DIR_FS_CATALOG . 'admin/includes/extra/firstweb/multi-order/template-actions-end';
    const HOOK_CONTROLLER_INVOKE = DIR_FS_CATALOG . 'admin/includes/extra/firstweb/multi-order/controller-invoke';

    public function invoke()
    {
        foreach(auto_include(self::HOOK_CONTROLLER_INVOKE, 'php') as $file) require_once $file;

        if ($_POST['fwAction'] == 'pdf') {
            $this->invokePdf();

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

    public function invokePdf()
    {
        if ($_POST['fwPdfType'] == 'bills') {
            $this->invokeCreateBills();

        } elseif ($_POST['fwPdfType'] == 'deliveryNotes') {
            $this->invokeCreateDeliveryNotes();

        } elseif ($_POST['fwPdfType'] == 'billsAndDeliveryNotes') {
            $this->invokeCreateBillsAndDeliveryNotes();

        } elseif ($_POST['fwPdfType'] == 'billsAndDeliveryNotesMixed') {
            $this->invokeCreateBillsAndDeliveryNotesMixed();
        }
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
        $statusTemplateId = $_POST['statusTemplate'];

        $multiOrder = new MultiOrder();
        $multiOrder->updateAllOrders($orderIds, $statusId, $notifyCustomer, $statusTemplateId);

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
            'productModel' => $this->getValue('filterProductModel'),
            'productModelMode' => $this->getValue('filterProductModelMode'),
        ];

        $sql = $this->buildSql($filter);
        $split = new \splitPageResults($_GET['page'], $pageMaxDisplayResults, $sql, $orders_query_numrows, 'o.orders_id');

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

        require_once self::TEMPLATE_PATH . 'Index.tmpl.php';
    }

    public function buildSql($filter)
    {
        $tableOrders = 'orders o';

        if ($filter['productModel'] && $filter['productModelMode'] == 2) {
            // Nur ein Produkt darf enthalten sein und keine anderen aus diesem Gurnd
            // erzeuge eine "künstliche" Tabelle, in der nur Bestellungen sind mit einem Produkt
            $tableOrders = "(SELECT o.* FROM orders o, orders_products op WHERE o.orders_id = op.orders_id GROUP BY op.orders_id HAVING COUNT(op.orders_id) = 1) o";
        }

        $sql = "SELECT DISTINCT o.* FROM " . $tableOrders . ", orders_products op";
        $sql .= ' WHERE 1=1 ';

        if ($filter['productModel']) {
            $sql .= "AND o.orders_id = op.orders_id AND op.products_model = '" . $filter['productModel'] . "'";
        }

        if ($filter['orderId'] > 0) {
            $sql .= " AND o.orders_id = '" . $filter['orderId'] . "'";
        }

        if ($filter['orderStatusId'] >= 0) {
            $sql .= " AND o.orders_status = '" . $filter['orderStatusId'] . "'";
        }

        if ($filter['customer']) {
            $sql .= " AND (o.customers_name LIKE '%" . $filter['customer'] . "%' OR o.customers_company LIKE '%" . $filter['customer'] . "%' OR o.customers_id LIKE '%" . $filter['customer'] . "%')";
        }

        if ($filter['orderType'] == 001) {
            $sql .= " AND o.comments NOT LIKE '%magnalister%'";
        }

        if ($filter['orderType'] == 100) {
            $sql .= " AND o.comments LIKE '%magnalister%' AND o.comments LIKE '%(Amazon)%' AND o.comments NOT LIKE '%BUSINESS ORDER%'";
        }

        if ($filter['orderType'] == 101) {
            $sql .= " AND o.comments LIKE '%magnalister%' AND o.comments LIKE '%(Amazon Prime)%'";
        }

        if ($filter['orderType'] == 102) {
            $sql .= " AND o.comments LIKE '%magnalister%' AND o.comments LIKE '%(Amazon)%' AND o.comments LIKE '%BUSINESS ORDER%'";
        }

        if ($filter['orderType'] == 200) {
            $sql .= " AND o.comments LIKE '%magnalister%' AND o.comments LIKE '%(eBay)%'";
        }

        if ($filter['orderType'] == 300) {
            $sql .= " AND o.comments LIKE '%magnalister%' AND o.comments LIKE '%(Rakuten)%'";
        }

        $sql .= ' ORDER BY o.orders_id DESC';

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
