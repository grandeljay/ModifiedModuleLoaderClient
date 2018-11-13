<?php
defined( '_VALID_XTC' ) or die( 'Direct Access to this location is not allowed.' );

if (MODULE_FW_MULTI_ORDER_STATUS == 'true') {
    switch ($_SESSION['language_code']) {
        case 'de':
            define('MENU_NAME_FW_MULTI_ORDER', 'Massenverarbeitung (First-Web)');
            break;
        case 'en':
            define('MENU_NAME_FW_MULTI_ORDER', 'Multi Order (First-Web)');
            break;
        default:
            define('MENU_NAME_FW_MULTI_ORDER', 'Massenverarbeitung (First-Web)');
            break;
    }

    //BOX_HEADING_TOOLS = Name der box in der der neue Menueeintrag erscheinen soll
    $add_contents[BOX_HEADING_CUSTOMERS][] = array(
        'admin_access_name' => 'fw_multi_order',    // Eintrag fuer Adminrechte
        'filename' => 'fw_multi_order.php',         // Dateiname der neuen Admindatei
        'boxname' => MENU_NAME_FW_MULTI_ORDER,      // Anzeigename im Menü
        'parameter' => '',                          // zusätzliche Parameter z.B. 'set=export'
        'ssl' => ''                                 // SSL oder NONSSL, kein Eintrag = NONSSL
    );
}
