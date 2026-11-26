<?php
/**
 * Shared helpers for the student feedback forms / PDFs ("banner and cards" style).
 *
 * - The header (title banner, student details card and mark box) is built by FeedbackPdfHeader().
 * - What the mark box shows (mark, grade or class, plus the optional university descriptor) is
 *   chosen by staff on the email / download pages and set with SetFeedbackPdfOptions().
 * - Grades, classes and descriptors come from the assessment_criteria table
 *   (loaded into $assessment_criteria_text by setup_tables.php, edited on
 *   admin2/upload_assessment_criteria_xlsx.php).
 * - Styles are the .fbp-* classes in css/pagedesc.css.
 */

if (!function_exists('FeedbackPdfDefaultOptions')) {

/** @return array{grade_display: string, show_descriptor: bool} */
function FeedbackPdfDefaultOptions() {
    return ['grade_display' => 'mark', 'show_descriptor' => false];
}

/** Valid values for grade_display, with labels for the option controls. */
function FeedbackPdfGradeDisplayChoices() {
    return [
        'mark'  => 'Mark (e.g. 68.5 / 100)',
        'grade' => 'Grade (A+ to F)',
        'class' => 'Class (Zero to Excellent 1st)',
        'none'  => 'None (no mark, grade or class)',
    ];
}

/** Set the options used by FeedbackPdfHeader() for the rest of this request. */
function SetFeedbackPdfOptions($grade_display, $show_descriptor) {
    if (!array_key_exists($grade_display, FeedbackPdfGradeDisplayChoices())) {
        $grade_display = 'mark';
    }
    $GLOBALS['PMA_FEEDBACK_PDF_OPTS'] = [
        'grade_display'   => $grade_display,
        'show_descriptor' => (bool) $show_descriptor,
    ];
}

/** @return array{grade_display: string, show_descriptor: bool} */
function GetFeedbackPdfOptions() {
    return $GLOBALS['PMA_FEEDBACK_PDF_OPTS'] ?? FeedbackPdfDefaultOptions();
}

/**
 * Read the options from a submitted form (fields grade_display / show_descriptor).
 * The choice is remembered in the session so the next time the page opens it is preselected.
 * Returns the options array without applying it.
 */
function FeedbackPdfOptionsFromRequest(array $req) {
    $opts = FeedbackPdfDefaultOptions();
    if (isset($req['grade_display']) && array_key_exists($req['grade_display'], FeedbackPdfGradeDisplayChoices())) {
        $opts['grade_display'] = $req['grade_display'];
    }
    $opts['show_descriptor'] = !empty($req['show_descriptor']);
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['feedback_pdf_opts'] = $opts;
    }
    return $opts;
}

/** The options last chosen by this user (session), else the defaults. */
function FeedbackPdfRememberedOptions() {
    return $_SESSION['feedback_pdf_opts'] ?? FeedbackPdfDefaultOptions();
}

/**
 * Bootstrap form controls for choosing the options (put inside an existing <form>).
 */
function FeedbackPdfOptionsFormHtml(?array $opts = null) {
    $opts = $opts ?? FeedbackPdfRememberedOptions();
    $html = "<div class='card bg-light mb-3'><div class='card-body py-2'>\n";
    $html .= "<p class='mb-1 fw-bold small'>Result shown on the feedback PDF</p>\n";
    foreach (FeedbackPdfGradeDisplayChoices() as $value => $label) {
        $checked = $opts['grade_display'] === $value ? 'checked' : '';
        $html .= "<div class='form-check form-check-inline'>"
               . "<input class='form-check-input' type='radio' name='grade_display' id='grade_display_$value' value='$value' $checked>"
               . "<label class='form-check-label' for='grade_display_$value'>$label</label></div>\n";
    }
    $checked = $opts['show_descriptor'] ? 'checked' : '';
    $html .= "<div class='form-check mt-1'>"
           . "<input class='form-check-input' type='checkbox' name='show_descriptor' id='show_descriptor' value='1' $checked>"
           . "<label class='form-check-label' for='show_descriptor'>Also show the university descriptor for the mark</label></div>\n";
    $html .= "<div class='text-muted small mt-1'>Grades, classes and descriptors come from the assessment criteria table (Admin &rsaquo; View / Load Assessment Criteria).</div>\n";
    $html .= "</div></div>\n";
    return $html;
}

/**
 * Look up the assessment criteria row for a percentage mark (rounded to the nearest whole mark).
 * @return array|null [class, grade_text, gpa, descriptor_university, descriptor_school] or null.
 */
function FeedbackCriteriaForPercent($percent) {
    global $assessment_criteria_text;
    if ($percent === null || !is_numeric($percent) || $percent < 0) {
        return null;
    }
    $key = (int) round(min(100, (float) $percent));
    if (empty($assessment_criteria_text[$key]) || !isset($assessment_criteria_text[$key][0])) {
        return null;
    }
    return $assessment_criteria_text[$key];
}

