<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_742 extends CI_Migration
{
    public function up()
    {
        // Moves Veltrix's master API credentials out of a git-tracked config
        // file (application/config/veltrix.php, now removed) and into the
        // same global payment_config row (branch_id 9999) every other
        // gateway secret already lives in, edited from Saas::settings_payment()
        // instead of a server file edit that a deploy could silently clobber.
        $columns = array(
            'veltrix_api_base'          => "VARCHAR(255) NOT NULL DEFAULT ''",
            'veltrix_api_key'           => "VARCHAR(255) NOT NULL DEFAULT ''",
            'veltrix_default_sender_id' => "VARCHAR(20) NOT NULL DEFAULT 'SCHLEDGE'",
            'veltrix_sms_price'         => "DECIMAL(8,2) NOT NULL DEFAULT 4.00",
            'veltrix_email_price'       => "DECIMAL(8,2) NOT NULL DEFAULT 1.00",
        );
        foreach ($columns as $name => $definition) {
            if (!$this->db->field_exists($name, 'payment_config')) {
                $this->db->query("ALTER TABLE payment_config ADD COLUMN `{$name}` {$definition}");
            }
        }
    }
}
