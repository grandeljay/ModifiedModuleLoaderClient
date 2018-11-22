<?php
namespace FirstWeb\MultiOrder\Classes;

class DbHelper
{
    public function getStatusTemplate($id)
    {
        $id = (int) $id;
        $sql = "SELECT * FROM fw_status_template WHERE id=" . $id;
        $query = xtc_db_query($sql);
        $row = xtc_db_fetch_array($query);
        return $row;

    }

    public function getAllStatusTemplates()
    {
        return [];

        $sql = "SELECT * FROM fw_status_template";
        $query = xtc_db_query($sql);
        $statusTemplates = [];
        while ($row = xtc_db_fetch_array($query)) {
            $statusTemplates[] = $row;
        }
        return $statusTemplates;
    }

    public function getOrdersTracking($trackingId)
    {
        $sql = "SELECT * FROM orders_tracking WHERE tracking_id='$trackingId'";
        $query = xtc_db_query($sql);

        $row = xtc_db_fetch_array($query);
        return $row;
    }

    public function getTrackingIds($orderId)
    {
        $sql = "SELECT * FROM orders_tracking WHERE orders_id='$orderId'";
        $query = xtc_db_query($sql);

        $trackingIds = array();
        while ($row = xtc_db_fetch_array($query)) {
            $trackingIds[] = $row['tracking_id'];
        }

        return $trackingIds;
    }

    public function getAllOrderStati()
    {
        // Alle Bestellstatus-Moeglichkeiten aus der Datebank abfragen
        $orderStatusQueryRaw = "SELECT * FROM " . TABLE_ORDERS_STATUS . " WHERE language_id = '" . (int) $_SESSION['languages_id'] . "'";
        $orderStatusQuery = xtc_db_query($orderStatusQueryRaw);
        $orderStatus = array();
        while ($orderStatusRow = xtc_db_fetch_array($orderStatusQuery)) {
            $orderStatus[$orderStatusRow['orders_status_id']] = $orderStatusRow['orders_status_name'];
        }
        
        return $orderStatus;
    }
}
