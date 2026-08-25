<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_711 extends CI_Migration
{
    public function up()
    {
        $faqs = array(
            'What is SchoolEdge.com.ng?' => 'SchoolEdge.com.ng is a school management platform that helps schools manage academic, administrative, financial, and communication activities in one place.',
            'Who can use SchoolEdge?' => 'School owners, administrators, teachers, accountants, parents, and students can use SchoolEdge. Each user receives access to the tools and information relevant to their role.',
            'What features does SchoolEdge offer?' => 'SchoolEdge supports student and staff management, attendance tracking, fees and payment records, results and report cards, class management, parent communication, and school reporting.',
            'How do I register my school?' => 'Select the registration option on the website and follow the steps to create your school account. You can also contact the SchoolEdge team for help with setup.',
            'Is SchoolEdge suitable for every type of school?' => 'Yes. SchoolEdge can support nursery, primary, and secondary schools of different sizes. Available features can be configured to match each school\'s operations.',
            'Can parents view their children\'s information?' => 'Yes. Parents can securely access approved information such as results, attendance, fee status, announcements, and other school updates.',
            'Can teachers enter scores and prepare results online?' => 'Yes. Teachers can record assessments and examination scores, while SchoolEdge calculates results and generates report cards according to the school\'s grading system.',
            'Does SchoolEdge support school-fee management?' => 'Yes. Schools can assign fees, record payments, monitor outstanding balances, issue receipts, and review payment reports.',
            'Is our school\'s information secure?' => 'SchoolEdge uses access controls and security measures to protect school data. Users can only access information permitted for their assigned roles.',
            'Can we use SchoolEdge on a mobile phone?' => 'Yes. SchoolEdge can be accessed through supported web browsers on smartphones, tablets, laptops, and desktop computers.',
            'Do we need to install any software?' => 'No. SchoolEdge is web-based, so users can access it online without installing traditional desktop software.',
            'What subscription plans are available?' => 'Subscription options vary according to the school\'s size and required features. Review the pricing section or contact the SchoolEdge team for current plans and inclusions.',
            'Is training available for school staff?' => 'Yes. Onboarding guidance and training can be provided to help administrators and staff use the platform effectively.',
            'Can SchoolEdge be customized for our school?' => 'Schools can configure sessions, terms, classes, subjects, grading systems, fees, and user permissions. Additional customization may depend on the selected plan.',
            'What happens if we need technical support?' => 'The SchoolEdge support team can assist with account setup, platform usage, and technical issues through the available support channels.',
            'How can our school get started?' => 'Register your school on SchoolEdge.com.ng or contact the SchoolEdge team to discuss your requirements and choose a suitable subscription plan.',
        );

        foreach ($faqs as $title => $description) {
            $existing = $this->db
                ->select('id')
                ->where('title', $title)
                ->get('saas_cms_faq_list')
                ->row();

            if ($existing) {
                $this->db
                    ->where('id', $existing->id)
                    ->update('saas_cms_faq_list', array('description' => $description));
            } else {
                $this->db->insert('saas_cms_faq_list', array(
                    'title' => $title,
                    'description' => $description,
                ));
            }
        }

        $this->db->update('saas_settings', array(
            'faq_title' => 'Frequently Asked Questions',
            'faq_description' => 'Find answers to the most common questions about SchoolEdge.com.ng. From subscription details to feature explanations, our FAQ section is designed to help you quickly understand how our platform works and how it can benefit your school.',
        ));
    }
}
