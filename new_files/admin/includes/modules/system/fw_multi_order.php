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
    }

    public function remove()
    {
        parent::remove();
        $this->deleteAdminAccess('fw_multi_order');
    }
}
