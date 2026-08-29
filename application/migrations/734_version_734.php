<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_734 extends CI_Migration
{
    public function up()
    {
        // Audiobook support, mirroring exactly how migration 727 added ebook
        // support: an optional media file per book, independent of physical
        // stock/issue status.
        $columns = array(
            'audiobook_file' => "ALTER TABLE book ADD COLUMN audiobook_file VARCHAR(255) NULL AFTER ebook_uploaded_at",
            'audiobook_original_name' => "ALTER TABLE book ADD COLUMN audiobook_original_name VARCHAR(255) NULL AFTER audiobook_file",
            'audiobook_uploaded_at' => "ALTER TABLE book ADD COLUMN audiobook_uploaded_at DATETIME NULL AFTER audiobook_original_name",
            'audiobook_duration_seconds' => "ALTER TABLE book ADD COLUMN audiobook_duration_seconds INT NULL AFTER audiobook_uploaded_at",
        );
        foreach ($columns as $column => $sql) {
            if (!$this->db->field_exists($column, 'book')) $this->db->query($sql);
        }
    }
}
