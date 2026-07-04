<?php

return [
    /*
     * Confidence threshold above which a defect automatically triggers a REJECT.
     * Any detection at or above this score → action = 'reject' (no inspector needed).
     */
    'auto_reject_threshold' => (float) env('YOLO_AUTO_REJECT_THRESHOLD', 0.80),

    /*
     * Confidence threshold below which ALL defects are considered too uncertain
     * to act on → action = 'pass' (treated as no real defect found).
     * If any defect score falls between pass and reject thresholds → send to inspector.
     */
    'auto_pass_threshold' => (float) env('YOLO_AUTO_PASS_THRESHOLD', 0.50),
];