/**
 * Build the header of a feedback form: title banner, details card and the mark box,
 * plus the university descriptor if that option is on.
 *
 * @param array $markers  label => name, e.g. ['Supervisor' => 'Dr X', 'Marker' => 'Prof Y']
 * @param mixed $mark     The mark awarded (out of $max_mark); null if none.
 */
function FeedbackPdfHeader($module_desc, $assesstype_desc, $mark_pc, $student_name, $programme, array $markers, $title, $project_id, $mark, $max_mark) {
    $opts = GetFeedbackPdfOptions();

    $has_mark = is_numeric($mark) && $mark >= 0;
    $percent = ($has_mark && is_numeric($max_mark) && $max_mark > 0) ? $mark * 100.0 / $max_mark : null;
    $criteria = FeedbackCriteriaForPercent($percent);

    // Mark box contents
    $mark_s = $has_mark ? rtrim(rtrim(sprintf('%.1f', $mark), '0'), '.') : '&ndash;';
    $box_label = 'Mark';
    $box_value = $mark_s;
    $box_value_class = 'fbp-big';
    $box_sub = "out of $max_mark";
    if ($opts['grade_display'] === 'grade' && $criteria) {
        $box_label = 'Grade';
        $box_value = htmlspecialchars($criteria[1]);
        $box_sub = '';
    } elseif ($opts['grade_display'] === 'class' && $criteria) {
        $box_label = 'Classification';
        $box_value = htmlspecialchars($criteria[0]);
        $box_value_class = 'fbp-big fbp-big-text';
        $box_sub = '';
    }

    // Details card: student / programme on the left, markers on the right
    $left = "<div class='fbp-k'>Student</div><div class='fbp-v'>$student_name</div>"
          . "<div class='fbp-k'>Programme</div><div class='fbp-v'>$programme</div>";
    $right = '';
    foreach ($markers as $label => $name) {
        $right .= "<div class='fbp-k'>$label</div><div class='fbp-v'>$name</div>";
    }

    $html = "<div class='fbp-wrap'>";
    $html .= "<div class='fbp-banner'><div class='fbp-banner-sub'>$module_desc &middot; $mark_pc% of module mark</div>"
           . "<div class='fbp-banner-title'>$assesstype_desc Feedback</div></div>";
    $html .= "<table class='fbp-sum'><tr>"
           . "<td class='fbp-info'><table class='fbp-info-in'><tr>"
           . "<td width='50%'>$left</td><td>$right</td></tr>"
           . "<tr><td colspan='2'><div class='fbp-k'>Project $project_id</div><div class='fbp-v'><i>$title</i></div></td></tr></table></td>"
           . ($opts['grade_display'] === 'none' ? '' :
               "<td class='fbp-gap'></td>"
             . "<td class='fbp-markbox'><div class='fbp-k'>$box_label</div><div class='$box_value_class'>$box_value</div>"
             . ($box_sub !== '' ? "<div class='fbp-k'>$box_sub</div>" : '')
             . "</td>")
           . "</tr></table>";

    if ($opts['show_descriptor'] && $criteria && trim((string) $criteria[3]) !== '') {
        $html .= "<div class='fbp-descriptor'><div class='fbp-k'>University grade descriptor</div>" . $criteria[3] . "</div>";
    }
    $html .= "</div>\n";
    return $html;
}

/** Section heading bar, e.g. "Section 1: Background (20%)". */
function FeedbackPdfSectionHeading($text, $weight_pc = null) {
    $w = ($weight_pc !== null && $weight_pc !== '') ? " <span class='fbp-sec-w'>($weight_pc%)</span>" : '';
    return "<div class='fbp-sec'>$text$w</div>";
}

/**
 * Create an mPDF document set up for feedback PDFs: margins, the
 * "Student | Module | Page X of Y" footer and the app stylesheet.
 */
function NewFeedbackMpdf($student_name, $module_desc) {
    $mpdf = new \Mpdf\Mpdf([
        'tempDir'       => PMA_TMP_DIR,
        'margin_top'    => 14,
        'margin_left'   => 16,
        'margin_right'  => 16,
        'margin_bottom' => 16,
    ]);
    $clean = function ($s) { return str_replace('|', '/', strip_tags((string) $s)); };
    $mpdf->SetFooter($clean($student_name) . '|' . $clean($module_desc) . '|Page {PAGENO} of {nbpg}');
    $mpdf->WriteHTML(file_get_contents(__DIR__ . '/css/pagedesc.css'), \Mpdf\HTMLParserMode::HEADER_CSS);
    return $mpdf;
}

}
