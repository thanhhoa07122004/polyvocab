<?php

return [
    'learn_new_title' => 'Học từ mới',
    'learn_new_desc' => 'Chọn số lượng để bắt đầu học bộ “:name”.',
    'language' => 'Ngôn ngữ',
    'unstudied_content' => 'Còn :count từ chưa học',
    'items_count' => ':count từ',
    'item_unit_short' => ':count từ',
    'how_many_today' => 'Số từ muốn học',
    'remaining_items' => 'Bộ này còn :count từ chưa học.',
    'all_items' => 'Tất cả · :count từ',
    'custom' => 'Tùy chỉnh',
    'select_practice_modes' => 'Chọn cách luyện sau khi làm quen',
    'practice_modes_hint' => 'Sau khi làm quen, bạn sẽ luyện bằng các dạng đã chọn. Có thể chọn nhiều dạng.',
    'usable_for_items' => 'Có thể dùng cho :count từ',
    'unavailable' => 'Chưa dùng được',
    'unavailable_modes_title' => 'Các dạng chưa dùng được · :count',
    'start_learning' => 'Bắt đầu học',
    'start_learning_with_count' => 'Bắt đầu làm quen · :count từ',
    'back_to_deck' => 'Về bộ từ',
    'select_at_least_one_mode' => 'Chọn ít nhất một cách luyện để tiếp tục.',
    'preparing' => 'Đang chuẩn bị…',
    'progress_saved_note' => 'Tiến độ được lưu tự động sau mỗi từ.',
    'flow_steps' => 'Làm quen từ mới → Kết quả',
    'item_unit_explanation' => 'Mỗi thẻ học tương ứng với một từ hoặc nét nghĩa trong bộ từ.',
    'familiarize_then_practice' => 'Bạn sẽ xem lần lượt các từ để làm quen. Sau khi hoàn thành, bạn có thể chọn luyện tập ngay hoặc quay về bộ từ.',
    'session_summary' => 'Nội dung học',
    'modes_selected' => 'Cách luyện',
    'session_in_progress' => 'BẠN ĐANG HỌC DỞ',
    'active_session_has_progress' => 'Bạn đang học dở bộ từ này.',
    'active_session_no_progress' => 'Bạn đã bắt đầu nhưng chưa học nội dung nào.',
    'resume_session' => 'Tiếp tục học',
    'abandon_session' => 'Kết thúc lần học này',
    'abandon_confirm' => 'Kết quả đã lưu vẫn được giữ. Phần chưa học sẽ được trả về danh sách để bạn chọn lại.',
    'flashcard_remember_question' => 'Bạn có nhớ đáp án trước khi lật thẻ không?',
    'save_error_retry' => 'Chưa lưu được câu trả lời. Bạn hãy thử lại.',

    // Tên và mô tả 8 dạng bài học
    'exercises' => [
        'flashcard' => [
            'name' => 'Thẻ ghi nhớ',
            'desc' => 'Tự nhớ rồi lật thẻ để xem đáp án.',
        ],
        'multiple_choice_term_to_meaning' => [
            'name' => 'Chọn nghĩa',
            'desc' => 'Nhìn từ và chọn nghĩa phù hợp.',
        ],
        'multiple_choice_meaning_to_term' => [
            'name' => 'Chọn từ',
            'desc' => 'Nhìn nghĩa và chọn từ tương ứng.',
        ],
        'matching_pairs' => [
            'name' => 'Ghép cặp',
            'desc' => 'Ghép từ với nghĩa phù hợp.',
        ],
        'listen_and_choose' => [
            'name' => 'Nghe và chọn',
            'desc' => 'Nghe phát âm rồi chọn đáp án.',
        ],
        'listen_and_type' => [
            'name' => 'Nghe và viết',
            'desc' => 'Nghe phát âm rồi nhập lại từ.',
        ],
        'fill_in_the_blank' => [
            'name' => 'Điền từ',
            'desc' => 'Điền phần còn thiếu trong câu.',
        ],
        'word_unscramble' => [
            'name' => 'Sắp xếp chữ',
            'desc' => 'Sắp xếp các chữ hoặc mảnh để tạo đáp án.',
        ],
    ],

    // Lý do dạng bài chưa dùng được
    'reasons' => [
        'no_audio' => 'Chưa có âm thanh.',
        'no_examples' => 'Thiếu câu ví dụ.',
        'no_suitable_cloze' => 'Chưa có bài điền từ phù hợp.',
        'need_min_terms' => 'Cần ít nhất :min từ khác nhau để tạo lựa chọn.',
        'need_min_meanings' => 'Cần ít nhất :min nghĩa trong bộ từ.',
    ],
];
