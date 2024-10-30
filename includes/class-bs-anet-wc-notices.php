<?php
/**
 * anetNotice class
 * @author Bipin
 */
class anetNotice {
    private $_message;
    private $_class;

    /**
     * __construct
     *
     * @param [type] $message
     * @param string $type
     */
    function __construct($message, $type = '') {
        $repeat = $message == $this->_message ? true : false;
        $this->_message = $message;
        switch ($type) {
            case 'error':
                $this->_class = 'wc_anet_notice notice notice-error';
            break;
            case 'warning':
                $this->_class = 'wc_anet_notice notice notice-warning';
            break;
            case ('info' || ''):
                $this->_class = 'wc_anet_notice notice notice-info';
            break;
            default:
                $this->_class = 'wc_anet_notice notice notice-info';
        }
        if (!$repeat) add_action('admin_notices', array($this, 'render'));
    }

    /**
     * render
     *
     * @return void
     */
    function render() {
        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($this->_class), esc_html($this->_message));
    }
}
