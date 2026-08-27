<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_727 extends CI_Migration
{
    public function up()
    {
        $columns = array(
            'ebook_file' => "ALTER TABLE book ADD COLUMN ebook_file VARCHAR(255) NULL AFTER cover",
            'ebook_original_name' => "ALTER TABLE book ADD COLUMN ebook_original_name VARCHAR(255) NULL AFTER ebook_file",
            'ebook_uploaded_at' => "ALTER TABLE book ADD COLUMN ebook_uploaded_at DATETIME NULL AFTER ebook_original_name",
        );
        foreach ($columns as $column => $sql) {
            if (!$this->db->field_exists($column, 'book')) $this->db->query($sql);
        }
    }
}
