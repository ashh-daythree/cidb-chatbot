<?php

declare(strict_types=1);

return [
    'PPK' => [
        'label_en' => 'PPK',
        'label_ms' => 'PPK',
        'subtopics' => [
            ['code' => 'PPK_GENERAL', 'label_en' => 'General', 'label_ms' => 'Umum', 'sort_order' => 1],
            ['code' => 'PPK_RENEWAL_PROCEDURE', 'label_en' => 'Renewal Procedure', 'label_ms' => 'Prosedur Pembaharuan', 'sort_order' => 2],
            ['code' => 'PPK_SCORE_EVALUATION', 'label_en' => 'SCORE Evaluation', 'label_ms' => 'Penilaian SCORE', 'sort_order' => 3],
            ['code' => 'PPK_CCD_POINTS', 'label_en' => 'CCD Points', 'label_ms' => 'Mata CCD', 'sort_order' => 4],
            ['code' => 'PPK_DOCUMENT_REQUIREMENTS', 'label_en' => 'Document Requirements', 'label_ms' => 'Keperluan Dokumen', 'sort_order' => 5],
            ['code' => 'PPK_APPLICATION_DURATION', 'label_en' => 'Application Process Duration', 'label_ms' => 'Tempoh Proses Permohonan', 'sort_order' => 6],
        ],
        'questions' => [
            ['subtopic_code' => 'PPK_GENERAL', 'sort_order' => 1,
                'question_en' => 'What is PPK?', 'answer_en' => 'PPK is the Perakuan Pendaftaran Kontraktor issued by CIDB to registered contractors in Malaysia.',
                'question_ms' => 'Apakah PPK?', 'answer_ms' => 'PPK ialah Perakuan Pendaftaran Kontraktor yang dikeluarkan oleh CIDB kepada kontraktor yang berdaftar di Malaysia.'],
            ['subtopic_code' => 'PPK_GENERAL', 'sort_order' => 2,
                'question_en' => 'Do I need to visit a CIDB office to renew my PPK?', 'answer_en' => 'No. PPK renewal can be done online through CIMS CIDB.

https://cims.cidb.gov.my/',
                'question_ms' => 'Perlukah saya hadir ke pejabat CIDB untuk memperbaharui PPK?', 'answer_ms' => 'Tidak perlu. Pembaharuan PPK boleh dibuat secara dalam talian melalui CIMS CIDB.

https://cims.cidb.gov.my/'],
            ['subtopic_code' => 'PPK_GENERAL', 'sort_order' => 3,
                'question_en' => 'Where can I renew my PPK?', 'answer_en' => 'PPK renewal can be done online through CIMS CIDB.

https://cims.cidb.gov.my/',
                'question_ms' => 'Di manakah saya boleh memperbaharui PPK?', 'answer_ms' => 'Pembaharuan PPK boleh dibuat secara dalam talian melalui CIMS CIDB.

https://cims.cidb.gov.my/'],
            ['subtopic_code' => 'PPK_GENERAL', 'sort_order' => 4,
                'question_en' => 'Who needs to apply for PPK?', 'answer_en' => 'Any party carrying out or claiming to carry out construction work in Malaysia, including local and foreign contractors, is subject to CIDB registration requirements.',
                'question_ms' => 'Siapa yang perlu memohon PPK ini?', 'answer_ms' => 'Mana-mana pihak yang menjalankan atau mengaku menjalankan kerja-kerja pembinaan di Malaysia, termasuk kontraktor tempatan dan kontraktor asing, tertakluk kepada keperluan pendaftaran CIDB.'],
            ['subtopic_code' => 'PPK_GENERAL', 'sort_order' => 5,
                'question_en' => 'Can I renew PPK, SPKK and STB at the same time?', 'answer_en' => 'No. The applications must be completed in stages. Applicants must ensure that their PPK is active before proceeding with the SPKK application. The same applies to the STB application.',
                'question_ms' => 'Bolehkah saya memperbaharui PPK, SPKK dan STB secara serentak?', 'answer_ms' => 'Tidak. Permohonan perlu dilakukan secara berperingkat. Pemohon perlu memastikan PPK masih aktif terlebih dahulu sebelum meneruskan permohonan SPKK. Perkara yang sama terpakai bagi permohonan STB.'],
            ['subtopic_code' => 'PPK_RENEWAL_PROCEDURE', 'sort_order' => 1,
                'question_en' => 'How do I start a PPK renewal application?', 'answer_en' => 'Log in to CIMS, go to Registration and select the PPK renewal option. Complete the required information, declarations and supporting requirements shown by the system.',
                'question_ms' => 'Bagaimanakah saya boleh memulakan permohonan pembaharuan PPK?', 'answer_ms' => 'Sila log masuk ke sistem CIMS, pergi ke menu Pendaftaran dan pilih pilihan Pembaharuan PPK. Lengkapkan maklumat, pengakuan dan keperluan sokongan yang diperlukan seperti yang dipaparkan oleh sistem.'],
            ['subtopic_code' => 'PPK_GENERAL', 'sort_order' => 6,
                'question_en' => "Why can't I see the PPK renewal option in CIMS?", 'answer_en' => "The PPK renewal option may not be displayed because there are prerequisites or mandatory requirements that have not yet been fulfilled, based on CIDB's renewal manual. Please ensure that all renewal requirements have been fulfilled before submitting your renewal application through the CIMS system.",
                'question_ms' => 'Mengapa saya tidak dapat melihat pilihan pembaharuan PPK dalam sistem CIMS?', 'answer_ms' => 'Pilihan pembaharuan PPK mungkin tidak dipaparkan kerana terdapat prasyarat atau keperluan wajib yang masih belum dipenuhi, berdasarkan manual pembaharuan CIDB. Sila pastikan semua syarat pembaharuan telah dipenuhi terlebih dahulu sebelum membuat permohonan pembaharuan melalui sistem CIMS.'],
            ['subtopic_code' => 'PPK_RENEWAL_PROCEDURE', 'sort_order' => 2,
                'question_en' => 'Can I apply for a grade upgrade while my PPK registration is still active?', 'answer_en' => 'Yes, you may apply for a grade upgrade while your PPK is still active, provided that you meet the requirements set by CIDB.',
                'question_ms' => 'Bolehkah saya memohon untuk naik gred semasa pendaftaran PPK saya masih aktif?', 'answer_ms' => 'Ya, anda boleh memohon naik gred walaupun PPK masih aktif, sekiranya memenuhi syarat dan keperluan yang ditetapkan oleh CIDB.'],
            ['subtopic_code' => 'PPK_APPLICATION_DURATION', 'sort_order' => 1,
                'question_en' => 'How long do I have to complete and submit the application?', 'answer_en' => 'Applicants have 14 days to complete and submit the PPK application from the date the application is first created. If the application is not submitted within this period, it will be automatically cancelled by the system.',
                'question_ms' => 'Berapa lama tempoh yang diberikan untuk melengkapkan dan menghantar permohonan?', 'answer_ms' => 'Pemohon dibenarkan melengkapkan permohonan PPK dalam tempoh 14 hari dari tarikh permohonan pertama kali dicipta. Sekiranya permohonan tidak dihantar dalam tempoh tersebut, permohonan akan dibatalkan secara automatik oleh sistem.'],
            ['subtopic_code' => 'PPK_APPLICATION_DURATION', 'sort_order' => 2,
                'question_en' => 'What happens if a renewal application is not submitted within 14 days?', 'answer_en' => 'The application will be automatically cancelled by the system if it is not submitted within 14 days.',
                'question_ms' => 'Apakah yang berlaku sekiranya permohonan pembaharuan tidak dihantar dalam tempoh 14 hari?', 'answer_ms' => 'Permohonan akan dibatalkan secara automatik oleh sistem sekiranya tidak dihantar dalam tempoh 14 hari.'],
            ['subtopic_code' => 'PPK_GENERAL', 'sort_order' => 7,
                'question_en' => 'What are the requirements for PPK renewal?', 'answer_en' => "PPK renewal requirements depend on the contractor's grade and applicable requirements, including CCD Points courses, training courses, competency courses, mandatory courses and SCORE/MCORE, depending on the contractor's grade.",
                'question_ms' => 'Apakah syarat-syarat yang perlu dipenuhi untuk pembaharuan PPK?', 'answer_ms' => 'Syarat pembaharuan PPK bergantung kepada gred kontraktor dan keperluan yang berkaitan, termasuk kursus Mata CCD, kursus latihan, kursus kompetensi, kursus wajib serta SCORE/MCORE, mengikut gred kontraktor.'],
            ['subtopic_code' => 'PPK_DOCUMENT_REQUIREMENTS', 'sort_order' => 1,
                'question_en' => 'Do I need to upload SSM documents?', 'answer_en' => 'The requirement to upload SSM documents depends on the type of application and the changes made. Please refer to the supporting document request displayed in CIMS and upload the required documents.',
                'question_ms' => 'Adakah saya perlu memuat naik dokumen SSM untuk permohonan ini?', 'answer_ms' => 'Keperluan untuk memuat naik dokumen SSM bergantung pada jenis permohonan dan perubahan yang dibuat. Sila rujuk permintaan dokumen sokongan yang dipaparkan dalam sistem CIMS dan muat naik dokumen yang diperlukan.'],
            ['subtopic_code' => 'PPK_DOCUMENT_REQUIREMENTS', 'sort_order' => 2,
                'question_en' => 'My company is registered in Sabah/Sarawak. Do I need to attach the state registration document when applying for PPK renewal?', 'answer_en' => 'Yes. For companies in Sarawak registered using Business Registration, the registration document must be attached when applying for PPK renewal through CIMS.',
                'question_ms' => 'Syarikat saya berdaftar di Negeri Sabah/Sarawak. Adakah saya perlu melampirkan dokumen pendaftaran negeri semasa membuat permohonan pembaharuan PPK?', 'answer_ms' => 'Ya. Bagi syarikat di Sarawak yang berdaftar menggunakan Pendaftaran Perniagaan, dokumen pendaftaran tersebut perlu dilampirkan semasa membuat permohonan pembaharuan PPK dalam CIMS.'],
            ['subtopic_code' => 'PPK_RENEWAL_PROCEDURE', 'sort_order' => 3,
                'question_en' => 'Can I update company information during renewal?', 'answer_en' => 'Yes. Applicants can update company information during the PPK renewal process and upload the required supporting documents.',
                'question_ms' => 'Bolehkah saya mengemas kini maklumat syarikat semasa proses pembaharuan PPK?', 'answer_ms' => 'Ya. Pemohon boleh mengemas kini maklumat syarikat semasa proses pembaharuan PPK serta memuat naik dokumen sokongan yang diperlukan.'],
            ['subtopic_code' => 'PPK_RENEWAL_PROCEDURE', 'sort_order' => 4,
                'question_en' => 'My company address has changed. What should I do?', 'answer_en' => 'Please update the company address through the CIMS system and ensure that the updated information is accurate and up to date.',
                'question_ms' => 'Alamat syarikat saya telah berubah. Apakah yang perlu saya lakukan?', 'answer_ms' => 'Sila kemas kini alamat syarikat melalui sistem CIMS dan pastikan maklumat yang dikemas kini adalah tepat dan terkini.'],
            ['subtopic_code' => 'PPK_RENEWAL_PROCEDURE', 'sort_order' => 5,
                'question_en' => 'My director or shareholder has changed. Can I still renew?', 'answer_en' => 'Yes. You can renew your PPK and update the director or shareholder information during the PPK renewal process.',
                'question_ms' => 'Pengarah atau pemegang saham syarikat saya telah berubah. Adakah saya masih boleh membuat pembaharuan PPK?', 'answer_ms' => 'Ya. Anda boleh membuat pembaharuan PPK dan mengemas kini maklumat pengarah atau pemegang saham semasa proses pembaharuan PPK.'],
            ['subtopic_code' => 'PPK_RENEWAL_PROCEDURE', 'sort_order' => 6,
                'question_en' => 'Can I change my contractor grade during PPK renewal?', 'answer_en' => 'Yes. G2 to G7 contractors can change their grade during the PPK renewal process. Only G1 contractors cannot change their grade during the PPK renewal process.',
                'question_ms' => 'Bolehkah saya menukar gred kontraktor semasa proses pembaharuan PPK?', 'answer_ms' => 'Ya. Kontraktor G2 hingga G7 boleh menukar gred semasa proses pembaharuan PPK. Hanya kontraktor G1 tidak boleh menukar gred semasa proses pembaharuan PPK.'],
            ['subtopic_code' => 'PPK_RENEWAL_PROCEDURE', 'sort_order' => 7,
                'question_en' => 'Can I add a category or specialization when renewing?', 'answer_en' => 'Yes, you can. Only categories and specializations supported by certificates can be added during PPK renewal, subject to the applicable qualification and competency requirements. Specializations using an LOA cannot be added.',
                'question_ms' => 'Bolehkah saya menambah kategori atau pengkhususan semasa proses pembaharuan PPK?', 'answer_ms' => 'Boleh. Hanya penambahan kategori dan pengkhususan menggunakan sijil dibenarkan semasa pembaharuan PPK, tertakluk kepada syarat kelayakan dan kompetensi yang ditetapkan. Penambahan pengkhususan menggunakan LOA tidak dibenarkan.'],
            ['subtopic_code' => 'PPK_CCD_POINTS', 'sort_order' => 1,
                'question_en' => 'Do I need CCD points for PPK renewal?', 'answer_en' => "Yes. CCD points are required for PPK renewal. The required number of CCD points depends on the contractor's grade.",
                'question_ms' => 'Adakah saya perlu mempunyai mata CCD untuk pembaharuan PPK?', 'answer_ms' => 'Ya. Mata CCD diperlukan untuk pembaharuan PPK. Jumlah mata CCD yang diperlukan bergantung kepada gred kontraktor.'],
            ['subtopic_code' => 'PPK_CCD_POINTS', 'sort_order' => 2,
                'question_en' => 'Whose CCD points can be counted?', 'answer_en' => "CCD points obtained by the company's owner, director or employed personnel can be counted, including certificates obtained by the company, subject to CIDB requirements.",
                'question_ms' => 'Mata CCD siapa yang boleh diambil kira untuk pembaharuan PPK?', 'answer_ms' => 'Mata CCD yang diperoleh oleh pemilik, pengarah atau personel yang bekerja dengan syarikat boleh diambil kira, termasuk sijil yang diperoleh oleh syarikat, tertakluk kepada keperluan CIDB.'],
            ['subtopic_code' => 'PPK_CCD_POINTS', 'sort_order' => 3,
                'question_en' => 'Why are my CCD points not accepted by the system?', 'answer_en' => 'Applicants must ensure that the course information and details match those stated on the CCD certificate.',
                'question_ms' => 'Mengapa mata CCD saya tidak diterima oleh sistem?', 'answer_ms' => 'Pemohon perlu memastikan maklumat dan butiran kursus Mata CCD sepadan dengan maklumat yang tertera pada sijil Mata CCD.'],
            ['subtopic_code' => 'PPK_CCD_POINTS', 'sort_order' => 4,
                'question_en' => 'Can I use CCD points from an employee?', 'answer_en' => 'Yes, provided that the employee works for the company and is registered for EPF contributions under the company.',
                'question_ms' => 'Bolehkah saya menggunakan mata CCD yang diperoleh oleh pekerja syarikat?', 'answer_ms' => 'Boleh, sekiranya pekerja tersebut bekerja dengan syarikat dan dicarum KWSP oleh syarikat.'],
            ['subtopic_code' => 'PPK_CCD_POINTS', 'sort_order' => 5,
                'question_en' => 'Does the director need to attend a course before the first PPK renewal?', 'answer_en' => 'Yes. The director must attend the mandatory course stated in the PPK registration approval letter before renewing the PPK.',
                'question_ms' => 'Adakah pengarah perlu menghadiri kursus sebelum membuat pembaharuan PPK kali pertama?', 'answer_ms' => 'Ya. Pengarah perlu menghadiri kursus wajib seperti yang dinyatakan dalam surat kelulusan pendaftaran PPK sebelum membuat pembaharuan PPK.'],
            ['subtopic_code' => 'PPK_CCD_POINTS', 'sort_order' => 6,
                'question_en' => "Why can't I enter my CCD points for my PPK renewal application?", 'answer_en' => 'Applicants must meet the prerequisites for updating CCD points, such as having a valid SCORE certificate.',
                'question_ms' => 'Mengapa saya tidak boleh mengisi butiran mata CCD bagi permohonan pembaharuan PPK saya?', 'answer_ms' => 'Pemohon perlu memenuhi prasyarat sebelum mengemas kini mata CCD, seperti mempunyai sijil SCORE yang sah.'],
            ['subtopic_code' => 'PPK_SCORE_EVALUATION', 'sort_order' => 1,
                'question_en' => 'What is MCORE?', 'answer_en' => 'MCORE is the Micro Competitiveness Rating for Enhancement, a CIDB assessment programme specifically for G1 contractors.',
                'question_ms' => 'Apa itu MCORE?', 'answer_ms' => 'MCORE ialah Micro Competitiveness Rating for Enhancement, iaitu program penilaian khas CIDB bagi kontraktor gred G1.'],
            ['subtopic_code' => 'PPK_SCORE_EVALUATION', 'sort_order' => 2,
                'question_en' => 'Does a G1 contractor need MCORE to renew PPK?', 'answer_en' => 'Yes.',
                'question_ms' => 'Adakah kontraktor G1 memerlukan MCORE untuk memperbaharui PPK?', 'answer_ms' => 'Ya.'],
            ['subtopic_code' => 'PPK_SCORE_EVALUATION', 'sort_order' => 3,
                'question_en' => 'What MCORE rating does G1 need for renewal?', 'answer_en' => 'G1 must have a valid MCORE for PPK renewal. No minimum rating level is required.',
                'question_ms' => 'Apakah penarafan MCORE yang diperlukan oleh G1 untuk pembaharuan?', 'answer_ms' => 'G1 perlu mempunyai MCORE yang masih sah untuk pembaharuan PPK. Tiada tahap penarafan minimum ditetapkan.'],
            ['subtopic_code' => 'PPK_SCORE_EVALUATION', 'sort_order' => 4,
                'question_en' => 'What is SCORE?', 'answer_en' => "SCORE is the Contractor Capability and Capability Assessment Programme developed by CIDB to measure contractors' capability and capacity.",
                'question_ms' => 'Apa itu SCORE?', 'answer_ms' => 'SCORE ialah Program Penilaian Keupayaan dan Kemampuan Kontraktor yang dibangunkan oleh CIDB untuk mengukur keupayaan dan kemampuan kontraktor.'],
            ['subtopic_code' => 'PPK_SCORE_EVALUATION', 'sort_order' => 5,
                'question_en' => 'Do G2-G7 contractors need SCORE for PPK renewal?', 'answer_en' => 'Yes. Effective 1 July 2024, SCORE assessment is mandatory for PPK renewal / re-registration applications for contractors in grades G2 to G7.',
                'question_ms' => 'Adakah kontraktor G2-G7 memerlukan SCORE untuk pembaharuan PPK?', 'answer_ms' => 'Ya. Berkuat kuasa 1 Julai 2024, penilaian SCORE diwajibkan bagi permohonan pembaharuan / pendaftaran semula PPK kontraktor gred G2 hingga G7.'],
            ['subtopic_code' => 'PPK_SCORE_EVALUATION', 'sort_order' => 6,
                'question_en' => 'Does G7 need SCORE to renew PPK?', 'answer_en' => 'Yes. G7 requires a valid SCORE for PPK renewal / re-registration, with no specific star-rating condition for that requirement.',
                'question_ms' => 'Adakah G7 memerlukan SCORE untuk memperbaharui PPK?', 'answer_ms' => 'Ya. G7 memerlukan SCORE yang sah untuk pembaharuan / pendaftaran semula PPK, tanpa syarat penarafan bintang tertentu bagi syarat tersebut.'],
            ['subtopic_code' => 'PPK_SCORE_EVALUATION', 'sort_order' => 7,
                'question_en' => 'What SCORE star rating is required for G7 renewal?', 'answer_en' => 'No minimum SCORE star rating is required for G7 PPK renewal.',
                'question_ms' => 'Apakah penarafan bintang SCORE yang diperlukan untuk pembaharuan G7?', 'answer_ms' => 'Tiada penarafan bintang minimum SCORE ditetapkan untuk pembaharuan PPK G7.'],
            ['subtopic_code' => 'PPK_SCORE_EVALUATION', 'sort_order' => 8,
                'question_en' => 'My SCORE has expired. Can I renew PPK?', 'answer_en' => 'No. SCORE must be renewed before applying for PPK renewal.',
                'question_ms' => 'SCORE saya telah tamat tempoh. Bolehkah saya memperbaharui PPK?', 'answer_ms' => 'Tidak. SCORE perlu diperbaharui terlebih dahulu sebelum membuat pembaharuan PPK.'],
            ['subtopic_code' => 'PPK_RENEWAL_PROCEDURE', 'sort_order' => 8,
                'question_en' => 'How much is the PPK renewal processing fee?', 'answer_en' => 'The PPK renewal processing fee is RM30 if the PPK is still within its registration validity period. If the PPK has expired, the processing fee is RM50.',
                'question_ms' => 'Berapakah yuran pemprosesan pembaharuan PPK?', 'answer_ms' => 'Yuran pemprosesan pembaharuan PPK ialah RM30 sekiranya PPK masih dalam tempoh sah pendaftaran. Jika PPK telah tamat tempoh, yuran pemprosesan ialah RM50.'],
            ['subtopic_code' => 'PPK_RENEWAL_PROCEDURE', 'sort_order' => 9,
                'question_en' => 'How much is the PPK renewal registration fee?', 'answer_en' => "The PPK registration fee depends on the contractor's grade and renewal period. For renewals made before the PPK expires, a 50% discount will be given if there is no grade change.",
                'question_ms' => 'Berapakah yuran pendaftaran pembaharuan PPK?', 'answer_ms' => 'Yuran pendaftaran PPK bergantung kepada gred kontraktor dan tempoh pembaharuan. Bagi pembaharuan yang dibuat sebelum PPK tamat tempoh, potongan 50% akan diberikan sekiranya tiada perubahan gred dibuat.'],
            ['subtopic_code' => 'PPK_RENEWAL_PROCEDURE', 'sort_order' => 10,
                'question_en' => 'How much is the registration fee by grade?', 'answer_en' => 'The annual registration fees are as follows:
- G1: RM20
- G2: RM80
- G3: RM150
- G4: RM350
- G5: RM700
- G6: RM1,000
- G7: RM1,400

For renewal applications submitted before the PPK expires without a grade change, a 50% discount on the registration fee will be given.',
                'question_ms' => 'Berapakah yuran pendaftaran mengikut gred?', 'answer_ms' => 'Yuran pendaftaran setahun adalah seperti berikut:
- G1: RM20
- G2: RM80
- G3: RM150
- G4: RM350
- G5: RM700
- G6: RM1,000
- G7: RM1,400

Bagi permohonan pembaharuan yang dibuat sebelum PPK tamat tempoh tanpa perubahan gred, diskaun 50% daripada yuran pendaftaran akan diberikan.'],
            ['subtopic_code' => 'PPK_RENEWAL_PROCEDURE', 'sort_order' => 11,
                'question_en' => 'How do I pay the PPK renewal fee?', 'answer_en' => 'Payment can be made online through the CIMS system. The payment procedure is as follows:
1. Log in to your CIMS ID.
2. Click the Registration button.
3. Under Fees Due, click the invoice number displayed.
4. Select the payment method and proceed with the payment.',
                'question_ms' => 'Bagaimanakah cara untuk membayar yuran pembaharuan PPK?', 'answer_ms' => 'Bayaran boleh dibuat secara dalam talian melalui sistem CIMS. Tatacara pembayaran adalah seperti berikut:
1. Log masuk ke ID CIMS.
2. Klik butang Pendaftaran.
3. Pada bahagian Fi / Yuran Perlu Dibayar, klik nombor invois yang tertera.
4. Pilih kaedah pembayaran dan teruskan pembayaran.'],
            ['subtopic_code' => 'PPK_APPLICATION_DURATION', 'sort_order' => 3,
                'question_en' => 'How long do I have to pay the processing fee?', 'answer_en' => 'The processing fee must be paid within 7 days from the date the application is submitted. Otherwise, the application will be automatically cancelled.',
                'question_ms' => 'Berapa lama tempoh yang diberikan untuk membayar yuran pemprosesan?', 'answer_ms' => 'Yuran proses perlu dibayar dalam tempoh 7 hari dari tarikh permohonan dihantar. Jika tidak, permohonan akan dibatalkan secara automatik.'],
            ['subtopic_code' => 'PPK_APPLICATION_DURATION', 'sort_order' => 4,
                'question_en' => 'How long do I have to pay the registration fee after approval?', 'answer_en' => 'The registration fee must be paid within 14 days from the date of the approval letter. Otherwise, the approval will be automatically cancelled.',
                'question_ms' => 'Berapa lama tempoh yang diberikan untuk membayar yuran pendaftaran selepas kelulusan?', 'answer_ms' => 'Yuran pendaftaran perlu dibayar dalam tempoh 14 hari dari tarikh surat kelulusan. Jika tidak, kelulusan akan dibatalkan secara automatik.'],
            ['subtopic_code' => 'PPK_APPLICATION_DURATION', 'sort_order' => 5,
                'question_en' => "What happens if I don't pay the registration fee after approval?", 'answer_en' => 'The application approval will be automatically cancelled if the registration fee is not paid within 14 days. The applicant must submit a new application.',
                'question_ms' => 'Apakah yang berlaku jika saya tidak membayar yuran pendaftaran selepas kelulusan?', 'answer_ms' => 'Kelulusan permohonan akan dibatalkan secara automatik jika yuran pendaftaran tidak dibayar dalam tempoh 14 hari. Pemohon perlu membuat permohonan baharu.'],
            ['subtopic_code' => 'PPK_GENERAL', 'sort_order' => 8,
                'question_en' => 'I paid but CIMS still shows unpaid. What should I do?', 'answer_en' => 'Check the payment status and transaction record in CIMS. If the payment has been successfully deducted but remains unrecorded, contact CIDB support with the relevant payment evidence and application reference.',
                'question_ms' => 'Saya telah membuat bayaran tetapi CIMS masih menunjukkan status belum dibayar. Apakah yang perlu saya lakukan?', 'answer_ms' => 'Semak status pembayaran dan rekod transaksi dalam CIMS. Sekiranya bayaran telah dipotong tetapi belum dikemas kini, hubungi khidmat sokongan CIDB dengan mengemukakan bukti pembayaran dan rujukan permohonan.'],
            ['subtopic_code' => 'PPK_GENERAL', 'sort_order' => 9,
                'question_en' => 'My online payment failed. Can I try again?', 'answer_en' => 'Yes. Select the payment method available in CIMS and ensure that the previous transaction was unsuccessful before making another payment.',
                'question_ms' => 'Bayaran dalam talian saya gagal. Bolehkah saya cuba semula?', 'answer_ms' => 'Boleh. Pilih kaedah pembayaran yang disediakan dalam CIMS dan pastikan transaksi sebelumnya belum berjaya sebelum membuat bayaran semula.'],
            ['subtopic_code' => 'PPK_GENERAL', 'sort_order' => 10,
                'question_en' => 'How do I check my PPK renewal status?', 'answer_en' => 'Application status can be checked through CIMS > Registration > View Application History.',
                'question_ms' => 'Bagaimanakah cara untuk menyemak status pembaharuan PPK saya?', 'answer_ms' => 'Status permohonan boleh disemak melalui CIMS > Registration > View Application History.'],
            ['subtopic_code' => 'PPK_GENERAL', 'sort_order' => 11,
                'question_en' => "What does 'Kuiri' mean in my renewal application?", 'answer_en' => 'Query means the applicant needs to make corrections based on the comments provided before resubmitting the application.',
                'question_ms' => "Apakah maksud 'Kuiri' dalam permohonan pembaharuan saya?", 'answer_ms' => 'Kuiri bermaksud pemohon perlu membuat pembetulan berdasarkan ulasan yang diberikan sebelum menghantar semula permohonan.'],
            ['subtopic_code' => 'PPK_APPLICATION_DURATION', 'sort_order' => 6,
                'question_en' => 'How long do I have to respond to a CIDB query?', 'answer_en' => 'The response period for Query-1 is 14 days, while Query-2 is 5 days. If no corrections are made within the respective period, the application will be processed based on the information previously declared.',
                'question_ms' => 'Berapa lama tempoh yang diberikan untuk menjawab kuiri CIDB?', 'answer_ms' => 'Tempoh untuk menjawab Query-1 ialah 14 hari, manakala Query-2 ialah 5 hari. Jika tiada pembetulan dibuat dalam tempoh tersebut, permohonan akan diproses berdasarkan maklumat yang telah diisytiharkan.'],
            ['subtopic_code' => 'PPK_RENEWAL_PROCEDURE', 'sort_order' => 12,
                'question_en' => 'Is there an extra processing fee for correcting a queried application?', 'answer_en' => 'No. No additional processing fee is charged.',
                'question_ms' => 'Adakah terdapat yuran pemprosesan tambahan untuk membetulkan permohonan yang dikuiri?', 'answer_ms' => 'Tidak. Tiada yuran pemprosesan tambahan dikenakan.'],
            ['subtopic_code' => 'PPK_APPLICATION_DURATION', 'sort_order' => 7,
                'question_en' => "What happens if I don't answer the query?", 'answer_en' => 'If you do not make the required corrections within the given period, your application will be processed based on the information you previously provided.',
                'question_ms' => 'Apakah yang berlaku jika saya tidak menjawab kuiri tersebut?', 'answer_ms' => 'Jika anda tidak membuat pembetulan dalam tempoh yang diberikan, permohonan anda akan diproses berdasarkan maklumat yang telah diisytiharkan sebelum itu.'],
            ['subtopic_code' => 'PPK_GENERAL', 'sort_order' => 12,
                'question_en' => 'Why was my PPK renewal rejected?', 'answer_en' => 'Common reasons include unmet requirements, invalid or incomplete supporting documents, and eligibility issues. Please check the rejection comments in the application rejection letter in the CIMS system to find out the reason for the rejection.',
                'question_ms' => 'Mengapa permohonan pembaharuan PPK saya ditolak?', 'answer_ms' => 'Antara sebab utama ialah syarat tidak dipenuhi, dokumen sokongan tidak sah atau tidak lengkap, serta isu kelayakan. Sila semak ulasan penolakan dalam surat gagal permohonan di sistem CIMS untuk mengetahui sebab penolakan.'],
            ['subtopic_code' => 'PPK_GENERAL', 'sort_order' => 13,
                'question_en' => 'Can I appeal a rejected PPK renewal?', 'answer_en' => 'No. There is no appeal application for PPK renewal. If the application is unsuccessful, you need to submit a new application.',
                'question_ms' => 'Bolehkah saya membuat rayuan terhadap pembaharuan PPK yang ditolak?', 'answer_ms' => 'Tidak boleh. Tiada permohonan rayuan bagi pembaharuan PPK. Jika permohonan gagal, anda perlu mengemukakan semula permohonan.'],
            ['subtopic_code' => 'PPK_GENERAL', 'sort_order' => 14,
                'question_en' => 'My PPK has expired. Can I still renew it?', 'answer_en' => 'Yes. An expired PPK can still be renewed through the CIMS system.',
                'question_ms' => 'PPK saya telah tamat tempoh. Bolehkah saya memperbaharuinya?', 'answer_ms' => 'Boleh. PPK yang telah tamat tempoh masih boleh diperbaharui melalui sistem CIMS.'],
            ['subtopic_code' => 'PPK_GENERAL', 'sort_order' => 15,
                'question_en' => 'Can I continue using an expired PPK while waiting for renewal?', 'answer_en' => 'No. An expired PPK is no longer valid for use. Contractors must ensure that their registration is active and valid before carrying out construction work.',
                'question_ms' => 'Bolehkah saya terus menggunakan PPK yang telah tamat tempoh sementara menunggu pembaharuan?', 'answer_ms' => 'Tidak. PPK yang telah tamat tempoh tidak sah digunakan. Kontraktor perlu memastikan pendaftaran masih aktif dan sah sebelum menjalankan kerja pembinaan.'],
        ],
    ],
    'SPKK' => [
        'label_en' => 'SPKK',
        'label_ms' => 'SPKK',
        'subtopics' => [
            ['code' => 'SPKK_GENERAL', 'label_en' => 'General', 'label_ms' => 'Umum', 'sort_order' => 1],
            ['code' => 'SPKK_RENEWAL_PROCEDURE', 'label_en' => 'Renewal Procedure', 'label_ms' => 'Prosedur Pembaharuan', 'sort_order' => 2],
            ['code' => 'SPKK_REQUIREMENTS', 'label_en' => 'SPKK Requirements', 'label_ms' => 'Keperluan SPKK', 'sort_order' => 3],
            ['code' => 'SPKK_SCORE_STAR_RATING', 'label_en' => 'SCORE Star Rating', 'label_ms' => 'Penarafan Bintang SCORE', 'sort_order' => 4],
            ['code' => 'SPKK_NOMINEE', 'label_en' => 'SPKK Nominee', 'label_ms' => 'Penama SPKK', 'sort_order' => 5],
            ['code' => 'SPKK_APPLICATION_DURATION', 'label_en' => 'Application Process Duration', 'label_ms' => 'Tempoh Proses Permohonan', 'sort_order' => 6],
        ],
        'questions' => [
            ['subtopic_code' => 'SPKK_GENERAL', 'sort_order' => 1,
                'question_en' => 'What is SPKK?', 'answer_en' => 'Certify that SPKK holders are eligible to participate in government construction work procurement in accordance with the prescribed qualifications.',
                'question_ms' => 'Apa itu SPKK?', 'answer_ms' => 'Memperakukan bahawa pemegang SPKK layak menyertai perolehan kerja kerajaan dalam bidang pembinaan mengikut kelayakan yang ditetapkan.'],
            ['subtopic_code' => 'SPKK_RENEWAL_PROCEDURE', 'sort_order' => 1,
                'question_en' => 'How can I renew my SPKK?', 'answer_en' => 'To proceed with SPKK renewal, please log in to your CIMS account registered under the company ID.

https://cims.cidb.gov.my/',
                'question_ms' => 'Bagaimana cara untuk saya membuat Pembaharuan SPKK?', 'answer_ms' => 'Untuk membuat Pembaharuan SPKK, sila log masuk ke akaun CIMS yang berdaftar menggunakan ID syarikat.

https://cims.cidb.gov.my/'],
            ['subtopic_code' => 'SPKK_GENERAL', 'sort_order' => 2,
                'question_en' => 'When should SPKK renewal be done?', 'answer_en' => 'The PPK renewal must be completed first. Once the renewed PPK certificate is approved, SPKK renewal can be done when the SPKK certificate has expired.',
                'question_ms' => 'Bila Pembaharuan SPKK perlu dilakukan?', 'answer_ms' => 'Pembaharuan PPK perlu dilengkapkan terlebih dahulu. Selepas sijil PPK diluluskan, Pembaharuan SPKK boleh dilakukan apabila sijil SPKK telah tamat tempoh.'],
            ['subtopic_code' => 'SPKK_NOMINEE', 'sort_order' => 1,
                'question_en' => 'What documents are required for SPKK renewal?', 'answer_en' => 'For SPKK renewal, no supporting documents are required. You only need to include the nominees declared in the PPK application, such as directors, owners, technical personnel, key management personnel, or competent persons. For G7, the company must have a valid ISO 9001:2008/2015 certification or CQMS as an alternative. The exemption for foreign directors/shareholders applies to government/Government-linked companies, registered cooperatives, and public companies listed on Bursa Malaysia. From 2027 onwards, G7 contractors must also have ABMS MS37001 (Anti-Bribery) certification for new applications and re-registration.',
                'question_ms' => 'Apakah dokumen yang diperlukan untuk Pembaharuan SPKK?', 'answer_ms' => 'Untuk Pembaharuan SPKK, tiada dokumen diperlukan. Anda hanya perlu memasukkan penama yang telah diisytiharkan dalam permohonan PPK, seperti pengarah, pemilik, personel teknikal, pengurusan utama atau orang kompeten. Bagi G7, syarikat perlu mempunyai ISO 9001:2008/2015 yang masih sah atau CQMS sebagai alternatif. Pengecualian pengarah/pemegang saham asing terpakai bagi syarikat kerajaan/Government-linked, koperasi berdaftar dan syarikat awam Bursa Malaysia. Mulai 2027, kontraktor G7 juga wajib mempunyai ABMS MS37001 (Anti-Bribery) untuk permohonan baharu dan pendaftaran semula.'],
            ['subtopic_code' => 'SPKK_GENERAL', 'sort_order' => 3,
                'question_en' => 'Can SPKK renewal be done together with PPK and STB renewal?', 'answer_en' => 'No. The SPKK renewal can only be carried out after the PPK renewal is approved and the PPK certificate is received. The company can then proceed with the SPKK renewal. The STB renewal can only be carried out after the SPKK certificate is received.',
                'question_ms' => 'Adakah Pembaharuan SPKK boleh dilakukan sekali dengan Pembaharuan PPK dan STB?', 'answer_ms' => 'Tidak boleh. Pembaharuan SPKK perlu dilakukan selepas Pembaharuan PPK berjaya dan sijil PPK diterima. Setelah itu, syarikat boleh meneruskan Pembaharuan SPKK. Pembaharuan STB hanya boleh dilakukan selepas sijil SPKK diterima.'],
            ['subtopic_code' => 'SPKK_GENERAL', 'sort_order' => 4,
                'question_en' => 'Can SPKK renewal be done separately from PPK renewal?', 'answer_en' => 'Yes, it can. Companies are advised to submit the applications in stages, starting with the PPK renewal, followed by the SPKK renewal.',
                'question_ms' => 'Bolehkah Pembaharuan SPPK dilakukan berasingan dengan Pembaharuan PPK?', 'answer_ms' => 'Ya, boleh. Pihak syarikat disarankan untuk membuat permohonan secara berperingkat, iaitu Pembaharuan PPK terlebih dahulu dan seterusnya diikuti dengan Pembaharuan SPKK.'],
            ['subtopic_code' => 'SPKK_REQUIREMENTS', 'sort_order' => 1,
                'question_en' => 'What are the requirements for SPKK renewal?', 'answer_en' => "The contractor must ensure that the following requirements are fulfilled for SPKK renewal:
1. The PPK certificate is still valid.
2. The company's paid-up capital must meet the minimum requirement based on its registered grade. For grade G1, the minimum required paid-up capital is RM10,000.
3. A shareholder or individual who already holds an SPKK must not hold more than 5% equity in any other company that also holds an SPKK.
4. Directors, Key Management Personnel, Technical Personnel and Competent Persons of a company holding an SPKK must not be registered or involved in any other company that also holds an SPKK.
5. Score must be at least 3 stars for companies in grades G7 to G5 and at least 2 stars for companies in grades G4 to G2.
6. From 1 January 2027, G7 companies are required to have Anti-Bribery Management System (ABMS) MS 37001 certification for new applications and re-registration of the Government Procurement Works Certificate (SPKK). For the ISO 9001 requirement, Contractor Quality Management System (CQMS) may be accepted as an alternative if the company does not have ISO 9001 certification.

Note: Exceptions are given to consortiums, cooperatives, associations and GLCs for Board of Directors or owners.
- G1 > 20 years: Renewal is limited to a maximum of six (6) years, comprising two (2) renewals of three (3) years each. Thereafter, the company is required to apply for a grade upgrade.
- G1 < 20 years: Registration may be maintained until the company reaches 26 years of registration, after which the company is required to apply for a grade upgrade.
- Effective from 2021: G1 renewal requires the company to have at least one qualified technical personnel.",
                'question_ms' => 'Apakah syarat-syarat untuk pembaharuan SPKK?', 'answer_ms' => 'Pihak kontraktor hendaklah memastikan syarat-syarat berikut dipenuhi bagi Pembaharuan SPKK:
1. Sijil PPK masih sah.
2. Modal berbayar syarikat hendaklah memenuhi jumlah minimum yang ditetapkan mengikut gred syarikat. Bagi gred G1, modal berbayar minimum yang diperlukan ialah RM10,000.
3. Pemegang saham syarikat atau individu yang telah memiliki SPKK tidak boleh memiliki ekuiti melebihi 5% dalam mana-mana syarikat lain yang turut mempunyai SPKK.
4. Pengarah, Pengurusan Utama, Personel Teknikal dan Orang Kompeten bagi syarikat yang mempunyai SPKK tidak boleh didaftarkan atau terlibat dalam mana-mana syarikat lain yang turut mempunyai SPKK.
5. Score hendaklah sekurang-kurangnya 3 bintang bagi syarikat gred G7 hingga G5 dan sekurang-kurangnya 2 bintang bagi syarikat gred G4 hingga G2.
6. Mulai 1 Januari 2027, syarikat G7 diwajibkan memiliki pensijilan Anti-Bribery Management System (ABMS) MS 37001 bagi permohonan baharu dan pendaftaran semula Sijil Perolehan Kerja Kerajaan (SPKK). Bagi keperluan ISO 9001, Contractor Quality Management System (CQMS) boleh diterima sebagai alternatif sekiranya syarikat tidak mempunyai pensijilan ISO 9001.

Nota: Pengecualian diberikan kepada konsortium, koperasi, persatuan dan GLC bagi Ahli Lembaga Pengarah atau pemilik.
- G1 > 20 tahun: Pembaharuan maksimum 6 tahun (2 kali x 3 tahun), kemudian wajib naik gred.
- G1 < 20 tahun: Boleh berdaftar sehingga 26 tahun, kemudian wajib naik gred.
- Mulai 2021: Pembaharuan G1 wajib ada seorang personel teknikal yang berkelayakan.'],
            ['subtopic_code' => 'SPKK_REQUIREMENTS', 'sort_order' => 2,
                'question_en' => 'Can a joint venture use or renew SPKK?', 'answer_en' => 'No. A joint venture (JV) company is established or used specifically for the project that has been awarded and is not intended to obtain SPKK generally.',
                'question_ms' => 'Bolehkah syarikat usaha sama menggunakan atau memperbaharui SPKK?', 'answer_ms' => 'Tidak. Syarikat usaha sama (JV) hanya ditubuhkan atau digunakan bagi projek yang telah dianugerahkan sahaja dan bukan untuk mendapatkan SPKK secara umum.'],
            ['subtopic_code' => 'SPKK_REQUIREMENTS', 'sort_order' => 3,
                'question_en' => 'Is SPKK available for all CIDB grades G1 to G7?', 'answer_en' => "SPKK requirements are structured according to the contractor's grade. The financial, SCORE and other applicable requirements may differ depending on the grade.",
                'question_ms' => 'Adakah SPKK tersedia untuk semua gred CIDB dari G1 hingga G7?', 'answer_ms' => 'Keperluan SPKK ditetapkan mengikut gred kontraktor. Syarat berkaitan kewangan, SCORE dan keperluan lain adalah berbeza mengikut gred.'],
            ['subtopic_code' => 'SPKK_RENEWAL_PROCEDURE', 'sort_order' => 2,
                'question_en' => 'Can I change my CIDB grade while renewing SPKK?', 'answer_en' => 'Changes to the grade involve registration with PPK and may affect eligibility for SPKK. Therefore, applications for grade changes must be submitted through the relevant CIDB registration/renewal/update process, and not through the SPKK renewal process.',
                'question_ms' => 'Bolehkah saya menukar gred CIDB semasa membuat Pembaharuan SPKK?', 'answer_ms' => 'Perubahan gred melibatkan pendaftaran PPK dan boleh memberi kesan kepada kelayakan SPKK. Oleh itu, permohonan perubahan gred perlu dibuat melalui proses pendaftaran/pembaharuan/kemaskini pendaftaran CIDB dan bukan melalui proses pembaharuan SPKK.'],
            ['subtopic_code' => 'SPKK_REQUIREMENTS', 'sort_order' => 4,
                'question_en' => 'If my PPK grade changes, what happens to SPKK?', 'answer_en' => 'As SPKK eligibility is linked to PPK and grade-specific requirements, any change in the PPK grade may affect the SPKK; holders of PPK Grade G2 and above with SPKK are not allowed to cancel and re-register for SPKK G1 or downgrade their grade to G1.',
                'question_ms' => 'Jika gred PPK saya berubah, apakah yang akan berlaku kepada SPKK?', 'answer_ms' => 'Oleh kerana kelayakan SPKK berkait dengan PPK dan syarat khusus mengikut gred, perubahan gred PPK boleh memberi kesan kepada SPKK; bagi pemegang PPK gred G2 dan ke atas yang mempunyai SPKK, mereka tidak dibenarkan membatalkan dan mendaftar semula SPKK G1 atau menurunkan gred kepada G1.'],
            ['subtopic_code' => 'SPKK_REQUIREMENTS', 'sort_order' => 5,
                'question_en' => 'What financial requirement applies to G1 SPKK renewal?', 'answer_en' => 'The SPKK renewal requirements stipulate that a G1 contractor must have at least RM10,000 in the required financial capital for SPKK renewal.',
                'question_ms' => 'Apakah keperluan kewangan yang dikenakan bagi pembaharuan SPKK G1?', 'answer_ms' => 'Syarat pembaharuan SPKK ditetapkan bahawa kontraktor G1 perlu mempunyai sekurang-kurangnya RM10,000 modal kewangan yang diperlukan bagi tujuan pembaharuan SPKK.'],
            ['subtopic_code' => 'SPKK_SCORE_STAR_RATING', 'sort_order' => 1,
                'question_en' => 'Do I need SCORE for SPKK renewal?', 'answer_en' => 'Yes. SCORE certification is required, with a minimum of 2 stars for G2 to G4 contractors and 3 stars for G5 to G7 contractors.',
                'question_ms' => 'Adakah saya perlu mendapatkan SCORE untuk pembaharuan SPKK?', 'answer_ms' => 'Ya. Pensijilan SCORE diperlukan dengan minimum 2 bintang bagi kontraktor G2 hingga G4, manakala 3 bintang bagi kontraktor G5 hingga G7.'],
            ['subtopic_code' => 'SPKK_SCORE_STAR_RATING', 'sort_order' => 2,
                'question_en' => 'Does G7 need a valid SCORE to renew SPKK?', 'answer_en' => 'Correct. A G7 company is required to hold a valid SCORE certification in order to proceed with the renewal of its SPKK. The same requirement applies to companies in other grades.',
                'question_ms' => 'Adakah G7 memerlukan SCORE yang sah untuk Pembaharuan SPKK?', 'answer_ms' => 'Benar, syarikat G7 memerlukan Score yang sah bagi meneruskan Pembaharuan SPKK. Begitu juga dengan syarikat di gred lain.'],
            ['subtopic_code' => 'SPKK_SCORE_STAR_RATING', 'sort_order' => 3,
                'question_en' => 'My SCORE has expired. Can I renew G7 SPKK?', 'answer_en' => 'No. If the SCORE certification has expired, the company must renew its SCORE certification first before proceeding with the renewal of its G7 PPK. Once a valid SCORE certification has been obtained, the company may proceed with the PPK renewal, followed by the SPKK renewal.',
                'question_ms' => 'Score saya telah tamat tempoh. Bolehkah saya membuat Pembaharuan SPKK G7?', 'answer_ms' => 'Tidak. Jika sijil SCORE telah tamat tempoh, syarikat perlu memperbaharui SCORE terlebih dahulu sebelum membuat pembaharuan PPK G7. Selepas SCORE yang sah diperoleh, barulah proses pembaharuan PPK dan seterusnya SPKK boleh diteruskan.'],
            ['subtopic_code' => 'SPKK_GENERAL', 'sort_order' => 5,
                'question_en' => 'My PPK renewal is pending/in-progress. Can my SPKK renewal be completed first?', 'answer_en' => 'No. The PPK renewal must be completed first, and the PPK certificate must be issued. Only after the PPK certificate has been obtained can the SPKK renewal process proceed.',
                'question_ms' => 'Pembaharuan PPK masih dalam proses. Bolehkah pembaharuan SPKK saya diselesaikan terlebih dahulu?', 'answer_ms' => 'Tidak boleh. Pembaharuan PPK perlu diselesaikan terlebih dahulu sehingga sijil PPK diterima. Setelah sijil PPK diperoleh, barulah proses pembaharuan SPKK boleh diteruskan.'],
            ['subtopic_code' => 'SPKK_REQUIREMENTS', 'sort_order' => 6,
                'question_en' => 'My company was suspended. Is my SPKK still valid?', 'answer_en' => 'If the company is suspended, the status of the SPKK will also be affected and it cannot be used throughout the suspension period. The SPKK may only be used again once the company has resolved the issues relating to the imposed restrictions.',
                'question_ms' => 'Syarikat saya telah digantung. Adakah SPKK saya masih sah?', 'answer_ms' => 'Jika syarikat dikenakan penggantungan, status SPKK turut terjejas dan tidak boleh digunakan sepanjang tempoh penggantungan tersebut. SPKK hanya boleh digunakan semula setelah syarikat menyelesaikan isu berkenaan sekatan yang dikenakan.'],
            ['subtopic_code' => 'SPKK_RENEWAL_PROCEDURE', 'sort_order' => 3,
                'question_en' => 'I changed my company address. Will it affect SPKK renewal?', 'answer_en' => 'No. The change of company address will not affect the SPKK renewal process. However, the company must ensure that the updated address information has been updated in the CIMS system before submitting the renewal application.',
                'question_ms' => 'Saya telah menukar alamat syarikat. Adakah perubahan tersebut akan menjejaskan pembaharuan SPKK?', 'answer_ms' => 'Tidak. Perubahan alamat syarikat tidak akan menjejaskan proses pembaharuan SPKK. Walau bagaimanapun, pihak syarikat hendaklah memastikan maklumat alamat terkini telah dikemas kini dalam sistem CIMS sebelum mengemukakan permohonan pembaharuan.'],
            ['subtopic_code' => 'SPKK_GENERAL', 'sort_order' => 6,
                'question_en' => 'How do I check my SPKK renewal status?', 'answer_en' => 'You can check the SPKK renewal status by logging in to CIMS and clicking REGISTRATION > VIEW APPLICATION LIST.

https://cims.cidb.gov.my/',
                'question_ms' => 'Bagaimana cara untuk saya membuat semakan status Pembaharuan SPKK?', 'answer_ms' => 'Semakan status Pembaharuan SPKK boleh dilakukan dengan log masuk CIMS dan klik pada bahagian PENDAFTARAN > klik PAPAR REKOD PERMOHONAN.

https://cims.cidb.gov.my/'],
            ['subtopic_code' => 'SPKK_GENERAL', 'sort_order' => 7,
                'question_en' => 'Where can I view my SPKK letter or certificate?', 'answer_en' => 'To view the application outcome letter or SPKK certificate, please log in to the CIDB CIMS system and select REGISTRATION > VIEW RESULT & CERTIFICATE',
                'question_ms' => 'Di manakah saya boleh melihat surat atau sijil SPKK saya?', 'answer_ms' => 'Untuk melihat surat keputusan permohonan atau sijil SPKK, sila log masuk ke sistem CIMS CIDB dan pilih menu PENDAFTARAN > PAPAR KEPUTUSAN/SIJIL'],
            ['subtopic_code' => 'SPKK_GENERAL', 'sort_order' => 8,
                'question_en' => 'Can I appeal if my SPKK renewal is rejected?', 'answer_en' => 'No. A rejected SPKK renewal application is not eligible for appeal. The company must submit a new SPKK renewal application through the CIMS portal.',
                'question_ms' => 'Bolehkah saya membuat rayuan sekiranya permohonan pembaharuan SPKK saya ditolak?', 'answer_ms' => 'Tidak. Permohonan pembaharuan SPKK yang telah ditolak tidak boleh dibuat rayuan. Syarikat perlu mengemukakan semula permohonan Pembaharuan SPKK melalui portal CIMS.'],
            ['subtopic_code' => 'SPKK_RENEWAL_PROCEDURE', 'sort_order' => 4,
                'question_en' => 'How do I pay for the renewal transaction?', 'answer_en' => 'Payment must be made as indicated in CIMS under REGISTRATION > FEES IN DUE > Click on the displayed Invoice Number and proceed with the payment.',
                'question_ms' => 'Bagaimanakah cara untuk membuat bayaran bagi transaksi pembaharuan?', 'answer_ms' => 'Pembayaran hendaklah dibuat seperti yang dipaparkan dalam CIMS di bahagian PENDAFTARAN > YURAN PERLU DIBAYAR > klik pada Nombor Invois yang tertera dan teruskan pembayaran.'],
            ['subtopic_code' => 'SPKK_GENERAL', 'sort_order' => 9,
                'question_en' => 'I paid but my renewal still shows unpaid. What should I do?', 'answer_en' => 'The company is advised to wait up to 48 hours to allow sufficient time for the payment verification and status update process in the CIMS system.',
                'question_ms' => 'Saya telah membuat pembayaran, tetapi status pembaharuan masih menunjukkan "Belum Dibayar". Apakah yang perlu saya lakukan?', 'answer_ms' => 'Pihak syarikat disarankan untuk menunggu sehingga 48 jam bagi membolehkan proses pengesahan dan pengemaskinian status pembayaran dalam sistem CIMS.'],
            ['subtopic_code' => 'SPKK_GENERAL', 'sort_order' => 10,
                'question_en' => 'My payment failed. Can I try again?', 'answer_en' => 'Please first verify whether the previous payment was successful. If the payment failed and no deduction was made, please make the payment again using the payment options available in CIMS. If the amount has been deducted but the payment status remains unclear, the company is advised to wait up to 48 hours for the payment status to be updated.',
                'question_ms' => 'Pembayaran saya gagal. Bolehkah saya mencuba semula pembayaran?', 'answer_ms' => 'Sila pastikan terlebih dahulu sama ada pembayaran sebelumnya telah berjaya. Sekiranya pembayaran gagal dan tiada sebarang potongan dibuat, sila buat pembayaran semula menggunakan kaedah pembayaran yang tersedia dalam CIMS. Sekiranya wang telah ditolak tetapi status pembayaran masih tidak jelas, pihak syarikat disarankan untuk menunggu 48 jam terlebih dahulu.'],
            ['subtopic_code' => 'SPKK_GENERAL', 'sort_order' => 11,
                'question_en' => 'Where can I download renewed SPKK certificate?', 'answer_en' => 'To download the certificate for PPK Renewal, the company needs to log in to CIMS portal and select REGISTRATION > VIEW RESULTS/CERTIFICATE.

https://cims.cidb.gov.my/smis/regcontractor/index.vbhtml',
                'question_ms' => 'Di manakah saya boleh memuat turun sijil SPKK yang telah diperbaharui?', 'answer_ms' => 'Untuk memuat turun sijil bagi Pembaharuan PPK, pihak syarikat perlu log masuk ke dalam laman web CIMS dan memilih menu PENDAFTARAN > PAPAR KEPUTUSAN/SIJIL.

https://cims.cidb.gov.my/smis/regcontractor/index.vbhtml'],
            ['subtopic_code' => 'SPKK_GENERAL', 'sort_order' => 12,
                'question_en' => 'Can I use an old SPKK certificate after PPK has expired?', 'answer_en' => 'No. The company must first apply for PPK Renewal and ensure that the application has been approved. Subsequently, the company must proceed with the SPKK Renewal application until the application status is approved. Once the SPKK Renewal application is approved, the renewed SPKK certificate may be used.',
                'question_ms' => 'Bolehkah saya menggunakan sijil SPKK lama selepas PPK tamat tempoh?', 'answer_ms' => 'Tidak boleh. Syarikat perlu membuat Pembaharuan PPK terlebih dahulu dan memastikan permohonan tersebut telah diluluskan. Seterusnya, syarikat perlu meneruskan permohonan Pembaharuan SPKK sehingga status permohonan diluluskan. Setelah permohonan Pembaharuan SPKK diluluskan, sijil SPKK yang baharu boleh digunakan.'],
            ['subtopic_code' => 'SPKK_GENERAL', 'sort_order' => 13,
                'question_en' => 'Can I participate in a government tender while SPKK renewal is pending?', 'answer_en' => 'No. The company is advised to ensure that the SPKK Renewal application has been approved before participating in or obtaining government tenders. Once the SPKK Renewal application is approved, the company may use the valid SPKK certificate for the purpose of participating in government tenders.',
                'question_ms' => 'Bolehkah saya menyertai tender kerajaan semasa permohonan Pembaharuan SPKK masih dalam proses?', 'answer_ms' => 'Tidak Boleh. Pihak syarikat disarankan untuk memastikan permohonan Pembaharuan SPKK telah diluluskan terlebih dahulu sebelum menyertai atau mendapatkan tender kerajaan. Setelah status permohonan Pembaharuan SPKK diluluskan, syarikat boleh menggunakan sijil SPKK yang sah bagi tujuan penyertaan tender kerajaan.'],
            ['subtopic_code' => 'SPKK_APPLICATION_DURATION', 'sort_order' => 1,
                'question_en' => 'How long is SPKK valid?', 'answer_en' => 'The validity period of the SPKK Renewal certificate is linked to the validity period of the PPK certificate. The PPK renewal period can be selected for up to a maximum of 3 years. If the company selects a 3-year validity period for the PPK, the SPKK certificate will also be valid for 3 years.',
                'question_ms' => 'Berapa lamakah tempoh sah laku SPKK?', 'answer_ms' => 'Tempoh sah laku sijil Pembaharuan SPKK adalah berkait dengan tempoh sah laku sijil PPK. Tempoh pembaharuan PPK boleh dipilih sehingga maksimum 3 tahun. Sekiranya pihak syarikat memilih tempoh sah laku PPK selama 3 tahun, maka sijil SPKK juga akan mempunyai tempoh sah laku selama 3 tahun.'],
            ['subtopic_code' => 'SPKK_REQUIREMENTS', 'sort_order' => 7,
                'question_en' => 'What can cause my SPKK to be cancelled?', 'answer_en' => "The SPKK and STB certificates will be cancelled if a company with an original grade of G2 to G7 applies for Registration Update – Downgrade to G1. In addition, the SPKK certificate may also be cancelled if the company's PPK certificate is cancelled or if the company's paid-up capital is less than RM10,000.",
                'question_ms' => 'Apakah yang boleh menyebabkan sijil SPKK saya dibatalkan?', 'answer_ms' => 'Sijil SPKK dan STB akan terbatal sekiranya syarikat yang mempunyai gred asal G2 hingga G7 membuat permohonan Kemaskini Pendaftaran – Turun Gred ke G1. Selain itu, sijil SPKK juga boleh terbatal sekiranya sijil PPK dibatalkan atau modal berbayar syarikat kurang daripada RM10,000.'],
            ['subtopic_code' => 'SPKK_REQUIREMENTS', 'sort_order' => 8,
                'question_en' => 'Can CIDB suspend or withdraw SPKK?', 'answer_en' => 'Yes. CIDB may suspend or cancel (withdraw) the SPKK, depending on the type of violation and the circumstances of the case.',
                'question_ms' => 'Bolehkah CIDB menggantung atau menarik balik sijil SPKK?', 'answer_ms' => 'Ya. CIDB boleh menggantung atau membatalkan (menarik balik) SPKK, bergantung kepada jenis pelanggaran dan keadaan kes.'],
            ['subtopic_code' => 'SPKK_GENERAL', 'sort_order' => 14,
                'question_en' => 'Does renewing SPKK automatically renew STB?', 'answer_en' => 'No. Renewing the SPKK does not automatically renew the STB. The company must submit a separate STB renewal application after the SPKK renewal is completed.',
                'question_ms' => 'Adakah pembaharuan SPKK secara automatik memperbaharui STB?', 'answer_ms' => 'Tidak. Pembaharuan SPKK tidak akan memperbaharui STB secara automatik. Syarikat perlu membuat permohonan pembaharuan STB secara berasingan selepas pembaharuan SPKK selesai.'],
            ['subtopic_code' => 'SPKK_SCORE_STAR_RATING', 'sort_order' => 4,
                'question_en' => 'How many SCORE stars are required for SPKK renewal?', 'answer_en' => 'For SPKK renewal, the company must meet the SCORE certification requirement of a minimum of two stars for G2–G4 contractors and three stars for G5–G7 contractors.',
                'question_ms' => 'Berapakah bintang SCORE diperlukan untuk pembaharuan SPKK?', 'answer_ms' => 'Untuk pembaharuan SPKK, syarikat perlu memenuhi keperluan pensijilan SCORE, iaitu minimum dua bintang bagi kontraktor G2 hingga G4 dan minimum tiga bintang bagi kontraktor G5 hingga G7.'],
            ['subtopic_code' => 'SPKK_APPLICATION_DURATION', 'sort_order' => 2,
                'question_en' => 'How long does the PPK renewal review process take?', 'answer_en' => 'The review of the SPKK renewal application takes 5 working days after the application is submitted.',
                'question_ms' => 'Berapa lama tempoh semakan pembaharuan SPPK?', 'answer_ms' => 'Semakan permohonan Pembaharuan SPKK mengambil masa 5 hari bekerja selepas permohonan dihantar.'],
            ['subtopic_code' => 'SPKK_APPLICATION_DURATION', 'sort_order' => 3,
                'question_en' => 'How long do I have to pay the processing fee?', 'answer_en' => 'The processing fee must be paid within seven (7) days from the date the application is submitted. If payment is not received within that period, the application is automatically cancelled.',
                'question_ms' => 'Berapa lama tempoh yang diberikan untuk membayar yuran pemprosesan?', 'answer_ms' => 'Yuran proses hendaklah dijelaskan dalam tempoh tujuh (7) hari dari tarikh permohonan dihantar. Jika bayaran tidak diterima dalam tempoh tersebut, permohonan dibatalkan secara automatik.'],
            ['subtopic_code' => 'SPKK_APPLICATION_DURATION', 'sort_order' => 4,
                'question_en' => 'How long do I have to pay the registration fee after approval?', 'answer_en' => 'No payment period is applicable as no registration fee is charged for SPKK.',
                'question_ms' => 'Berapa lama tempoh yang diberikan untuk membayar yuran pendaftaran selepas kelulusan?', 'answer_ms' => 'Tiada tempoh pembayaran ditetapkan kerana tiada yuran pendaftaran dikenakan bagi SPKK.'],
            ['subtopic_code' => 'SPKK_RENEWAL_PROCEDURE', 'sort_order' => 5,
                'question_en' => 'How much is the processing fee?', 'answer_en' => 'RM30 - If the renewal is made before the expiry date of the SPKK / RM50 - If the renewal is made after the expiry date of the SPKK.',
                'question_ms' => 'Berapakah harga yuran proses?', 'answer_ms' => 'RM30 - Sekiranya pembaharuan dilakukan sebelum tarikh tamat tempoh SPKK/RM50 - Sekiranya pembaharuan dilakukan selepas tarikh tamat tempoh SPKK'],
            ['subtopic_code' => 'SPKK_GENERAL', 'sort_order' => 15,
                'question_en' => 'Do I need to visit a CIDB office to renew my SPPK?', 'answer_en' => "No. SPPK renewal can be completed online through CIDB's CIMS system, subject to CIDB's current requirements and procedures.",
                'question_ms' => 'Perlukah saya hadir ke pejabat CIDB untuk memperbaharui SPPK?', 'answer_ms' => 'Tidak Perlu. Pembaharuan SPPK boleh dilakukan secara dalam talian melalui sistem CIMS CIDB, tertakluk kepada keperluan dan proses semasa yang ditetapkan oleh CIDB.'],
            ['subtopic_code' => 'SPKK_GENERAL', 'sort_order' => 16,
                'question_en' => "Why can't I see the SPPK renewal option in CIMS?", 'answer_en' => 'The SPKK renewal option may not be displayed because the company has not yet completed the PPK renewal process in CIMS. Please complete the PPK renewal application in CIMS to ensure that the SPKK renewal application can proceed.',
                'question_ms' => 'Mengapa saya tidak dapat melihat pilihan pembaharuan SPPK dalam sistem CIMS?', 'answer_ms' => 'Pilihan pembaharuan SPPK mungkin tidak dipaparkan kerana syarikat tidak menyelesaikan lagi Pembaharuan PPK pada CIMS. Sila selesaikan permohonan Pembaharuan PPK dalam CIMS bagi memastikan permohonan Pembaharuan SPKK dapat diteruskan.'],
            ['subtopic_code' => 'SPKK_RENEWAL_PROCEDURE', 'sort_order' => 6,
                'question_en' => 'Can I apply for a grade upgrade while my SPPK registration is still active?', 'answer_en' => 'Yes. The company may apply for an SPKK grade upgrade as long as the SPKK certificate is valid and active, subject to the applicable requirements and eligibility criteria.',
                'question_ms' => 'Bolehkah saya memohon untuk naik gred semasa pendaftaran SPPK saya masih aktif?', 'answer_ms' => 'Ya, boleh. Syarikat boleh memohon untuk naik gred SPKK selagi sijil SPKK masih sah dan aktif, tertakluk kepada syarat serta kelayakan yang ditetapkan.'],
            ['subtopic_code' => 'SPKK_APPLICATION_DURATION', 'sort_order' => 5,
                'question_en' => 'My new/renewal application disappeared. Why?', 'answer_en' => 'The application will be automatically cancelled by the system if it is not updated and submitted within the 14-day period specified by CIDB.',
                'question_ms' => 'Mengapa permohonan daftar baru/pembaharuan saya telah hilang daripada sistem?', 'answer_ms' => 'Permohonan akan dibatalkan secara automatik oleh sistem sekiranya tidak dikemas kini dan dihantar dalam tempoh 14 hari yang ditetapkan oleh CIDB.'],
            ['subtopic_code' => 'SPKK_NOMINEE', 'sort_order' => 2,
                'question_en' => 'My name is currently registered with another company that holds an SPKK. Can I also be registered with another company?', 'answer_en' => 'No. If the name is registered as a Director, Key Management Personnel, Technical Personnel, Competent Person or SPKK Nominee with another company that holds an SPKK, the name cannot be registered with another company at the same time. A Renewal/Update application must be submitted to remove the name from the existing company first.',
                'question_ms' => 'Nama saya telah berdaftar pada syarikat lain yang mempunyai SPKK, bolehkah berdaftar dengan syarikat lain?', 'answer_ms' => 'Tidak boleh. Jika nama telah didaftarkan sebagai Pengarah, Pengurusan Utama, Personel Teknikal, Orang Kompeten atau Penama SPKK di syarikat lain yang mempunyai SPKK, nama tersebut tidak boleh didaftarkan di syarikat lain pada masa yang sama. Permohonan Pembaharuan/Kemaskini perlu dibuat untuk mengeluarkan nama daripada syarikat sedia ada terlebih dahulu.'],
            ['subtopic_code' => 'SPKK_SCORE_STAR_RATING', 'sort_order' => 5,
                'question_en' => 'What is SCORE?', 'answer_en' => "SCORE is the Contractor Capability and Capability Assessment Programme developed by CIDB to measure contractors' capability and capacity.",
                'question_ms' => 'Apa itu SCORE?', 'answer_ms' => 'SCORE ialah Program Penilaian Keupayaan dan Kemampuan Kontraktor yang dibangunkan oleh CIDB untuk mengukur keupayaan dan kemampuan kontraktor.'],
            ['subtopic_code' => 'SPKK_GENERAL', 'sort_order' => 17,
                'question_en' => 'Why was my SPPK renewal rejected?', 'answer_en' => 'The reason depends on the application. Check the comments in CIMS. Common areas to verify include unmet requirements, invalid/insufficient supporting information or eligibility issues.

https://cims.cidb.gov.my/smis/regcontractor/index.vbhtml',
                'question_ms' => 'Mengapa permohonan pembaharuan SPPK saya ditolak?', 'answer_ms' => 'Sebab penolakan bergantung kepada permohonan. Semak keputusan atau ulasan dalam CIMS. Antara sebab utama termasuk syarat tidak dipenuhi, dokumen sokongan tidak sah/lengkap atau isu kelayakan.

https://cims.cidb.gov.my/smis/regcontractor/index.vbhtml'],
            ['subtopic_code' => 'SPKK_GENERAL', 'sort_order' => 18,
                'question_en' => 'Can my consultant or staff renew PPK for my company?', 'answer_en' => 'No. The PPK renewal must be made using the authorized company account.',
                'question_ms' => 'Bolehkah perunding saya memperbaharui SPPK untuk syarikat saya?', 'answer_ms' => 'Tidak boleh. Pembaharuan PPK perlu dibuat menggunakan akaun syarikat yang diberi kuasa.'],
        ],
    ],
    'STB' => [
        'label_en' => 'STB',
        'label_ms' => 'STB',
        'subtopics' => [
            ['code' => 'STB_GENERAL', 'label_en' => 'General', 'label_ms' => 'Umum', 'sort_order' => 1],
            ['code' => 'STB_RENEWAL_PROCEDURE', 'label_en' => 'Renewal Procedure', 'label_ms' => 'Prosedur Pembaharuan', 'sort_order' => 2],
            ['code' => 'STB_DOCUMENT_REQUIREMENTS', 'label_en' => 'Document Requirements', 'label_ms' => 'Keperluan Dokumen', 'sort_order' => 3],
            ['code' => 'STB_APPLICATION_DURATION', 'label_en' => 'Application Process Duration', 'label_ms' => 'Tempoh Proses Permohonan', 'sort_order' => 4],
            ['code' => 'STB_REQUIREMENTS', 'label_en' => 'STB Requirements', 'label_ms' => 'Keperluan STB', 'sort_order' => 5],
            ['code' => 'STB_REGISTRATION_FEE', 'label_en' => 'Registration Fee', 'label_ms' => 'Yuran Pendaftaran', 'sort_order' => 6],
        ],
        'questions' => [
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 1,
                'question_en' => 'What is STB?', 'answer_en' => "STB is the Bumiputera Status Certificate that recognises a company's Bumiputera status for participation in procurement opportunities reserved for Bumiputera contractors, subject to the applicable requirements.",
                'question_ms' => 'Apakah STB?', 'answer_ms' => 'STB ialah Sijil Taraf Bumiputera yang mengiktiraf status Bumiputera sesebuah syarikat bagi tujuan penyertaan dalam perolehan yang dikhaskan kepada kontraktor Bumiputera, tertakluk kepada syarat yang ditetapkan.'],
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 2,
                'question_en' => 'Who issues or recognizes STB?', 'answer_en' => 'The Bumiputera status conditions are administered by Pusat Khidmat Kontraktor (PKK) and the contractor renewal workflow is handled through the CIDB/CIMS process.',
                'question_ms' => 'Siapakah yang mengeluarkan atau mengiktiraf STB?', 'answer_ms' => 'Syarat status Bumiputera ditadbir oleh Pusat Khidmat Kontraktor (PKK), manakala proses pembaharuan kontraktor dikendalikan melalui proses CIDB/CIMS.'],
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 3,
                'question_en' => 'What is STB used for?', 'answer_en' => 'STB is evidence that a contractor has recognized Bumiputera status subject to PKK conditions. Specific tenders or programmes may require valid STB in addition to other registrations.',
                'question_ms' => 'Apakah kegunaan STB?', 'answer_ms' => 'STB merupakan bukti bahawa kontraktor mempunyai status Bumiputera yang diiktiraf tertakluk kepada syarat PKK. Tender atau program tertentu mungkin memerlukan STB yang sah selain pendaftaran lain.'],
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 4,
                'question_en' => 'Is STB the same as SPKK?', 'answer_en' => 'No. SPKK is the Government Works Procurement Certificate while STB recognizes Bumiputera status. They are separate certificates with different requirements.',
                'question_ms' => 'Adakah STB sama dengan SPKK?', 'answer_ms' => 'Tidak. SPKK ialah Sijil Perolehan Kerja Kerajaan manakala STB mengiktiraf status Bumiputera. Kedua-duanya ialah sijil berasingan dengan syarat yang berbeza.'],
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 5,
                'question_en' => 'Is STB the same as PPK?', 'answer_en' => 'No. PPK is CIDB contractor registration while STB concerns Bumiputera-status recognition.',
                'question_ms' => 'Adakah STB sama dengan PPK?', 'answer_ms' => 'Tidak. PPK ialah pendaftaran kontraktor CIDB manakala STB berkaitan pengiktirafan status Bumiputera.'],
            ['subtopic_code' => 'STB_RENEWAL_PROCEDURE', 'sort_order' => 1,
                'question_en' => 'How do I renew my STB?', 'answer_en' => 'The following are the steps for STB renewal:
1. Log in using the company ID through the CIMS portal.
2. Click the Registration button.
3. Click STB Renewal.
4. Enter the bank details and cheque signatory information.
5. Upload the relevant documents.
6. Submit the application.',
                'question_ms' => 'Bagaimanakah cara untuk memperbaharui STB saya?', 'answer_ms' => 'Berikut merupakan tatacara pembaharuan STB:
1. Log masuk ID syarikat melalui portal CIMS.
2. Klik butang Pendaftaran.
3. Klik Pembaharuan STB.
4. Masukkan maklumat bank dan penandatangan cek.
5. Sila muat naik dokumen berkaitan.
6. Hantar permohonan.'],
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 6,
                'question_en' => 'Can I renew STB online?', 'answer_en' => 'Yes, STB renewal can be done online through the CIMS system. Please log in to the CIMS and follow the prescribed renewal steps.',
                'question_ms' => 'Bolehkah saya memperbaharui STB secara dalam talian?', 'answer_ms' => 'Ya, pembaharuan STB boleh dilakukan secara dalam talian melalui sistem yang disediakan. Sila log masuk ke portal CIMS dan ikuti langkah pembaharuan yang ditetapkan.'],
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 7,
                'question_en' => 'Can I renew PPK, SPKK and STB together?', 'answer_en' => 'No. The renewals must be done in stages. Applicants must renew PPK first, followed by SPKK. Once the SPKK renewal is approved, the applicant can then proceed to renew STB.',
                'question_ms' => 'Bolehkah saya memperbaharui PPK, SPKK dan STB secara serentak?', 'answer_ms' => 'Tidak boleh. Pembaharuan perlu dilakukan secara berperingkat. Pemohon perlu memperbaharui PPK terlebih dahulu, diikuti SPKK. Selepas SPKK diluluskan, barulah pemohon boleh memperbaharui STB.'],
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 8,
                'question_en' => 'Do I need to renew PPK and SPKK before STB?', 'answer_en' => 'Yes. PPK must be renewed first, followed by SPKK. Once the SPKK renewal is approved, you may then proceed with the STB renewal.',
                'question_ms' => 'Adakah saya perlu memperbaharui PPK dan SPKK sebelum STB?', 'answer_ms' => 'Ya. PPK perlu diperbaharui terlebih dahulu, diikuti dengan SPKK. Selepas SPKK diluluskan, barulah STB boleh diperbaharui.'],
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 9,
                'question_en' => 'Can I renew STB if my PPK renewal is still pending?', 'answer_en' => 'No. You need to wait until the renewal of PPK and SPKK has been completed and approved. Only then can you proceed with the STB renewal.',
                'question_ms' => 'Bolehkah saya memperbaharui STB jika pembaharuan PPK saya masih dalam proses?', 'answer_ms' => 'Tidak boleh. Anda perlu menunggu sehingga pembaharuan PPK dan SPKK selesai dan diluluskan terlebih dahulu. Selepas itu, barulah anda boleh memperbaharui STB.'],
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 10,
                'question_en' => 'Can I renew STB if my SPKK renewal is still pending?', 'answer_en' => 'STB is issued after successful SPKK and PPK renewal, so the linked SPKK status must be resolved first.',
                'question_ms' => 'Bolehkah saya memperbaharui STB jika pembaharuan SPKK saya masih dalam proses?', 'answer_ms' => 'STB dikeluarkan selepas pembaharuan SPKK dan PPK berjaya, jadi status SPKK yang berkaitan perlu diselesaikan terlebih dahulu.'],
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 11,
                'question_en' => 'My STB has expired. What should I do?', 'answer_en' => 'If the STB has expired, the company must renew the PPK first. Once the PPK renewal is successfully completed, the company can proceed with the SPKK renewal. The STB renewal can only be carried out after the SPKK renewal is successfully completed.',
                'question_ms' => 'STB saya telah tamat tempoh. Apakah yang perlu saya lakukan?', 'answer_ms' => 'Jika STB telah tamat tempoh, syarikat perlu memperbaharui PPK terlebih dahulu. Setelah PPK berjaya diperbaharui, syarikat boleh meneruskan pembaharuan SPKK. Selepas SPKK berjaya diperbaharui, barulah pembaharuan STB boleh dibuat.'],
            ['subtopic_code' => 'STB_REQUIREMENTS', 'sort_order' => 1,
                'question_en' => 'What are the main Bumiputera requirements for STB?', 'answer_en' => 'The main Bumiputera Status requirements are as follows:
1. Bumiputera shareholding: 100% for G1 and at least 51% for G2–G7.
2. Shareholders: Bumiputera shareholders must outnumber non-Bumiputera shareholders.
3. Board of Directors: 100% Bumiputera for G1 and at least 51% for G2–G7.
4. Key positions: The CEO/Managing Director/General Manager must be Bumiputera.
5. Bumiputera employees: 100% for G1 and at least 51% for G2–G7.
6. Financial management, management and organisation must be controlled by Bumiputera.
7. Bumiputera must be actively involved and play a key role in the company.',
                'question_ms' => 'Apakah syarat utama Bumiputera untuk STB?', 'answer_ms' => 'Syarat utama Taraf Bumiputera adalah seperti berikut:
1. Saham Bumiputera: G1 sebanyak 100%, manakala G2–G7 sekurang-kurangnya 51%.
2. Pemegang saham: Pemegang saham Bumiputera mesti melebihi pemegang saham bukan Bumiputera.
3. Lembaga Pengarah: G1 sebanyak 100%, manakala G2–G7 sekurang-kurangnya 51% Bumiputera.
4. Jawatan utama: CEO/Pengarah Urusan/GM mestilah Bumiputera.
5. Pekerja Bumiputera: G1 sebanyak 100%, manakala G2–G7 sekurang-kurangnya 51%.
6. Pengurusan kewangan, pengurusan dan organisasi mestilah dikuasai Bumiputera.
7. Bumiputera mesti terlibat secara aktif dan memainkan peranan utama dalam syarikat.'],
            ['subtopic_code' => 'STB_REQUIREMENTS', 'sort_order' => 2,
                'question_en' => 'What Bumiputera shareholding is required for G1 STB?', 'answer_en' => 'The required Bumiputera shareholding for STB G1 is 100%',
                'question_ms' => 'Berapakah pegangan saham Bumiputera yang diperlukan untuk STB G1?', 'answer_ms' => 'Pegangan saham Bumiputera yang diperlukan untuk STB G1 ialah 100%.'],
            ['subtopic_code' => 'STB_REQUIREMENTS', 'sort_order' => 3,
                'question_en' => 'What Bumiputera shareholding is required for G2-G7 STB?', 'answer_en' => 'The required Bumiputera shareholding for STB G2–G7 is at least 51%',
                'question_ms' => 'Berapakah pegangan saham Bumiputera yang diperlukan untuk STB G2-G7?', 'answer_ms' => 'Pegangan saham Bumiputera yang diperlukan untuk STB G2–G7 ialah sekurang-kurangnya 51%.'],
            ['subtopic_code' => 'STB_REQUIREMENTS', 'sort_order' => 4,
                'question_en' => 'Is 51% Bumiputera shareholding alone enough for G2-G7 STB?', 'answer_en' => 'No. A 51% Bumiputera shareholding alone is not sufficient. The company must also meet other requirements, including the composition of the Board of Directors, key management, employees, financial management, organisational control and active Bumiputera involvement.',
                'question_ms' => 'Adakah pegangan saham Bumiputera sebanyak 51% sahaja mencukupi untuk STB G2-G7?', 'answer_ms' => 'Tidak. Pegangan saham 51% sahaja tidak mencukupi. Syarikat juga perlu memenuhi syarat lain seperti komposisi Lembaga Pengarah, pengurusan utama, pekerja, pengurusan kewangan, kawalan organisasi dan penglibatan aktif Bumiputera.'],
            ['subtopic_code' => 'STB_REQUIREMENTS', 'sort_order' => 5,
                'question_en' => 'Does the number of Bumiputera shareholders matter?', 'answer_en' => 'Yes, Bumiputera share ownership and the number of Bumiputera individuals must exceed non-Bumiputera individuals.',
                'question_ms' => 'Adakah bilangan pemegang saham Bumiputera penting?', 'answer_ms' => 'Ya, pemilikan saham Bumiputera dan bilangan individu Bumiputera mestilah melebihi individu bukan Bumiputera.'],
            ['subtopic_code' => 'STB_REQUIREMENTS', 'sort_order' => 6,
                'question_en' => 'What board composition is required for G1?', 'answer_en' => 'For G1, 100% of the Board of Directors must be Bumiputera.',
                'question_ms' => 'Apakah komposisi lembaga pengarah yang diperlukan untuk G1?', 'answer_ms' => 'Bagi G1, 100% ahli Lembaga Pengarah mestilah Bumiputera.'],
            ['subtopic_code' => 'STB_REQUIREMENTS', 'sort_order' => 7,
                'question_en' => 'What board composition is required for G2-G7?', 'answer_en' => 'For G2–G7, at least 51% of the Board of Directors must be Bumiputera.',
                'question_ms' => 'Apakah komposisi lembaga pengarah yang diperlukan untuk G2-G7?', 'answer_ms' => 'Bagi G2–G7, sekurang-kurangnya 51% ahli Lembaga Pengarah mestilah Bumiputera.'],
            ['subtopic_code' => 'STB_REQUIREMENTS', 'sort_order' => 8,
                'question_en' => 'Who must hold the position of CEO, Managing Director, Finance Manager or General Manager?', 'answer_en' => 'Chief Executive, Managing Director, Finance Manager or General Manager and other key posts must be held by Bumiputera.',
                'question_ms' => 'Siapakah yang mesti memegang jawatan CEO, Pengarah Urusan, Pengurus Kewangan atau Pengurus Besar?', 'answer_ms' => 'Ketua Eksekutif, Pengarah Urusan, Pengurus Kewangan atau Pengurus Besar serta jawatan penting lain mesti dipegang oleh Bumiputera.'],
            ['subtopic_code' => 'STB_REQUIREMENTS', 'sort_order' => 9,
                'question_en' => 'Do other key positions need to be held by Bumiputera?', 'answer_en' => 'Yes. Important/key positions must be held by Bumiputera as part of Bumiputera management control.',
                'question_ms' => 'Adakah jawatan penting lain perlu dipegang oleh Bumiputera?', 'answer_ms' => 'Ya. jawatan penting/utama dipegang oleh Bumiputera sebagai sebahagian daripada kawalan pengurusan Bumiputera.'],
            ['subtopic_code' => 'STB_REQUIREMENTS', 'sort_order' => 10,
                'question_en' => 'What percentage of employees must be Bumiputera for G1?', 'answer_en' => 'For G1, 100% of the employees must be Bumiputera.',
                'question_ms' => 'Berapakah peratus pekerja yang mesti terdiri daripada Bumiputera bagi G1?', 'answer_ms' => 'Bagi G1, 100% pekerja mestilah terdiri daripada Bumiputera.'],
            ['subtopic_code' => 'STB_REQUIREMENTS', 'sort_order' => 11,
                'question_en' => 'What percentage of employees must be Bumiputera for G2-G7?', 'answer_en' => 'For G2–G7, at least 51% of the employees must be Bumiputera.',
                'question_ms' => 'Berapakah peratus pekerja yang mesti terdiri daripada Bumiputera bagi G2-G7?', 'answer_ms' => 'Bagi G2–G7, sekurang-kurangnya 51% pekerja mestilah terdiri daripada Bumiputera.'],
            ['subtopic_code' => 'STB_DOCUMENT_REQUIREMENTS', 'sort_order' => 1,
                'question_en' => 'Does the organization chart matter for STB renewal?', 'answer_en' => "Yes. The organisation chart is important to demonstrate the company's management structure and Bumiputera control for STB renewal.",
                'question_ms' => 'Adakah carta organisasi penting untuk pembaharuan STB?', 'answer_ms' => 'Ya. Carta organisasi penting untuk menunjukkan struktur pengurusan dan kawalan Bumiputera dalam syarikat bagi tujuan pembaharuan STB.'],
            ['subtopic_code' => 'STB_REQUIREMENTS', 'sort_order' => 12,
                'question_en' => 'I changed shareholders. Will it affect STB renewal?', 'answer_en' => 'Yes. A change in shareholders may affect the STB renewal as it involves the composition and Bumiputera shareholding. The company must ensure that all Bumiputera Status requirements are still met.',
                'question_ms' => 'Saya telah menukar pemegang saham. Adakah ia akan menjejaskan pembaharuan STB?', 'answer_ms' => 'Ya. Pertukaran pemegang saham boleh menjejaskan pembaharuan STB kerana ia melibatkan komposisi dan pegangan saham Bumiputera. Syarikat perlu memastikan semua syarat Taraf Bumiputera masih dipenuhi.'],
            ['subtopic_code' => 'STB_REQUIREMENTS', 'sort_order' => 13,
                'question_en' => 'Can I add a non-Bumiputera shareholder and keep STB?', 'answer_en' => 'Yes, but only if the company continues to meet all Bumiputera ownership and control requirements for G2-G7. For G1, Bumiputera ownership must be 100%, so adding non-Bumiputera shareholders is not allowed.',
                'question_ms' => 'Bolehkah saya menambah pemegang saham bukan Bumiputera dan mengekalkan STB?', 'answer_ms' => 'Boleh, tetapi hanya jika syarikat masih memenuhi semua syarat pemilikan dan kawalan Bumiputera bagi G2-G7. Bagi G1, pemilikan Bumiputera mestilah 100%, jadi penambahan pemegang saham bukan Bumiputera tidak dibenarkan.'],
            ['subtopic_code' => 'STB_REQUIREMENTS', 'sort_order' => 14,
                'question_en' => 'I changed directors. Will it affect STB?', 'answer_en' => 'Yes. A change of director may affect the STB if the company no longer meets the minimum requirement of 51% Bumiputera representation on the Board of Directors. The company must ensure that the Board of Directors continues to meet the Bumiputera Status requirements after the change of director.',
                'question_ms' => 'Saya telah menukar pengarah. Adakah ia akan menjejaskan STB?', 'answer_ms' => 'Ya. Pertukaran pengarah boleh menjejaskan STB sekiranya menyebabkan syarikat tidak lagi memenuhi syarat minimum 51% Lembaga Pengarah Bumiputera. Syarikat perlu memastikan komposisi Lembaga Pengarah masih memenuhi syarat Taraf Bumiputera selepas pertukaran pengarah.'],
            ['subtopic_code' => 'STB_RENEWAL_PROCEDURE', 'sort_order' => 2,
                'question_en' => 'I changed my CEO or Managing Director. Do I need to update PKK?', 'answer_en' => 'Yes. The company must update the changes after updating the information in CIMS, subject to the current PKK procedures. Key management is also one of the STB eligibility requirements.',
                'question_ms' => 'Saya telah menukar CEO atau Pengarah Urusan. Adakah saya perlu mengemas kini PKK?', 'answer_ms' => 'Ya. Syarikat perlu mengemas kini maklumat STB selepas dikemas kini dalam CIMS.'],
            ['subtopic_code' => 'STB_RENEWAL_PROCEDURE', 'sort_order' => 3,
                'question_en' => 'I changed my company address. Do I need to inform PKK?', 'answer_en' => 'Yes. Any change of CEO or Managing Director must be updated in PKK after the change has been updated with CIDB. This is important as key management is one of the STB eligibility requirements.',
                'question_ms' => 'Saya telah menukar alamat syarikat. Adakah saya perlu memaklumkan kepada PKK?', 'answer_ms' => 'Ya. Sebarang perubahan CEO atau Pengarah Urusan perlu dikemas kini dalam PKK selepas perubahan tersebut dikemas kini dengan CIDB. Ini penting kerana pengurusan utama merupakan salah satu syarat kelayakan STB.'],
            ['subtopic_code' => 'STB_RENEWAL_PROCEDURE', 'sort_order' => 4,
                'question_en' => 'When must I report changes to PKK?', 'answer_en' => 'The company must promptly update the information through the Registration Update application in CIMS after any changes are made, while the STB certificate remains valid.

https://cims.cidb.gov.my/',
                'question_ms' => 'Bilakah saya perlu melaporkan perubahan kepada PKK?', 'answer_ms' => 'Syarikat perlu membuat kemaskini dengan segera melalui permohonan Kemaskini Pendaftaran di CIMS selepas berlaku sebarang perubahan, selagi sijil STB masih dalam tempoh sah laku.

https://cims.cidb.gov.my/'],
            ['subtopic_code' => 'STB_DOCUMENT_REQUIREMENTS', 'sort_order' => 2,
                'question_en' => 'What documents are required for STB renewal?', 'answer_en' => 'The documents required for STB renewal are as follows:
1. Identity Card
2. Birth Certificate
3. Premises Location Plan
4. Statutory Declaration under the Statutory Declarations Act 1960, Employment Declaration Form (Appendix 1)
5. Bank Confirmation Letter (Appendix 2)
6. Copy of Contribution Statement, EPF Form A and Payment Receipt
7. Copy of Parent Company Particulars (SSM)
8. Latest Company Organisation Chart

For further information, please click the following link:
https://cims.cidb.gov.my/SMIS/regcontractor/DMS/STBSupportDocument/SENARAI%20SEMAK%20BAGI%20PERMOHONAN%20STB%20v2.pdf',
                'question_ms' => 'Apakah dokumen yang diperlukan untuk pembaharuan STB?', 'answer_ms' => 'Dokumen yang diperlukan untuk memperbaharui STB adalah seperti berikut:
1. Kad Pengenalan
2. Surat Beranak / Sijil Kelahiran
3. Pelan lokasi premis
4. Surat Akuan Sumpah Berkanun Akta 1960, Borang Akuan Pekerjaan (Lampiran 1)
5. Surat Pengesahan Bank (Lampiran 2)
6. Salinan penyata caruman, Borang A KWSP dan resit bayaran
7. Salinan butiran syarikat induk (SSM)
8. Carta organisasi syarikat terkini

Untuk maklumat lanjut, sila rujuk pautan berikut:
https://cims.cidb.gov.my/SMIS/regcontractor/DMS/STBSupportDocument/SENARAI%20SEMAK%20BAGI%20PERMOHONAN%20STB%20v2.pdf'],
            ['subtopic_code' => 'STB_DOCUMENT_REQUIREMENTS', 'sort_order' => 3,
                'question_en' => 'Where do I upload STB renewal documents?', 'answer_en' => 'The following are the steps for uploading STB documents:
1. Log in using the company ID through the CIMS portal.
2. Click the Registration button.
3. Click STB Renewal.
4. Enter the bank details and cheque signatory information.
5. Upload the relevant documents.
6. Submit the application.',
                'question_ms' => 'Di manakah saya perlu memuat naik dokumen pembaharuan STB?', 'answer_ms' => 'Berikut merupakan tatacara muat naik dokumen STB:
1. Log masuk ID syarikat melalui portal CIMS.
2. Klik butang Pendaftaran.
3. Klik Pembaharuan STB.
4. Masukkan maklumat bank dan penandatangan cek.
5. Sila muat naik dokumen berkaitan.
6. Hantar permohonan.'],
            ['subtopic_code' => 'STB_DOCUMENT_REQUIREMENTS', 'sort_order' => 4,
                'question_en' => "What happens if I don't upload the required STB documents?", 'answer_en' => 'Failure to upload the required documents can result in the application being declined.',
                'question_ms' => 'Apakah yang berlaku jika saya tidak memuat naik dokumen STB yang diperlukan?', 'answer_ms' => 'Kegagalan memuat naik dokumen yang diperlukan boleh menyebabkan permohonan gagal.'],
            ['subtopic_code' => 'STB_DOCUMENT_REQUIREMENTS', 'sort_order' => 5,
                'question_en' => 'Do I need employee information for STB renewal?', 'answer_en' => 'Yes, the EPF statement must be uploaded during the STB renewal process.',
                'question_ms' => 'Adakah saya memerlukan maklumat pekerja untuk pembaharuan STB?', 'answer_ms' => 'Ya, penyata KWSP perlu dimuatnaik ketika pembaharuan STB'],
            ['subtopic_code' => 'STB_DOCUMENT_REQUIREMENTS', 'sort_order' => 6,
                'question_en' => 'Do I need SSM documents for STB renewal?', 'answer_en' => 'Yes, the SSM document must be uploaded during the STB renewal process.',
                'question_ms' => 'Adakah saya memerlukan dokumen SSM untuk pembaharuan STB?', 'answer_ms' => 'Ya, dokumen SSM perlu dimuatnaik ketika pembaharuan STB'],
            ['subtopic_code' => 'STB_DOCUMENT_REQUIREMENTS', 'sort_order' => 7,
                'question_en' => 'Can I submit expired or old company documents?', 'answer_en' => 'No. The company must submit valid and up-to-date documents when submitting the application. Expired or outdated documents must be updated before the application is submitted.',
                'question_ms' => 'Bolehkah saya mengemukakan dokumen syarikat yang telah tamat tempoh atau lama?', 'answer_ms' => 'Tidak boleh. Syarikat perlu mengemukakan dokumen yang masih sah dan terkini semasa membuat permohonan. Dokumen yang telah tamat tempoh atau tidak terkini perlu dikemas kini terlebih dahulu.'],
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 12,
                'question_en' => "Why can't I click the STB renewal button in CIMS?", 'answer_en' => 'STB renewal can only be carried out when the PPK and SPKK are still valid. If either the PPK or SPKK has expired, please complete the PPK and SPKK renewals first. If both are still valid but the STB renewal button cannot be clicked, please submit an enquiry through CIDB e-Bantuan.',
                'question_ms' => 'Kenapa saya tidak dapat klik butang pembaharuan STB dalam CIMS?', 'answer_ms' => 'Pembaharuan STB hanya boleh dibuat selepas PPK dan SPKK masih dalam tempoh sah laku. Sekiranya PPK atau SPKK telah tamat tempoh, sila selesaikan pembaharuan PPK dan SPKK terlebih dahulu. Jika kedua-duanya masih sah tetapi butang pembaharuan STB tidak boleh diklik, sila kemukakan aduan melalui e-Bantuan CIDB.'],
            ['subtopic_code' => 'STB_RENEWAL_PROCEDURE', 'sort_order' => 5,
                'question_en' => 'How do I know that my STB renewal submission is complete?', 'answer_en' => 'The STB renewal application is complete once all declarations have been completed and the system displays a completion notification. An option to proceed with the payment process will then be displayed.',
                'question_ms' => 'Bagaimanakah saya tahu bahawa penghantaran pembaharuan STB saya telah lengkap?', 'answer_ms' => 'Permohonan pembaharuan STB telah lengkap apabila semua pengisytiharan dilengkapkan dan sistem memaparkan notifikasi penyelesaian. Seterusnya, pilihan untuk meneruskan ke proses pembayaran akan dipaparkan.'],
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 13,
                'question_en' => 'How do I check my STB renewal status?', 'answer_en' => 'Log in to CIMS, click Registration on the left side, and then click View Application Record on the right side to check the application status.',
                'question_ms' => 'Bagaimanakah saya boleh menyemak status pembaharuan STB saya?', 'answer_ms' => 'Log masuk ke CIMS, klik Pendaftaran pada bahagian kiri dan klik pada butang Papar Rekod Permohonan pada bahagian kanan untuk semak status permohonan.'],
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 14,
                'question_en' => 'System CIMS shows an error when I submit STB renewal. What should I do?', 'answer_en' => 'If an error occurs in the CIMS system during the STB renewal application, please submit an enquiry through CIDB e-Bantuan for further assistance.',
                'question_ms' => 'Sistem CIMS memaparkan ralat apabila saya menghantar pembaharuan STB. Apakah yang perlu saya lakukan?', 'answer_ms' => 'Sekiranya terdapat ralat pada sistem CIMS semasa permohonan pembaharuan STB, sila kemukakan aduan melalui e-Bantuan CIDB untuk semakan lanjut.'],
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 15,
                'question_en' => 'Why is my STB renewal still pending?', 'answer_en' => 'For STB status-related enquiries, please refer to PKK.

https://www.kuskop.gov.my/index.php?id=11&page_id=22&articleid=364',
                'question_ms' => 'Kenapa pembaharuan STB saya masih dalam proses?', 'answer_ms' => 'Bagi isu semakan status STB, mohon rujuk pihak PKK.

https://www.kuskop.gov.my/index.php?id=11&page_id=22&articleid=364'],
            ['subtopic_code' => 'STB_APPLICATION_DURATION', 'sort_order' => 1,
                'question_en' => 'How long does STB renewal take?', 'answer_en' => 'There is no specified timeframe. For further information and clarification, please refer to PKK.',
                'question_ms' => 'Berapa lamakah masa yang diperlukan untuk pembaharuan STB?', 'answer_ms' => 'Tiada tempoh yang ditetapkan. Untuk maklumat dan semakan lanjut, sila rujuk pihak PKK.'],
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 16,
                'question_en' => 'Will PKK visit my company premises?', 'answer_en' => "Yes. PKK may conduct a site visit to the company's premises as part of the STB assessment, if required. For further information, please refer to PKK.",
                'question_ms' => 'Adakah PKK akan melawat premis syarikat saya?', 'answer_ms' => 'Ya. PKK boleh menjalankan lawatan ke premis syarikat sebagai sebahagian daripada proses penilaian STB, jika diperlukan. Untuk maklumat lanjut, sila rujuk pihak PKK.'],
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 17,
                'question_en' => 'Do I need to attend a PKK course for STB renewal?', 'answer_en' => 'No training course is required for STB renewal.',
                'question_ms' => 'Adakah saya perlu menghadiri kursus PKK untuk pembaharuan STB?', 'answer_ms' => 'Tiada kursus diwajibkan bagi pembaharuan STB.'],
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 18,
                'question_en' => 'Why was my STB renewal rejected?', 'answer_en' => 'The STB renewal application may be rejected if the required documents are incomplete or do not meet the requirements.',
                'question_ms' => 'Kenapa pembaharuan STB saya ditolak?', 'answer_ms' => 'Permohonan pembaharuan STB boleh ditolak jika dokumen yang diperlukan tidak lengkap atau tidak memenuhi syarat.'],
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 19,
                'question_en' => 'Can I appeal a rejected STB renewal?', 'answer_en' => 'No. Any decision made by PKK shall be final, and no appeal shall be permitted.',
                'question_ms' => 'Bolehkah saya membuat rayuan terhadap pembaharuan STB yang ditolak?', 'answer_ms' => 'Tidak boleh. Sebarang keputusan yang dikeluarkan oleh PKK adalah muktamad dan sebarang rayuan adalah tidak dibenarkan.'],
            ['subtopic_code' => 'STB_REQUIREMENTS', 'sort_order' => 15,
                'question_en' => 'Can PKK cancel or suspend my STB?', 'answer_en' => 'Yes. PKK may cancel or suspend the STB certificate if the company provides false information, fails to provide the requested information, does not comply with the approval requirements, or breaches the STB conditions',
                'question_ms' => 'Bolehkah PKK membatalkan atau menggantung STB saya?', 'answer_ms' => 'Ya. PKK boleh membatalkan atau menggantung sijil STB sekiranya syarikat memberikan maklumat palsu, gagal mengemukakan maklumat yang diminta, tidak mematuhi syarat kelulusan atau melanggar syarat STB.'],
            ['subtopic_code' => 'STB_REQUIREMENTS', 'sort_order' => 16,
                'question_en' => 'What happens if information submitted for STB is false?', 'answer_en' => 'PKK may cancel, withdraw or suspend the certificate if information submitted is found to be untrue or false.',
                'question_ms' => 'Apakah yang berlaku jika maklumat yang dikemukakan untuk STB adalah palsu?', 'answer_ms' => 'PKK boleh membatalkan, menarik balik atau menggantung sijil jika maklumat yang dikemukakan didapati tidak benar atau palsu.'],
            ['subtopic_code' => 'STB_REQUIREMENTS', 'sort_order' => 17,
                'question_en' => 'What if my company no longer meets the Bumiputera conditions?', 'answer_en' => 'The company is compulsory to meet the STB recognition conditions. Non-compliance can affect certificate status and should be reported/updated through the required channels.',
                'question_ms' => 'Bagaimana jika syarikat saya tidak lagi memenuhi syarat Bumiputera?', 'answer_ms' => 'Sekiranya syarikat tidak lagi memenuhi syarat Taraf Bumiputera, syarikat perlu membuat kemaskini melalui CIMS. Ketidakpatuhan terhadap syarat tersebut boleh menyebabkan status STB syarikat terjejas.'],
            ['subtopic_code' => 'STB_REGISTRATION_FEE', 'sort_order' => 1,
                'question_en' => 'Is there a registration fee for STB?', 'answer_en' => 'No registration fee is charged for SPKK and STB certificates. They can be printed online after the application is approved.',
                'question_ms' => 'Adakah terdapat fi pendaftaran untuk STB?', 'answer_ms' => 'Tiada fi pendaftaran dikenakan untuk sijil SPKK dan STB. Sijil boleh dicetak secara dalam talian selepas permohonan diluluskan.'],
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 20,
                'question_en' => 'Where can I print my renewed STB?', 'answer_en' => 'After approval, the applicant can print the STB online through CIMS.',
                'question_ms' => 'Di manakah saya boleh mencetak STB yang telah diperbaharui?', 'answer_ms' => 'Selepas diluluskan, pemohon boleh mencetak STB secara dalam talian melalui CIMS.'],
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 21,
                'question_en' => 'Do I need to collect my STB at a CIDB office?', 'answer_en' => 'No. The PPK, SPKK and STB certificates can be printed online by the applicant through CIMS and do not need to be collected at the CIDB counter.',
                'question_ms' => 'Adakah saya perlu mengambil STB saya di pejabat CIDB?', 'answer_ms' => 'Tidak. Sijil PPK, SPKK dan STB boleh dicetak oleh pemohon secara dalam talian melalui CIMS dan tidak perlu diambil di kaunter CIDB.'],
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 22,
                'question_en' => 'Can I reprint a lost or damaged STB?', 'answer_en' => 'Yes. applicants can print certificates again online without charge.',
                'question_ms' => 'Bolehkah saya mencetak semula STB yang hilang atau rosak?', 'answer_ms' => 'Ya. pemohon boleh mencetak semula sijil secara dalam talian tanpa caj.'],
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 23,
                'question_en' => 'Does the online STB have a QR code?', 'answer_en' => 'The online PPK, SPKK and STB certificates include a QR code as a security feature.',
                'question_ms' => 'Adakah STB dalam talian mempunyai kod QR?', 'answer_ms' => 'Sijil PPK, SPKK dan STB atas talian mempunyai kod QR sebagai ciri keselamatan.'],
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 24,
                'question_en' => 'My STB is approved but I cannot print it. What should I do?', 'answer_en' => 'If the STB application has been approved but the certificate cannot be printed in CIMS, please refer to CIDB e-Bantuan for further assistance.',
                'question_ms' => 'STB saya telah diluluskan tetapi saya tidak dapat mencetaknya. Apakah yang perlu saya lakukan?', 'answer_ms' => 'Sekiranya permohonan STB telah diluluskan tetapi sijil masih tidak dapat dicetak dalam CIMS, sila rujuk e-Bantuan CIDB untuk semakan lanjut.'],
            ['subtopic_code' => 'STB_APPLICATION_DURATION', 'sort_order' => 2,
                'question_en' => 'How long is my STB valid?', 'answer_en' => 'The STB certificate validity period will follow the validity period of the PPK and SPKK certificates.',
                'question_ms' => 'Berapa lamakah tempoh sah STB saya?', 'answer_ms' => 'Tarikh sah tempoh sijil STB akan mengikut tempoh sah sijil PPK dan SPKK.'],
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 25,
                'question_en' => 'Can I use an expired STB for a tender?', 'answer_en' => 'No. Construction work cannot be carried out if the STB certificate has expired.',
                'question_ms' => 'Bolehkah saya menggunakan STB yang telah tamat tempoh untuk tender?', 'answer_ms' => 'Tidak boleh. Kerja-kerja pembinaan tidak boleh dilaksanakan sekiranya sijil STB telah tempoh.'],
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 26,
                'question_en' => 'Who should I contact if I cannot resolve my STB renewal issue?', 'answer_en' => 'For technical issues, please contact CIDB Careline or submit a form through CIDB e-Bantuan.

