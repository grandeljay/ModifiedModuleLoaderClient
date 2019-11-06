<?php
defined( '_VALID_XTC' ) or die( 'Direct Access to this location is not allowed.' );

use RobinTheHood\ModifiedStdModule\Classes\StdModule;
require_once DIR_FS_DOCUMENT_ROOT . '/vendor-no-composer/firstweb/MultiOrder/autoload.php';


class fw_multi_order extends StdModule
{
    public function __construct()
    {
        $this->init('MODULE_FW_MULTI_ORDER');
    }

    public function display()
    {
        return $this->displaySaveButton();
    }

    public function install()
    {
        parent::install();
        $this->setAdminAccess('fw_multi_order');

        // Tabelle erstellen
        xtc_db_query("CREATE TABLE `fw_multi_order_status_template` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `title` varchar(255) DEFAULT NULL,
                `text` text,
                PRIMARY KEY (`id`)
            )"
        );
    }

    public function remove()
    {
        parent::remove();
        $this->deleteAdminAccess('fw_multi_order');
    }
}
