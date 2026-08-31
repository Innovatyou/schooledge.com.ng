<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_740 extends CI_Migration
{
    public function up()
    {
        // Backstop against a double credit when both the Paystack browser
        // callback (Veltrixwallet::verify_paystack_payment) and the Paystack
        // webhook (Veltrixwebhook::paystack) land for the same wallet top-up:
        // whichever inserts first wins the reference, the second's insert
        // fails and Veltrix_wallet_model::credit() reports it as a no-op.
        $exists = $this->db->query("
            SELECT 1 FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'school_wallet_transaction'
              AND index_name = 'uniq_branch_reference'
        ")->row();
        if (!$exists) {
            $this->db->query("ALTER TABLE school_wallet_transaction ADD UNIQUE KEY uniq_branch_reference (branch_id, reference)");
        }
    }
}
