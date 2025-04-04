<?php
return [
    'positions' => [
        'special' => ['GDB-1-1', 'GDB-1-2', 'GDB-1-3', 'GDB-1-4', 'GDB-1-5'],
        'prize1' => ['G1-1-1', 'G1-1-2', 'G1-1-3', 'G1-1-4', 'G1-1-5'],
        'prize2' => ['G2-1-1', 'G2-1-2', 'G2-1-3', 'G2-1-4', 'G2-1-5',
            'G2-2-1', 'G2-2-2', 'G2-2-3', 'G2-2-4', 'G2-2-5'],
        'prize3' => ['G3-1-1', 'G3-1-2', 'G3-1-3', 'G3-1-4', 'G3-1-5',
            'G3-2-1', 'G3-2-2', 'G3-2-3', 'G3-2-4', 'G3-2-5',
            'G3-3-1', 'G3-3-2', 'G3-3-3', 'G3-3-4', 'G3-3-5',
            'G3-4-1', 'G3-4-2', 'G3-4-3', 'G3-4-4', 'G3-4-5',
            'G3-5-1', 'G3-5-2', 'G3-5-3', 'G3-5-4', 'G3-5-5',
            'G3-6-1', 'G3-6-2', 'G3-6-3', 'G3-6-4', 'G3-6-5'],
        'prize4' => ['G4-1-1', 'G4-1-2', 'G4-1-3', 'G4-1-4',
            'G4-2-1', 'G4-2-2', 'G4-2-3', 'G4-2-4',
            'G4-3-1', 'G4-3-2', 'G4-3-3', 'G4-3-4',
            'G4-4-1', 'G4-4-2', 'G4-4-3', 'G4-4-4'], // Mỗi số riêng biệt
        'prize5' => ['G5-1-1', 'G5-1-2', 'G5-1-3', 'G5-1-4',
            'G5-2-1', 'G5-2-2', 'G5-2-3', 'G5-2-4',
            'G5-3-1', 'G5-3-2', 'G5-3-3', 'G5-3-4',
            'G5-4-1', 'G5-4-2', 'G5-4-3', 'G5-4-4',
            'G5-5-1', 'G5-5-2', 'G5-5-3', 'G5-5-4',
            'G5-6-1', 'G5-6-2', 'G5-6-3', 'G5-6-4'], // Chỉnh theo từng chữ số
        'prize6' => ['G6-1-1', 'G6-1-2', 'G6-1-3',
            'G6-2-1', 'G6-2-2', 'G6-2-3',
            'G6-3-1', 'G6-3-2', 'G6-3-3'],
        'prize7' => ['G7-1-1', 'G7-1-2',
            'G7-2-1', 'G7-2-2',
            'G7-3-1', 'G7-3-2',
            'G7-4-1', 'G7-4-2'] // Chia từng chữ số cho prize7
    ],
    // Cấu hình các dạng ghép cầu
    'combination_types' => [
        'single' => [
            'description' => 'Cầu lô từ một vị trí duy nhất',
            'example' => 'Lấy số tại vị trí G7-3-2'
        ],
        'pair' => [
            'description' => 'Ghép từ hai vị trí',
            'example' => 'G7-3-2 & G7-4-2 = XY [0-99]'
        ],
        'multi' => [
            'description' => 'Ghép từ nhiều vị trí',
            'example' => 'G7-3-2 & G7-4-2 & G6-1-3 = XYZ'
        ],
        'dynamic' => [
            'description' => 'Máy học tự động xác định vị trí tối ưu',
            'example' => 'Tự động xác định vị trí dựa trên phân tích thống kê'
        ]
    ],

    // Các công thức ghép cầu mẫu
    'predefined_formulas' => [
        [
            'name' => 'Cầu Lô Song Thủ G7',
            'type' => 'pair',
            'positions' => ['G7-3-2', 'G7-4-2'],
            'description' => 'Ghép song thủ từ Giải 7'
        ],
        [
            'name' => 'Cầu Lô Tam Thủ G6-G7',
            'type' => 'multi',
            'positions' => ['G6-3-3', 'G7-3-2', 'G7-4-2'],
            'description' => 'Ghép tam thủ từ Giải 6 và Giải 7'
        ],
        [
            'name' => 'Cầu Lô Đầu DB',
            'type' => 'single',
            'positions' => ['GDB-1-1'],
            'description' => 'Lấy số đầu của giải đặc biệt'
        ]
    ]
];

