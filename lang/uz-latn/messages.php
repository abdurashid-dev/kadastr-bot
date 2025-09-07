<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Messages
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default messages used by
    | the application. You are free to modify these language lines according
    | to your application's requirements.
    |
    */

    // General
    'welcome' => 'Xush kelibsiz',
    'dashboard' => 'Boshqaruv paneli',
    'users' => 'Foydalanuvchilar',
    'files' => 'Fayllar',
    'settings' => 'Sozlamalar',
    'logout' => 'Chiqish',
    'login' => 'Kirish',
    'register' => 'Ro\'yxatdan o\'tish',
    'save' => 'Saqlash',
    'cancel' => 'Bekor qilish',
    'edit' => 'Tahrirlash',
    'delete' => 'O\'chirish',
    'create' => 'Yaratish',
    'update' => 'Yangilash',
    'search' => 'Qidirish',
    'filter' => 'Filtr',
    'clear' => 'Tozalash',
    'submit' => 'Yuborish',
    'back' => 'Orqaga',
    'next' => 'Keyingi',
    'previous' => 'Oldingi',
    'loading' => 'Yuklanmoqda...',
    'no_data' => 'Ma\'lumot yo\'q',
    'confirm' => 'Tasdiqlash',
    'yes' => 'Ha',
    'no' => 'Yo\'q',
    'success' => 'Muvaffaqiyat',
    'error' => 'Xatolik',
    'warning' => 'Ogohlantirish',
    'info' => 'Ma\'lumot',

    // User Management
    'user_management' => 'Foydalanuvchilarni boshqarish',
    'create_user' => 'Foydalanuvchi yaratish',
    'edit_user' => 'Foydalanuvchini tahrirlash',
    'user_details' => 'Foydalanuvchi tafsilotlari',
    'name' => 'Ism',
    'email' => 'Elektron pochta',
    'phone' => 'Telefon',
    'region' => 'Viloyat',
    'role' => 'Rol',
    'telegram_id' => 'Telegram ID',
    'created_at' => 'Yaratilgan vaqt',
    'updated_at' => 'Yangilangan vaqt',
    'actions' => 'Amallar',
    'user_created_successfully' => 'Foydalanuvchi muvaffaqiyatli yaratildi',
    'user_updated_successfully' => 'Foydalanuvchi muvaffaqiyatli yangilandi',
    'user_deleted_successfully' => 'Foydalanuvchi muvaffaqiyatli o\'chirildi',

    // File Management
    'file_management' => 'Fayllarni boshqarish',
    'upload_file' => 'Fayl yuklash',
    'file_details' => 'Fayl tafsilotlari',
    'file_name' => 'Fayl nomi',
    'original_filename' => 'Asl fayl nomi',
    'file_type' => 'Fayl turi',
    'file_size' => 'Fayl hajmi',
    'status' => 'Holat',
    'admin_notes' => 'Admin eslatmalari',
    'uploaded_by' => 'Yuklagan',
    'upload_date' => 'Yuklangan sana',
    'approve' => 'Tasdiqlash',
    'reject' => 'Rad etish',
    'pending' => 'Kutilmoqda',
    'waiting' => 'Kutish',
    'accepted' => 'Qabul qilingan',
    'rejected' => 'Rad etilgan',
    'file_approved_successfully' => 'Fayl muvaffaqiyatli tasdiqlandi',
    'file_rejected_successfully' => 'Fayl muvaffaqiyatli rad etildi',
    'file_status_updated' => 'Fayl holati yangilandi',

    // Roles
    'admin' => 'Admin',
    'checker' => 'Tekshiruvchi',
    'registrator' => 'Ro\'yxatga oluvchi',
    'user' => 'Foydalanuvchi',

    // Regions
    'regions' => 'Viloyatlar',
    'select_region' => 'Viloyatni tanlang',

    // Validation Messages
    'required' => 'Bu maydon majburiy',
    'email_invalid' => 'Iltimos, to\'g\'ri elektron pochta manzilini kiriting',
    'phone_invalid' => 'Iltimos, to\'g\'ri telefon raqamini kiriting',
    'password_min' => 'Parol kamida :min belgidan iborat bo\'lishi kerak',
    'password_confirmed' => 'Parol tasdiqlanishi mos kelmaydi',

    // Statistics
    'statistics' => 'Statistika',
    'total_files' => 'Jami fayllar',
    'pending_files' => 'Kutilayotgan fayllar',
    'waiting_files' => 'Kutish holatidagi fayllar',
    'accepted_files' => 'Qabul qilingan fayllar',
    'rejected_files' => 'Rad etilgan fayllar',

    // Date Filters
    'date_from' => 'Boshlanish sanasi',
    'date_to' => 'Tugash sanasi',
    'today' => 'Bugun',
    'yesterday' => 'Kecha',
    'this_week' => 'Bu hafta',
    'this_month' => 'Bu oy',
    'all_time' => 'Barcha vaqt',

    // Language Demo
    'current_language' => 'Joriy til',
    'selected_locale' => 'Tanlangan til',
    'available_languages' => 'Mavjud tillar',

    // Welcome Page
    'welcome_title' => 'Farg\'ona Kadastr',
    'welcome_subtitle' => 'Farg\'ona viloyati kadastr tizimi. Hujjatlarni yuklash va boshqarish uchun tizimga kiring.',
    'login_button' => 'Tizimga kirish',
    'register_button' => 'Ro\'yxatdan o\'tish',
    'dashboard_button' => 'Boshqaruv paneli',

    // Region Names (Farg'ona viloyati)
    'regions_list' => [
        'Quvasoy shahar' => 'Quvasoy shahar',
        'Farg\'ona shahar' => 'Farg\'ona shahar',
        'Qo\'qon shahar' => 'Qo\'qon shahar',
        'Marg\'ilon shahar' => 'Marg\'ilon shahar',
        'Beshariq tuman' => 'Beshariq tuman',
        'Bog\'dod tuman' => 'Bog\'dod tuman',
        'Buvayda tuman' => 'Buvayda tuman',
        'Dang\'ara tuman' => 'Dang\'ara tuman',
        'Yozyovon tuman' => 'Yozyovon tuman',
        'Quva tuman' => 'Quva tuman',
        'Oltiariq tuman' => 'Oltiariq tuman',
        'Qo\'shtepa tuman' => 'Qo\'shtepa tuman',
        'Rishton tuman' => 'Rishton tuman',
        'Toshloq tuman' => 'Toshloq tuman',
        'O\'zbekiston tuman' => 'O\'zbekiston tuman',
        'Uchko\'prik tuman' => 'Uchko\'prik tuman',
        'Farg\'ona tuman' => 'Farg\'ona tuman',
        'Furqat tuman' => 'Furqat tuman',
        'Sox tuman' => 'Sox tuman',
    ],
];
