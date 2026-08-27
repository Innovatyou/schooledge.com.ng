<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_721 extends CI_Migration
{
    public function up()
    {
        $sql = array('mobile_memberships' => <<<'SQL'
CREATE TABLE mobile_memberships (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, login_credential_id INT NOT NULL, user_id INT NOT NULL, branch_id INT NOT NULL, role_id INT NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'active', is_default TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL, updated_at DATETIME NULL, UNIQUE KEY uq_mobile_membership (login_credential_id,branch_id,role_id), KEY idx_mobile_membership_user (user_id,status), KEY idx_mobile_membership_branch (branch_id,status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL
        );
        foreach ($sql as $table => $query) {
            if (!$this->db->table_exists($table)) $this->db->query($query);
        }
    }
}