https://www.cidb.gov.my/e-bantuan/',
                'question_ms' => 'Siapakah yang perlu saya hubungi jika saya tidak dapat menyelesaikan masalah pembaharuan STB saya?', 'answer_ms' => 'Bagi isu teknikal, sila hubungi CIDB Careline atau isi borang e-Bantuan CIDB.

https://www.cidb.gov.my/e-bantuan/'],
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 27,
                'question_en' => 'How do I correct/resubmit my STB application?', 'answer_en' => 'Applicant need to wait for the application to be declined before submitting a new STB renewal application. Please ensure that all required documents are complete and uploaded before submitting the new application.',
                'question_ms' => 'Bagaimanakah saya boleh membetulkan atau menghantar semula permohonan STB saya?', 'answer_ms' => 'Pemohon perlu menunggu sehingga permohonan ditolak sebelum membuat semula permohonan pembaharuan STB. Sila pastikan semua dokumen yang diperlukan telah lengkap dan dimuat naik sebelum menghantar permohonan baharu.'],
            ['subtopic_code' => 'STB_GENERAL', 'sort_order' => 28,
                'question_en' => 'What is the difference between PPK, SPKK and STB?', 'answer_en' => "PPK is a contractor registration certificate that allows contractors to carry out construction works according to their registered grade, category and specialisation. SPKK is a certificate that allows contractors to participate in government work procurement, while STB is a certificate that recognises a company's Bumiputera status for procurement opportunities reserved for Bumiputera contractors.",
                'question_ms' => 'Apakah perbezaan antara PPK, SPKK dan STB?', 'answer_ms' => 'PPK merupakan sijil pendaftaran kontraktor untuk menjalankan kerja-kerja pembinaan mengikut gred, kategori dan pengkhususan yang didaftarkan. SPKK merupakan sijil yang membolehkan kontraktor menyertai perolehan kerja kerajaan, manakala STB merupakan sijil yang mengiktiraf status Bumiputera syarikat bagi tujuan perolehan yang dikhaskan kepada kontraktor Bumiputera.'],
        ],
    ],
];
