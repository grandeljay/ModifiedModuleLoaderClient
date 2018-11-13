<?php
defined( '_VALID_XTC' ) or die( 'Direct Access to this location is not allowed.' );

class fw_multi_order {
    public $code;
    public $title;
    public $description;
    public $enabled;
    public $sort_order;

    public function __construct()
    {
        $this->code = 'fw_multi_order';
        $this->title = MODULE_FW_MULTI_ORDER_TITLE;
        $this->description = MODULE_FW_MULTI_ORDER_LONG_DESCRIPTION;
        $this->sort_order = defined('MODULE_FW_MULTI_ORDER_SORT_ORDER') ? MODULE_FW_MULTI_ORDER_SORT_ORDER : 0;
        $this->enabled = ((strtolower(MODULE_FW_MULTI_ORDER_STATUS) == 'true') ? true : false);
    }

    public function process($file)
    {
    }

    public function display()
    {
    }

    public function check()
    {
        if (!isset($this->_check)) {
            $check_query = xtc_db_query("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'MODULE_FW_MULTI_ORDER_STATUS'");
            $this->_check = xtc_db_num_rows($check_query);
        }
        return $this->_check;
    }

    public function install()
    {
        xtc_db_query("INSERT INTO `" . TABLE_CONFIGURATION . "` (`configuration_key`, `configuration_value`, `configuration_group_id`, `sort_order`, `set_function`, `date_added`) VALUES ('MODULE_FW_MULTI_ORDER_STATUS', 'true', '6', '1', 'xtc_cfg_select_option(array(\'true\', \'false\'), ', now())");

        xtc_db_query("ALTER TABLE `" . TABLE_ADMIN_ACCESS . "` ADD `fw_multi_order` INT(1) NOT NULL DEFAULT 0");
        xtc_db_query("UPDATE `" . TABLE_ADMIN_ACCESS . "` SET `fw_multi_order` = 1 WHERE `customers_id` = 1");
        xtc_db_query("UPDATE `" . TABLE_ADMIN_ACCESS . "` SET `fw_multi_order` = 1 WHERE `customers_id`='groups'");
    }

    public function remove()
    {
        xtc_db_query("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'MODULE_FW_MULTI_ORDER_STATUS'");
        xtc_db_query("ALTER TABLE `" . TABLE_ADMIN_ACCESS . "` DROP `fw_multi_order`;");
    }

    public function keys()
    {
      return array('MODULE_FW_MULTI_ORDER_STATUS');
    }
}
