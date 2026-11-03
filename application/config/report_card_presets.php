<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Ready-made report card setups for the Report Card Setup wizard (exam/setup).
// Each preset gives the terms, the assessment columns with their full and
// pass marks, an optional mid-term test, and one or more grading scales
// (name, grade point, lowest mark, highest mark, remark). Schools can change
// every value in the wizard before it is applied.
$config['report_card_presets'] = array(
    'nigeria' => array(
        'label' => 'Nigeria',
        'code' => 'NG',
        'hint' => 'First, second and third term. 1st CA, 2nd CA and exam. WAEC grading.',
        'terms' => array('First Term', 'Second Term', 'Third Term'),
        'assessments' => array(
            array('1st CA', 20, 8),
            array('2nd CA', 20, 8),
            array('Exam', 60, 24),
        ),
        'midterm' => array('on' => true, 'name' => 'Mid-Term Test', 'full' => 40, 'pass' => 16),
        'type' => 3,
        'scales' => array(
            'waec' => array('label' => 'WAEC (A1 to F9), secondary', 'grades' => array(
                array('A1', 9, 75, 100, 'Excellent'),
                array('B2', 8, 70, 74, 'Very Good'),
                array('B3', 7, 65, 69, 'Good'),
                array('C4', 6, 60, 64, 'Credit'),
                array('C5', 5, 55, 59, 'Credit'),
                array('C6', 4, 50, 54, 'Credit'),
                array('D7', 3, 45, 49, 'Pass'),
                array('E8', 2, 40, 44, 'Pass'),
                array('F9', 1, 0, 39, 'Fail'),
            )),
            'primary' => array('label' => 'A to F, nursery and primary', 'grades' => array(
                array('A', 5, 70, 100, 'Excellent'),
                array('B', 4, 60, 69, 'Very Good'),
                array('C', 3, 50, 59, 'Good'),
                array('D', 2, 45, 49, 'Fair'),
                array('E', 1, 40, 44, 'Pass'),
                array('F', 0, 0, 39, 'Fail'),
            )),
        ),
    ),
    'ghana' => array(
        'label' => 'Ghana',
        'code' => 'GH',
        'hint' => 'Three terms. Class score and exam score. GES 1 to 9 grading.',
        'terms' => array('First Term', 'Second Term', 'Third Term'),
        'assessments' => array(
            array('Class Score', 30, 12),
            array('Exam Score', 70, 28),
        ),
        'midterm' => array('on' => true, 'name' => 'Mid-Term Test', 'full' => 40, 'pass' => 16),
        'type' => 3,
        'scales' => array(
            'ges' => array('label' => 'GES (1 to 9)', 'grades' => array(
                array('1', 1, 80, 100, 'Highest'),
                array('2', 2, 70, 79, 'Higher'),
                array('3', 3, 60, 69, 'High'),
                array('4', 4, 55, 59, 'High Average'),
                array('5', 5, 50, 54, 'Average'),
                array('6', 6, 45, 49, 'Low Average'),
                array('7', 7, 40, 44, 'Low'),
                array('8', 8, 35, 39, 'Lower'),
                array('9', 9, 0, 34, 'Lowest'),
            )),
        ),
    ),
    'kenya' => array(
        'label' => 'Kenya',
        'code' => 'KE',
        'hint' => 'Term 1, 2 and 3. Two CATs and an end-term exam. A to E grading.',
        'terms' => array('Term 1', 'Term 2', 'Term 3'),
        'assessments' => array(
            array('CAT 1', 15, 6),
            array('CAT 2', 15, 6),
            array('End Term Exam', 70, 28),
        ),
        'midterm' => array('on' => true, 'name' => 'Mid-Term Exam', 'full' => 50, 'pass' => 20),
        'type' => 3,
        'scales' => array(
            'kcse' => array('label' => 'A to E (12 points)', 'grades' => array(
                array('A', 12, 80, 100, 'Excellent'),
                array('A-', 11, 75, 79, 'Very Good'),
                array('B+', 10, 70, 74, 'Good'),
                array('B', 9, 65, 69, 'Good'),
                array('B-', 8, 60, 64, 'Fairly Good'),
                array('C+', 7, 55, 59, 'Average'),
                array('C', 6, 50, 54, 'Average'),
                array('C-', 5, 45, 49, 'Below Average'),
                array('D+', 4, 40, 44, 'Weak'),
                array('D', 3, 35, 39, 'Weak'),
                array('D-', 2, 30, 34, 'Poor'),
                array('E', 1, 0, 29, 'Very Poor'),
            )),
        ),
    ),
    'south_africa' => array(
        'label' => 'South Africa',
        'code' => 'ZA',
        'hint' => 'Four terms. School-based assessment and exam. Levels 1 to 7.',
        'terms' => array('Term 1', 'Term 2', 'Term 3', 'Term 4'),
        'assessments' => array(
            array('SBA', 40, 16),
            array('Exam', 60, 24),
        ),
        'midterm' => array('on' => false, 'name' => 'Mid-Term Test', 'full' => 50, 'pass' => 20),
        'type' => 3,
        'scales' => array(
            'nsc' => array('label' => 'Achievement levels (7 to 1)', 'grades' => array(
                array('7', 7, 80, 100, 'Outstanding'),
                array('6', 6, 70, 79, 'Meritorious'),
                array('5', 5, 60, 69, 'Substantial'),
                array('4', 4, 50, 59, 'Adequate'),
                array('3', 3, 40, 49, 'Moderate'),
                array('2', 2, 30, 39, 'Elementary'),
                array('1', 1, 0, 29, 'Not Achieved'),
            )),
        ),
    ),
    'uk' => array(
        'label' => 'United Kingdom',
        'code' => 'GB',
        'hint' => 'Autumn, spring and summer term. Coursework and exam. 9 to 1 grading.',
        'terms' => array('Autumn Term', 'Spring Term', 'Summer Term'),
        'assessments' => array(
            array('Coursework', 40, 16),
            array('Exam', 60, 24),
        ),
        'midterm' => array('on' => false, 'name' => 'Half-Term Test', 'full' => 50, 'pass' => 20),
        'type' => 3,
        'scales' => array(
            'gcse' => array('label' => '9 to 1 (GCSE style)', 'grades' => array(
                array('9', 9, 90, 100, 'Exceptional'),
                array('8', 8, 80, 89, 'Excellent'),
                array('7', 7, 70, 79, 'Very Good'),
                array('6', 6, 60, 69, 'Good'),
                array('5', 5, 50, 59, 'Strong Pass'),
                array('4', 4, 40, 49, 'Standard Pass'),
                array('3', 3, 30, 39, 'Below Pass'),
                array('2', 2, 20, 29, 'Weak'),
                array('1', 1, 10, 19, 'Very Weak'),
                array('U', 0, 0, 9, 'Ungraded'),
            )),
        ),
    ),
    'us' => array(
        'label' => 'United States',
        'code' => 'US',
        'hint' => 'Fall and spring semester. Classwork, tests and final exam. Letter grades with GPA.',
        'terms' => array('Fall Semester', 'Spring Semester'),
        'assessments' => array(
            array('Classwork', 30, 18),
            array('Tests', 30, 18),
            array('Final Exam', 40, 24),
        ),
        'midterm' => array('on' => true, 'name' => 'Midterm Exam', 'full' => 100, 'pass' => 60),
        'type' => 3,
        'scales' => array(
            'letter' => array('label' => 'Letter grades (A to F, 4.0 GPA)', 'grades' => array(
                array('A', 4.0, 90, 100, 'Excellent'),
                array('B', 3.0, 80, 89, 'Good'),
                array('C', 2.0, 70, 79, 'Satisfactory'),
                array('D', 1.0, 60, 69, 'Passing'),
                array('F', 0, 0, 59, 'Failing'),
            )),
        ),
    ),
);
