<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Where staff, students and parents get the SkoolOS app: Google Play, the App
// Store and/or the Android APK. The platform owner sets the store links here.
class Mobile_app extends Dashboard_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $apkPath = FCPATH . 'uploads/mobile_app/SchoolEdge.apk';
        $config = $this->db->get_where('global_settings', array('id' => 1))->row_array();
        $playUrl = isset($config['play_store_url']) ? $config['play_store_url'] : '';
        $iosUrl = isset($config['app_store_url']) ? $config['app_store_url'] : '';
        $showApk = !isset($config['show_apk']) || $config['show_apk'] == 1;

        $this->data['apk_available'] = $showApk && is_file($apkPath);
        $this->data['apk_url'] = base_url('uploads/mobile_app/SchoolEdge.apk');
        $this->data['apk_size'] = is_file($apkPath) ? round(filesize($apkPath) / 1048576, 1) : null;
        $this->data['play_store_url'] = $playUrl;
        $this->data['app_store_url'] = $iosUrl;
        $this->data['show_apk'] = $showApk;
        $this->data['store_fields'] = isset($config['play_store_url']);
        $this->data['qr'] = array(
            'play' => $this->qr($playUrl),
            'ios' => $this->qr($iosUrl),
            'apk' => $this->data['apk_available'] ? $this->qr($this->data['apk_url']) : '',
        );
        $this->data['sub_page'] = 'mobile_app/index';
        $this->data['main_menu'] = 'mobile_app';
        $this->data['title'] = translate('mobile_app');
        $this->load->view('layout/index', $this->data);
    }

    // platform owner only: store links and whether to offer the APK
    public function save()
    {
        if (!is_superadmin_loggedin() || !$_POST || !$this->db->field_exists('play_store_url', 'global_settings')) {
            access_denied();
        }
        $clean = function ($url) {
            $url = trim((string) $url);
            return ($url === '' || preg_match('#^https://[^\s"<>]+$#i', $url)) ? mb_substr($url, 0, 255) : false;
        };
        $play = $clean($this->input->post('play_store_url'));
        $ios = $clean($this->input->post('app_store_url'));
        if ($play === false || $ios === false) {
            set_alert('error', 'Store links must be full https:// addresses.');
            redirect(base_url('mobile_app'));
        }
        $this->db->where('id', 1)->update('global_settings', array(
            'play_store_url' => $play,
            'app_store_url' => $ios,
            'show_apk' => $this->input->post('show_apk') ? 1 : 0,
        ));
        set_alert('success', translate('information_has_been_saved_successfully'));
        redirect(base_url('mobile_app'));
    }

    // scan-to-download QR code for a link, cached by its content
    private function qr($url)
    {
        if ($url === '') {
            return '';
        }
        $file = 'uploads/qr_code/app_' . md5($url) . '.png';
        if (!is_file(FCPATH . $file)) {
            $this->load->library('ciqrcode', array('cacheable' => false));
            $this->ciqrcode->generate(array('data' => $url, 'savename' => FCPATH . $file, 'level' => 'M', 'size' => 6));
        }
        return is_file(FCPATH . $file) ? base_url($file) : '';
    }
}
