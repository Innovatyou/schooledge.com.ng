<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_753 extends CI_Migration
{
    public function up()
    {
        // Tidier starter certificate and admit card on the demo schools' saved templates.
        // The live sites run application/database_seeds/apply_pending_migrations.php, which does the same.
        $fixes = array(
            '<div style="position:relative;height:100%;text-align:center;padding:30px 60px 0;">' => '<div style="position:relative;height:100%;box-sizing:border-box;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:40px 70px;">',
            'font-size:36px;font-weight:bold;color:#16233F;margin-top:18px;letter-spacing:1px;">Certificate of Achievement' => 'font-size:42px;font-weight:bold;color:#16233F;margin-top:20px;letter-spacing:1px;">Certificate of Achievement',
            '<div style="font-family:Georgia,serif;font-size:28px;font-style:italic;color:#16233F;margin-top:12px;' => '<div style="font-family:Georgia,serif;font-size:32px;font-style:italic;color:#16233F;margin-top:14px;',
            '<table style="width:100%;max-width:520px;margin:40px auto 0;">' => '<table style="width:100%;max-width:600px;margin:56px auto 0;">',
            '<td style="text-align:center;width:33%;"><div style="border-top:1px solid #16233F;' => '<td style="text-align:center;width:33%;padding:0 14px;vertical-align:bottom;"><div style="border-top:1px solid #16233F;',
            '<td style="text-align:center;width:33%;">{signature}<div style="border-top:1px solid #16233F;' => '<td style="text-align:center;width:33%;padding:0 14px;vertical-align:bottom;">{signature}<div style="border-top:1px solid #16233F;',
            '<td style="width:40px;vertical-align:middle;">{logo}</td>' => '<td style="width:64px;vertical-align:middle;">{logo}</td>'
        );
        foreach (array('card_templete', 'certificates_templete') as $table) {
            if (!$this->db->table_exists($table) || !$this->db->field_exists('is_demo', 'branch')) {
                continue;
            }
            $rows = $this->db->select('t.id, t.content')->from($table . ' t')->join('branch b', 'b.id = t.branch_id')->where('b.is_demo', 1)->get()->result_array();
            foreach ($rows as $row) {
                $content = strtr($row['content'], $fixes);
                if ($content !== $row['content']) {
                    $this->db->where('id', $row['id'])->update($table, array('content' => $content));
                }
            }
        }
    }
}
