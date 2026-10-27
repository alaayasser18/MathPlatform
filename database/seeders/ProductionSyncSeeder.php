<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductionSyncSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Table: grades (1 rows)
        DB::table('grades')->truncate();
        DB::table('grades')->insert(array (
  0 => 
  array (
    'id' => 1,
    'name' => 'First Secondary',
    'created_at' => '2026-09-17 20:00:41',
    'updated_at' => '2026-09-17 20:00:41',
  ),
));

        // Table: admins (1 rows)
        DB::table('admins')->truncate();
        DB::table('admins')->insert(array (
  0 => 
  array (
    'id' => 1,
    'name' => 'Alaa-Admin',
    'email' => 'yasseralaa041@gmail.com',
    'password' => '$2y$12$2gCUqeGFVxqHB99wDkHDVefVWl4HRqfm9TYSMqT.qcgSSdoXfzd3y',
    'is_active' => 1,
    'created_at' => '2026-09-17 20:27:24',
    'updated_at' => '2026-09-17 20:27:24',
  ),
));

        // Table: students (14 rows)
        DB::table('students')->truncate();
        DB::table('students')->insert(array (
  0 => 
  array (
    'id' => 4,
    'first_name' => 'alaa',
    'last_name' => 'yasser',
    'phone' => '01090172421',
    'parent_phone' => '01227823946',
    'grade_id' => 1,
    'registered_at' => '2026-09-17',
    'created_at' => '2026-09-17 22:01:56',
    'updated_at' => '2026-09-17 22:01:56',
  ),
  1 => 
  array (
    'id' => 5,
    'first_name' => 'mariam',
    'last_name' => 'tarek',
    'phone' => '01012345778',
    'parent_phone' => '01112565678',
    'grade_id' => 1,
    'registered_at' => '2026-09-17',
    'created_at' => '2026-09-17 22:45:06',
    'updated_at' => '2026-09-17 22:45:06',
  ),
  2 => 
  array (
    'id' => 6,
    'first_name' => 'samy',
    'last_name' => 'ahmed',
    'phone' => '01012345668',
    'parent_phone' => '01112323678',
    'grade_id' => 1,
    'registered_at' => '2026-09-20',
    'created_at' => '2026-09-20 19:48:05',
    'updated_at' => '2026-09-20 19:48:05',
  ),
  3 => 
  array (
    'id' => 7,
    'first_name' => 'alaa',
    'last_name' => 'ahmed',
    'phone' => '01012345978',
    'parent_phone' => '01112345678',
    'grade_id' => 1,
    'registered_at' => '2026-09-20',
    'created_at' => '2026-09-20 20:29:59',
    'updated_at' => '2026-09-20 20:29:59',
  ),
  4 => 
  array (
    'id' => 8,
    'first_name' => 'Test',
    'last_name' => 'Student',
    'phone' => '01000000001',
    'parent_phone' => '01100000001',
    'grade_id' => 1,
    'registered_at' => '2026-10-16',
    'created_at' => '2026-10-16 21:09:22',
    'updated_at' => '2026-10-16 21:09:22',
  ),
  5 => 
  array (
    'id' => 9,
    'first_name' => 'Test',
    'last_name' => 'Student',
    'phone' => '01011111111',
    'parent_phone' => '01122222222',
    'grade_id' => 1,
    'registered_at' => '2026-10-16',
    'created_at' => '2026-10-16 21:13:41',
    'updated_at' => '2026-10-16 21:13:41',
  ),
  6 => 
  array (
    'id' => 10,
    'first_name' => 'الاء',
    'last_name' => 'ياسر',
    'phone' => '01090172426',
    'parent_phone' => '01096243874',
    'grade_id' => 1,
    'registered_at' => '2026-10-16',
    'created_at' => '2026-10-16 21:19:52',
    'updated_at' => '2026-10-16 21:19:52',
  ),
  7 => 
  array (
    'id' => 11,
    'first_name' => 'الاء',
    'last_name' => 'ياسر',
    'phone' => '01090172433',
    'parent_phone' => '01028536637',
    'grade_id' => 1,
    'registered_at' => '2026-10-16',
    'created_at' => '2026-10-16 21:27:50',
    'updated_at' => '2026-10-16 21:27:50',
  ),
  8 => 
  array (
    'id' => 12,
    'first_name' => 'سارة',
    'last_name' => 'أحمد',
    'phone' => '01023456789',
    'parent_phone' => '01198765432',
    'grade_id' => 1,
    'registered_at' => '2026-10-16',
    'created_at' => '2026-10-16 21:47:23',
    'updated_at' => '2026-10-16 21:47:23',
  ),
  9 => 
  array (
    'id' => 13,
    'first_name' => 'الاء',
    'last_name' => 'ياسر',
    'phone' => '01091273526',
    'parent_phone' => '01018238256',
    'grade_id' => 1,
    'registered_at' => '2026-10-16',
    'created_at' => '2026-10-16 21:50:20',
    'updated_at' => '2026-10-16 21:50:20',
  ),
  10 => 
  array (
    'id' => 14,
    'first_name' => 'علي',
    'last_name' => 'محمد',
    'phone' => '01048661813',
    'parent_phone' => '01148661813',
    'grade_id' => 1,
    'registered_at' => '2026-10-17',
    'created_at' => '2026-10-17 19:58:30',
    'updated_at' => '2026-10-17 19:58:30',
  ),
  11 => 
  array (
    'id' => 15,
    'first_name' => 'طالب',
    'last_name' => 'تجريبي 88871735',
    'phone' => '01088871735',
    'parent_phone' => '01188871735',
    'grade_id' => 1,
    'registered_at' => '2026-10-17',
    'created_at' => '2026-10-17 21:02:17',
    'updated_at' => '2026-10-17 21:02:17',
  ),
  12 => 
  array (
    'id' => 16,
    'first_name' => 'يوسف المعدل',
    'last_name' => 'إبراهيم السعيد',
    'phone' => '01083748552',
    'parent_phone' => '01183748552',
    'grade_id' => 1,
    'registered_at' => '2026-10-17',
    'created_at' => '2026-10-17 21:30:35',
    'updated_at' => '2026-10-17 21:30:49',
  ),
  13 => 
  array (
    'id' => 17,
    'first_name' => 'عمر المحدث',
    'last_name' => 'فاروق عثمان',
    'phone' => '01016077283',
    'parent_phone' => '01116077283',
    'grade_id' => 1,
    'registered_at' => '2026-10-17',
    'created_at' => '2026-10-17 21:46:17',
    'updated_at' => '2026-10-17 21:46:18',
  ),
));

        // Table: sections (4 rows)
        DB::table('sections')->truncate();
        DB::table('sections')->insert(array (
  0 => 
  array (
    'id' => 1,
    'grade_id' => 1,
    'name' => 'الجبر',
    'description' => NULL,
    'sort_order' => 0,
    'is_active' => 1,
    'created_at' => '2026-09-22 07:55:42',
    'updated_at' => '2026-09-22 07:55:42',
  ),
  1 => 
  array (
    'id' => 2,
    'grade_id' => 1,
    'name' => 'الهندسة',
    'description' => NULL,
    'sort_order' => 0,
    'is_active' => 1,
    'created_at' => '2026-09-22 07:56:15',
    'updated_at' => '2026-09-22 07:56:15',
  ),
  2 => 
  array (
    'id' => 3,
    'grade_id' => 1,
    'name' => 'حساب مثلثات',
    'description' => NULL,
    'sort_order' => 0,
    'is_active' => 1,
    'created_at' => '2026-09-22 07:56:22',
    'updated_at' => '2026-09-22 07:56:22',
  ),
  3 => 
  array (
    'id' => 4,
    'grade_id' => 1,
    'name' => 'الجبر والهندسة الفراغية',
    'description' => NULL,
    'sort_order' => 1,
    'is_active' => 1,
    'created_at' => '2026-10-17 22:00:52',
    'updated_at' => '2026-10-17 22:00:52',
  ),
));

        // Table: lessons (4 rows)
        DB::table('lessons')->truncate();
        DB::table('lessons')->insert(array (
  0 => 
  array (
    'id' => 1,
    'section_id' => 1,
    'title' => 'المصفوفات',
    'description' => 'شرح درس المصفوفات',
    'sort_order' => 1,
    'is_active' => 1,
    'created_at' => '2026-09-22 07:55:42',
    'updated_at' => '2026-09-22 07:55:42',
  ),
  1 => 
  array (
    'id' => 3,
    'section_id' => 2,
    'title' => 'المصفوفات',
    'description' => 'شرح درس المصفوفات',
    'sort_order' => 1,
    'is_active' => 1,
    'created_at' => '2026-09-22 07:56:15',
    'updated_at' => '2026-09-22 07:56:15',
  ),
  2 => 
  array (
    'id' => 4,
    'section_id' => 3,
    'title' => 'المصفوفات',
    'description' => 'شرح درس المصفوفات',
    'sort_order' => 1,
    'is_active' => 1,
    'created_at' => '2026-09-22 07:56:22',
    'updated_at' => '2026-09-22 07:56:22',
  ),
  3 => 
  array (
    'id' => 5,
    'section_id' => 4,
    'title' => 'المصفوفات والمحددات',
    'description' => 'شرح شامل للمصفوفات والعمليات عليها وتطبيقاتها مع حل تمارين متنوعة',
    'sort_order' => 1,
    'is_active' => 1,
    'created_at' => '2026-10-17 22:00:52',
    'updated_at' => '2026-10-17 22:00:52',
  ),
));

        // Table: exams (1 rows)
        DB::table('exams')->truncate();
        DB::table('exams')->insert(array (
  0 => 
  array (
    'id' => 3,
    'lesson_id' => 1,
    'title' => 'اختبار الدرس الأول',
    'description' => 'اختبار على محتوى الدرس',
    'duration_minutes' => 20,
    'passing_percentage' => 60,
    'is_active' => 1,
    'created_at' => '2026-09-22 13:11:17',
    'updated_at' => '2026-09-22 13:11:17',
  ),
));

        // Table: questions (2 rows)
        DB::table('questions')->truncate();
        DB::table('questions')->insert(array (
  0 => 
  array (
    'id' => 4,
    'exam_id' => 3,
    'question_text' => 'ما هو ناتج 2 + 2؟',
    'sort_order' => 1,
    'points' => 1,
    'created_at' => '2026-09-22 13:13:03',
    'updated_at' => '2026-09-22 13:13:03',
  ),
  1 => 
  array (
    'id' => 5,
    'exam_id' => 3,
    'question_text' => 'ما هو ناتج 2 - 2؟',
    'sort_order' => 2,
    'points' => 1,
    'created_at' => '2026-09-22 13:14:59',
    'updated_at' => '2026-09-22 13:14:59',
  ),
));

        // Table: options (6 rows)
        DB::table('options')->truncate();
        DB::table('options')->insert(array (
  0 => 
  array (
    'id' => 11,
    'question_id' => 4,
    'option_text' => '4',
    'is_correct' => 1,
    'sort_order' => 1,
    'created_at' => '2026-09-22 13:14:25',
    'updated_at' => '2026-09-22 13:14:25',
  ),
  1 => 
  array (
    'id' => 12,
    'question_id' => 4,
    'option_text' => '2',
    'is_correct' => 0,
    'sort_order' => 2,
    'created_at' => '2026-09-22 13:14:34',
    'updated_at' => '2026-09-22 13:14:34',
  ),
  2 => 
  array (
    'id' => 13,
    'question_id' => 4,
    'option_text' => '6',
    'is_correct' => 0,
    'sort_order' => 3,
    'created_at' => '2026-09-22 13:14:41',
    'updated_at' => '2026-09-22 13:14:41',
  ),
  3 => 
  array (
    'id' => 14,
    'question_id' => 5,
    'option_text' => '4',
    'is_correct' => 0,
    'sort_order' => 1,
    'created_at' => '2026-09-22 13:15:25',
    'updated_at' => '2026-09-22 13:15:25',
  ),
  4 => 
  array (
    'id' => 15,
    'question_id' => 5,
    'option_text' => '9',
    'is_correct' => 0,
    'sort_order' => 2,
    'created_at' => '2026-09-22 13:15:32',
    'updated_at' => '2026-09-22 13:15:32',
  ),
  5 => 
  array (
    'id' => 16,
    'question_id' => 5,
    'option_text' => '0',
    'is_correct' => 1,
    'sort_order' => 3,
    'created_at' => '2026-09-22 13:15:42',
    'updated_at' => '2026-09-22 13:15:42',
  ),
));

        // Table: subscription_codes (16 rows)
        DB::table('subscription_codes')->truncate();
        DB::table('subscription_codes')->insert(array (
  0 => 
  array (
    'id' => 1,
    'code' => 'ZFGYSCWMFC',
    'approval_status' => 'approved',
    'approved_at' => '2026-09-17 21:16:03',
    'expires_at' => NULL,
    'is_used' => 0,
    'used_by_student_id' => NULL,
    'used_at' => NULL,
    'created_at' => '2026-09-17 20:10:12',
    'updated_at' => '2026-09-17 21:16:03',
  ),
  1 => 
  array (
    'id' => 2,
    'code' => 'VUIFGFXMMN',
    'approval_status' => 'approved',
    'approved_at' => '2026-09-17 21:32:00',
    'expires_at' => NULL,
    'is_used' => 0,
    'used_by_student_id' => NULL,
    'used_at' => NULL,
    'created_at' => '2026-09-17 20:22:11',
    'updated_at' => '2026-09-17 21:32:00',
  ),
  2 => 
  array (
    'id' => 3,
    'code' => 'XW5W7FZEKY',
    'approval_status' => 'approved',
    'approved_at' => '2026-09-20 18:49:10',
    'expires_at' => NULL,
    'is_used' => 0,
    'used_by_student_id' => NULL,
    'used_at' => NULL,
    'created_at' => '2026-09-17 22:01:56',
    'updated_at' => '2026-09-20 18:49:11',
  ),
  3 => 
  array (
    'id' => 4,
    'code' => 'MAXAOXIZ6F',
    'approval_status' => 'approved',
    'approved_at' => '2026-09-17 22:45:06',
    'expires_at' => '2027-09-18',
    'is_used' => 0,
    'used_by_student_id' => NULL,
    'used_at' => NULL,
    'created_at' => '2026-09-17 22:45:06',
    'updated_at' => '2026-09-17 22:45:06',
  ),
  4 => 
  array (
    'id' => 5,
    'code' => '8CRQTEYYO0',
    'approval_status' => 'rejected',
    'approved_at' => NULL,
    'expires_at' => NULL,
    'is_used' => 0,
    'used_by_student_id' => NULL,
    'used_at' => NULL,
    'created_at' => '2026-09-20 19:48:05',
    'updated_at' => '2026-09-20 19:49:21',
  ),
  5 => 
  array (
    'id' => 6,
    'code' => 'XYF5UVUWR2',
    'approval_status' => 'approved',
    'approved_at' => '2026-10-10 13:46:46',
    'expires_at' => NULL,
    'is_used' => 0,
    'used_by_student_id' => NULL,
    'used_at' => NULL,
    'created_at' => '2026-09-20 20:29:59',
    'updated_at' => '2026-10-10 13:46:46',
  ),
  6 => 
  array (
    'id' => 7,
    'code' => 'PNQVMED31S',
    'approval_status' => 'pending',
    'approved_at' => NULL,
    'expires_at' => NULL,
    'is_used' => 0,
    'used_by_student_id' => NULL,
    'used_at' => NULL,
    'created_at' => '2026-10-16 21:09:22',
    'updated_at' => '2026-10-16 21:09:22',
  ),
  7 => 
  array (
    'id' => 8,
    'code' => 'AIO3DREQ67',
    'approval_status' => 'pending',
    'approved_at' => NULL,
    'expires_at' => NULL,
    'is_used' => 0,
    'used_by_student_id' => NULL,
    'used_at' => NULL,
    'created_at' => '2026-10-16 21:13:41',
    'updated_at' => '2026-10-16 21:13:41',
  ),
  8 => 
  array (
    'id' => 9,
    'code' => '71OIPRA42T',
    'approval_status' => 'rejected',
    'approved_at' => NULL,
    'expires_at' => NULL,
    'is_used' => 0,
    'used_by_student_id' => NULL,
    'used_at' => NULL,
    'created_at' => '2026-10-16 21:19:52',
    'updated_at' => '2026-10-16 21:28:58',
  ),
  9 => 
  array (
    'id' => 10,
    'code' => 'PNGTIFE42M',
    'approval_status' => 'approved',
    'approved_at' => '2026-10-16 21:28:44',
    'expires_at' => NULL,
    'is_used' => 0,
    'used_by_student_id' => NULL,
    'used_at' => NULL,
    'created_at' => '2026-10-16 21:27:50',
    'updated_at' => '2026-10-16 21:28:44',
  ),
  10 => 
  array (
    'id' => 11,
    'code' => 'A3BF7WQ1YU',
    'approval_status' => 'pending',
    'approved_at' => NULL,
    'expires_at' => NULL,
    'is_used' => 0,
    'used_by_student_id' => NULL,
    'used_at' => NULL,
    'created_at' => '2026-10-16 21:47:23',
    'updated_at' => '2026-10-16 21:47:23',
  ),
  11 => 
  array (
    'id' => 12,
    'code' => 'MQ1JGYE87N',
    'approval_status' => 'approved',
    'approved_at' => '2026-10-17 20:57:23',
    'expires_at' => NULL,
    'is_used' => 0,
    'used_by_student_id' => NULL,
    'used_at' => NULL,
    'created_at' => '2026-10-16 21:50:20',
    'updated_at' => '2026-10-17 20:57:23',
  ),
  12 => 
  array (
    'id' => 13,
    'code' => 'B1FT3W0HIR',
    'approval_status' => 'approved',
    'approved_at' => '2026-10-17 20:15:12',
    'expires_at' => NULL,
    'is_used' => 0,
    'used_by_student_id' => NULL,
    'used_at' => NULL,
    'created_at' => '2026-10-17 19:58:30',
    'updated_at' => '2026-10-17 20:15:12',
  ),
  13 => 
  array (
    'id' => 14,
    'code' => 'Q431ZR8FB7',
    'approval_status' => 'approved',
    'approved_at' => '2026-10-17 21:02:19',
    'expires_at' => NULL,
    'is_used' => 0,
    'used_by_student_id' => NULL,
    'used_at' => NULL,
    'created_at' => '2026-10-17 21:02:17',
    'updated_at' => '2026-10-17 21:02:19',
  ),
  14 => 
  array (
    'id' => 15,
    'code' => 'M4IKBWJEFP',
    'approval_status' => 'approved',
    'approved_at' => '2026-10-17 21:30:35',
    'expires_at' => '2027-10-17',
    'is_used' => 0,
    'used_by_student_id' => NULL,
    'used_at' => NULL,
    'created_at' => '2026-10-17 21:30:35',
    'updated_at' => '2026-10-17 21:30:35',
  ),
  15 => 
  array (
    'id' => 16,
    'code' => 'FFCPVBUEBL',
    'approval_status' => 'approved',
    'approved_at' => '2026-10-17 21:46:17',
    'expires_at' => '2027-10-17',
    'is_used' => 0,
    'used_by_student_id' => NULL,
    'used_at' => NULL,
    'created_at' => '2026-10-17 21:46:17',
    'updated_at' => '2026-10-17 21:46:17',
  ),
));

        // Table: subscriptions (14 rows)
        DB::table('subscriptions')->truncate();
        DB::table('subscriptions')->insert(array (
  0 => 
  array (
    'id' => 3,
    'student_id' => 4,
    'subscription_code_id' => 3,
    'start_date' => '2026-09-20',
    'end_date' => NULL,
    'status' => 'active',
    'approval_status' => 'approved',
    'approved_at' => '2026-09-20 18:49:10',
    'created_at' => '2026-09-17 22:01:56',
    'updated_at' => '2026-09-20 18:49:10',
  ),
  1 => 
  array (
    'id' => 4,
    'student_id' => 5,
    'subscription_code_id' => 4,
    'start_date' => '2026-09-18',
    'end_date' => '2027-09-18',
    'status' => 'active',
    'approval_status' => 'approved',
    'approved_at' => '2026-09-17 22:45:06',
    'created_at' => '2026-09-17 22:45:06',
    'updated_at' => '2026-09-17 22:45:06',
  ),
  2 => 
  array (
    'id' => 5,
    'student_id' => 6,
    'subscription_code_id' => 5,
    'start_date' => NULL,
    'end_date' => NULL,
    'status' => 'cancelled',
    'approval_status' => 'rejected',
    'approved_at' => NULL,
    'created_at' => '2026-09-20 19:48:05',
    'updated_at' => '2026-09-20 19:49:21',
  ),
  3 => 
  array (
    'id' => 6,
    'student_id' => 7,
    'subscription_code_id' => 6,
    'start_date' => '2026-10-10',
    'end_date' => NULL,
    'status' => 'cancelled',
    'approval_status' => 'approved',
    'approved_at' => '2026-10-10 13:46:46',
    'created_at' => '2026-09-20 20:29:59',
    'updated_at' => '2026-10-17 21:13:17',
  ),
  4 => 
  array (
    'id' => 7,
    'student_id' => 8,
    'subscription_code_id' => 7,
    'start_date' => NULL,
    'end_date' => NULL,
    'status' => 'pending',
    'approval_status' => 'pending',
    'approved_at' => NULL,
    'created_at' => '2026-10-16 21:09:22',
    'updated_at' => '2026-10-16 21:09:22',
  ),
  5 => 
  array (
    'id' => 8,
    'student_id' => 9,
    'subscription_code_id' => 8,
    'start_date' => NULL,
    'end_date' => NULL,
    'status' => 'pending',
    'approval_status' => 'pending',
    'approved_at' => NULL,
    'created_at' => '2026-10-16 21:13:41',
    'updated_at' => '2026-10-16 21:13:41',
  ),
  6 => 
  array (
    'id' => 9,
    'student_id' => 10,
    'subscription_code_id' => 9,
    'start_date' => NULL,
    'end_date' => NULL,
    'status' => 'cancelled',
    'approval_status' => 'rejected',
    'approved_at' => NULL,
    'created_at' => '2026-10-16 21:19:52',
    'updated_at' => '2026-10-16 21:28:58',
  ),
  7 => 
  array (
    'id' => 10,
    'student_id' => 11,
    'subscription_code_id' => 10,
    'start_date' => '2026-10-16',
    'end_date' => NULL,
    'status' => 'active',
    'approval_status' => 'approved',
    'approved_at' => '2026-10-16 21:28:44',
    'created_at' => '2026-10-16 21:27:50',
    'updated_at' => '2026-10-16 21:28:44',
  ),
  8 => 
  array (
    'id' => 11,
    'student_id' => 12,
    'subscription_code_id' => 11,
    'start_date' => NULL,
    'end_date' => NULL,
    'status' => 'pending',
    'approval_status' => 'pending',
    'approved_at' => NULL,
    'created_at' => '2026-10-16 21:47:23',
    'updated_at' => '2026-10-16 21:47:23',
  ),
  9 => 
  array (
    'id' => 12,
    'student_id' => 13,
    'subscription_code_id' => 12,
    'start_date' => '2026-10-17',
    'end_date' => NULL,
    'status' => 'active',
    'approval_status' => 'approved',
    'approved_at' => '2026-10-17 20:57:23',
    'created_at' => '2026-10-16 21:50:20',
    'updated_at' => '2026-10-17 20:57:23',
  ),
  10 => 
  array (
    'id' => 13,
    'student_id' => 14,
    'subscription_code_id' => 13,
    'start_date' => '2026-10-17',
    'end_date' => NULL,
    'status' => 'cancelled',
    'approval_status' => 'approved',
    'approved_at' => '2026-10-17 20:15:12',
    'created_at' => '2026-10-17 19:58:30',
    'updated_at' => '2026-10-17 20:15:39',
  ),
  11 => 
  array (
    'id' => 14,
    'student_id' => 15,
    'subscription_code_id' => 14,
    'start_date' => '2026-10-17',
    'end_date' => NULL,
    'status' => 'cancelled',
    'approval_status' => 'approved',
    'approved_at' => '2026-10-17 21:02:19',
    'created_at' => '2026-10-17 21:02:17',
    'updated_at' => '2026-10-17 21:02:20',
  ),
  12 => 
  array (
    'id' => 15,
    'student_id' => 16,
    'subscription_code_id' => 15,
    'start_date' => '2026-10-17',
    'end_date' => '2027-10-17',
    'status' => 'active',
    'approval_status' => 'approved',
    'approved_at' => '2026-10-17 21:30:35',
    'created_at' => '2026-10-17 21:30:35',
    'updated_at' => '2026-10-17 21:30:35',
  ),
  13 => 
  array (
    'id' => 16,
    'student_id' => 17,
    'subscription_code_id' => 16,
    'start_date' => '2026-10-17',
    'end_date' => '2027-10-17',
    'status' => 'active',
    'approval_status' => 'approved',
    'approved_at' => '2026-10-17 21:46:17',
    'created_at' => '2026-10-17 21:46:17',
    'updated_at' => '2026-10-17 21:46:17',
  ),
));

        // Table: enrollments (14 rows)
        DB::table('enrollments')->truncate();
        DB::table('enrollments')->insert(array (
  0 => 
  array (
    'id' => 4,
    'student_id' => 4,
    'grade_id' => 1,
    'academic_year' => '2026/2027',
    'created_at' => '2026-09-17 22:01:56',
    'updated_at' => '2026-09-17 22:01:56',
  ),
  1 => 
  array (
    'id' => 5,
    'student_id' => 5,
    'grade_id' => 1,
    'academic_year' => '2026/2027',
    'created_at' => '2026-09-17 22:45:06',
    'updated_at' => '2026-09-17 22:45:06',
  ),
  2 => 
  array (
    'id' => 6,
    'student_id' => 6,
    'grade_id' => 1,
    'academic_year' => '2026/2027',
    'created_at' => '2026-09-20 19:48:05',
    'updated_at' => '2026-09-20 19:48:05',
  ),
  3 => 
  array (
    'id' => 7,
    'student_id' => 7,
    'grade_id' => 1,
    'academic_year' => '2026/2027',
    'created_at' => '2026-09-20 20:29:59',
    'updated_at' => '2026-09-20 20:29:59',
  ),
  4 => 
  array (
    'id' => 8,
    'student_id' => 8,
    'grade_id' => 1,
    'academic_year' => '2026',
    'created_at' => '2026-10-16 21:09:22',
    'updated_at' => '2026-10-16 21:09:22',
  ),
  5 => 
  array (
    'id' => 9,
    'student_id' => 9,
    'grade_id' => 1,
    'academic_year' => '2026',
    'created_at' => '2026-10-16 21:13:41',
    'updated_at' => '2026-10-16 21:13:41',
  ),
  6 => 
  array (
    'id' => 10,
    'student_id' => 10,
    'grade_id' => 1,
    'academic_year' => '2026',
    'created_at' => '2026-10-16 21:19:52',
    'updated_at' => '2026-10-16 21:19:52',
  ),
  7 => 
  array (
    'id' => 11,
    'student_id' => 11,
    'grade_id' => 1,
    'academic_year' => '2026',
    'created_at' => '2026-10-16 21:27:50',
    'updated_at' => '2026-10-16 21:27:50',
  ),
  8 => 
  array (
    'id' => 12,
    'student_id' => 12,
    'grade_id' => 1,
    'academic_year' => '2026',
    'created_at' => '2026-10-16 21:47:23',
    'updated_at' => '2026-10-16 21:47:23',
  ),
  9 => 
  array (
    'id' => 13,
    'student_id' => 13,
    'grade_id' => 1,
    'academic_year' => '2026',
    'created_at' => '2026-10-16 21:50:20',
    'updated_at' => '2026-10-16 21:50:20',
  ),
  10 => 
  array (
    'id' => 14,
    'student_id' => 14,
    'grade_id' => 1,
    'academic_year' => '2026',
    'created_at' => '2026-10-17 19:58:30',
    'updated_at' => '2026-10-17 19:58:30',
  ),
  11 => 
  array (
    'id' => 15,
    'student_id' => 15,
    'grade_id' => 1,
    'academic_year' => '2026',
    'created_at' => '2026-10-17 21:02:17',
    'updated_at' => '2026-10-17 21:02:17',
  ),
  12 => 
  array (
    'id' => 16,
    'student_id' => 16,
    'grade_id' => 1,
    'academic_year' => '2026/2027',
    'created_at' => '2026-10-17 21:30:35',
    'updated_at' => '2026-10-17 21:30:35',
  ),
  13 => 
  array (
    'id' => 17,
    'student_id' => 17,
    'grade_id' => 1,
    'academic_year' => '2026/2027',
    'created_at' => '2026-10-17 21:46:17',
    'updated_at' => '2026-10-17 21:46:17',
  ),
));

        // Table: videos (1 rows)
        DB::table('videos')->truncate();
        DB::table('videos')->insert(array (
  0 => 
  array (
    'id' => 5,
    'lesson_id' => 5,
    'title' => 'المصفوفات والمحددات',
    'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'sort_order' => 1,
    'duration_seconds' => 0,
    'completion_percentage' => 90,
    'is_active' => 1,
    'created_at' => '2026-10-17 22:00:52',
    'updated_at' => '2026-10-17 22:00:52',
  ),
));

        // Table: devices (10 rows)
        DB::table('devices')->truncate();
        DB::table('devices')->insert(array (
  0 => 
  array (
    'id' => 1,
    'subscription_id' => 3,
    'device_identifier' => 'device-alaa-001',
    'device_name' => 'Chrome on Windows',
    'platform' => 'web',
    'browser' => NULL,
    'operating_system' => NULL,
    'user_agent' => NULL,
    'ip_address' => NULL,
    'registered_at' => '2026-09-20 22:25:26',
    'last_seen_at' => '2026-09-20 19:17:36',
    'is_trusted' => 0,
    'deactivated_at' => '2026-09-20 19:25:26',
    'created_at' => '2026-09-20 19:17:36',
    'updated_at' => '2026-09-20 19:25:26',
  ),
  1 => 
  array (
    'id' => 2,
    'subscription_id' => 3,
    'device_identifier' => 'device-alaa-002',
    'device_name' => 'Chrome on Windows',
    'platform' => 'web',
    'browser' => NULL,
    'operating_system' => NULL,
    'user_agent' => NULL,
    'ip_address' => NULL,
    'registered_at' => '2026-09-20 23:36:52',
    'last_seen_at' => '2026-09-20 19:31:07',
    'is_trusted' => 0,
    'deactivated_at' => '2026-09-20 20:36:52',
    'created_at' => '2026-09-20 19:27:14',
    'updated_at' => '2026-09-20 20:36:52',
  ),
  2 => 
  array (
    'id' => 4,
    'subscription_id' => 3,
    'device_identifier' => '8f4c2e91-7a32-4d8b-91a2-5e6f3c8d1204',
    'device_name' => 'Desktop',
    'platform' => 'web',
    'browser' => 'Google Chrome',
    'operating_system' => 'Windows',
    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36',
    'ip_address' => '127.0.0.1',
    'registered_at' => '2026-09-22 16:22:25',
    'last_seen_at' => '2026-09-22 13:22:25',
    'is_trusted' => 1,
    'deactivated_at' => NULL,
    'created_at' => '2026-09-20 20:37:27',
    'updated_at' => '2026-09-22 13:22:25',
  ),
  3 => 
  array (
    'id' => 5,
    'subscription_id' => 3,
    'device_identifier' => '11111111-2222-4333-8444-555555555555',
    'device_name' => 'Desktop',
    'platform' => 'web',
    'browser' => 'Google Chrome',
    'operating_system' => 'Windows',
    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36',
    'ip_address' => '127.0.0.1',
    'registered_at' => '2026-09-21 12:57:23',
    'last_seen_at' => '2026-09-20 21:08:58',
    'is_trusted' => 0,
    'deactivated_at' => '2026-09-21 09:57:23',
    'created_at' => '2026-09-20 21:08:58',
    'updated_at' => '2026-09-21 09:57:23',
  ),
  4 => 
  array (
    'id' => 6,
    'subscription_id' => 10,
    'device_identifier' => '79b9d0aa-43a8-4b9c-9400-7321e055c643',
    'device_name' => 'Desktop',
    'platform' => 'web',
    'browser' => 'Google Chrome',
    'operating_system' => 'Windows',
    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',
    'ip_address' => '127.0.0.1',
    'registered_at' => '2026-10-17 22:57:01',
    'last_seen_at' => '2026-10-17 20:57:01',
    'is_trusted' => 1,
    'deactivated_at' => NULL,
    'created_at' => '2026-10-16 23:39:08',
    'updated_at' => '2026-10-17 20:57:01',
  ),
  5 => 
  array (
    'id' => 7,
    'subscription_id' => 12,
    'device_identifier' => '79b9d0aa-43a8-4b9c-9400-7321e055c643',
    'device_name' => 'Desktop',
    'platform' => 'web',
    'browser' => 'Google Chrome',
    'operating_system' => 'Windows',
    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',
    'ip_address' => '127.0.0.1',
    'registered_at' => '2026-10-26 03:10:39',
    'last_seen_at' => '2026-10-17 22:28:49',
    'is_trusted' => 0,
    'deactivated_at' => '2026-10-26 00:10:39',
    'created_at' => '2026-10-17 22:28:12',
    'updated_at' => '2026-10-26 00:10:39',
  ),
  6 => 
  array (
    'id' => 8,
    'subscription_id' => 16,
    'device_identifier' => '19451be2-66bf-4283-b240-e576486b8b1e',
    'device_name' => 'Desktop',
    'platform' => 'web',
    'browser' => 'Unknown Browser',
    'operating_system' => 'Unknown OS',
    'user_agent' => 'node',
    'ip_address' => '127.0.0.1',
    'registered_at' => '2026-10-26 02:57:14',
    'last_seen_at' => '2026-10-17 22:32:46',
    'is_trusted' => 0,
    'deactivated_at' => '2026-10-25 23:57:14',
    'created_at' => '2026-10-17 22:32:45',
    'updated_at' => '2026-10-25 23:57:14',
  ),
  7 => 
  array (
    'id' => 9,
    'subscription_id' => 15,
    'device_identifier' => '79b9d0aa-43a8-4b9c-9400-7321e055c643',
    'device_name' => 'Desktop',
    'platform' => 'web',
    'browser' => 'Google Chrome',
    'operating_system' => 'Windows',
    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',
    'ip_address' => '127.0.0.1',
    'registered_at' => '2026-10-26 03:10:34',
    'last_seen_at' => '2026-10-17 22:41:58',
    'is_trusted' => 0,
    'deactivated_at' => '2026-10-26 00:10:34',
    'created_at' => '2026-10-17 22:40:58',
    'updated_at' => '2026-10-26 00:10:34',
  ),
  8 => 
  array (
    'id' => 10,
    'subscription_id' => 16,
    'device_identifier' => '036577df-5c45-4ec6-8f37-e01ff912b817',
    'device_name' => 'Desktop',
    'platform' => 'web',
    'browser' => 'Google Chrome',
    'operating_system' => 'Windows',
    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',
    'ip_address' => '127.0.0.1',
    'registered_at' => '2026-10-26 03:14:13',
    'last_seen_at' => '2026-10-26 00:14:13',
    'is_trusted' => 1,
    'deactivated_at' => NULL,
    'created_at' => '2026-10-25 23:57:31',
    'updated_at' => '2026-10-26 00:14:13',
  ),
  9 => 
  array (
    'id' => 11,
    'subscription_id' => 12,
    'device_identifier' => '036577df-5c45-4ec6-8f37-e01ff912b817',
    'device_name' => 'Desktop',
    'platform' => 'web',
    'browser' => 'Google Chrome',
    'operating_system' => 'Windows',
    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',
    'ip_address' => '127.0.0.1',
    'registered_at' => '2026-10-26 03:11:30',
    'last_seen_at' => '2026-10-26 00:11:30',
    'is_trusted' => 1,
    'deactivated_at' => NULL,
    'created_at' => '2026-10-26 00:10:51',
    'updated_at' => '2026-10-26 00:11:30',
  ),
));

        // Table: exam_attempts (1 rows)
        DB::table('exam_attempts')->truncate();
        DB::table('exam_attempts')->insert(array (
  0 => 
  array (
    'id' => 1,
    'student_id' => 4,
    'exam_id' => 3,
    'subscription_id' => 3,
    'score' => 0,
    'total_score' => 2,
    'percentage' => '0.00',
    'is_passed' => 0,
    'started_at' => '2026-09-22 13:21:52',
    'submitted_at' => NULL,
    'created_at' => '2026-09-22 13:21:52',
    'updated_at' => '2026-09-22 13:21:52',
  ),
));

        // Table: lesson_progress (1 rows)
        DB::table('lesson_progress')->truncate();
        DB::table('lesson_progress')->insert(array (
  0 => 
  array (
    'id' => 1,
    'student_id' => 4,
    'subscription_id' => 3,
    'lesson_id' => 1,
    'status' => 'completed',
    'completed_at' => '2026-09-22 08:21:55',
    'created_at' => '2026-09-22 08:21:55',
    'updated_at' => '2026-09-22 08:21:55',
  ),
));

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
