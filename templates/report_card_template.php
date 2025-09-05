<?php
/**
 * HTML template for the student report card.
 * This file is executed by ReportGenerator.php and all variables are passed into its scope.
 * It uses basic HTML and inline CSS for maximum compatibility with TCPDF.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Card</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        .header { text-align: center; margin-bottom: 20px; }
        .school-name { font-size: 18pt; font-weight: bold; }
        .report-title { font-size: 12pt; }
        .student-info-table { width: 100%; margin-bottom: 15px; }
        .student-info-table td { padding: 4px; }
        .scores-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .scores-table th, .scores-table td { border: 1px solid #333; padding: 5px; }
        .scores-table th { background-color: #E0E0E0; font-weight: bold; text-align: center; }
        .summary-table { width: 100%; }
        .summary-table td { padding: 4px; }
        .remarks-section { margin-top: 15px; }
        .remarks-label { font-weight: bold; }
        .footer-section { margin-top: 20px; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
    </style>
</head>
<body>
    <!-- Header -->
    <table class="header">
        <tr>
            <td style="width:20%; text-align:left;">
                <img src="<?php echo $logoPath; ?>" width="80">
            </td>
            <td style="width:60%;">
                <div class="school-name"><?php echo htmlspecialchars($schoolName); ?></div>
                <div class="report-title">END OF <?php echo strtoupper(htmlspecialchars($batch['term'])); ?> <?php echo htmlspecialchars($batch['year']); ?> REPORT</div>
            </td>
            <td style="width:20%; text-align:right;">
                <img src="<?php echo $studentPhotoPath; ?>" width="80">
            </td>
        </tr>
    </table>

    <!-- Student Info -->
    <table class="student-info-table">
        <tr>
            <td width="15%"><span class="text-bold">NAME:</span></td>
            <td width="85%"><?php echo strtoupper(htmlspecialchars($student['first_name'] . ' ' . ($student['other_name'] ?? '') . ' ' . $student['last_name'])); ?></td>
        </tr>
        <tr>
            <td><span class="text-bold">CLASS:</span></td>
            <td><?php echo htmlspecialchars($student['class_name'] . ' ' . $student['stream_name']); ?></td>
        </tr>
    </table>

    <!-- Scores Table -->
    <table class="scores-table">
        <thead>
            <tr>
                <th width="30%">SUBJECT</th>
                <th width="12%">B.O.T (100)</th>
                <th width="12%">M.O.T (100)</th>
                <th width="12%">E.O.T (100)</th>
                <th width="12%">GRADE</th>
                <th width="12%">INITIALS</th>
                <th width="10%">REMARK</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($subjects as $subject):
                $eot_mark = $scoresBySubject[$subject['name']]['EOT'] ?? null;
                $grade = $eot_mark !== null ? $this->getGradeFromMark($eot_mark) : '-';
                $remark = $eot_mark !== null ? $this->getRemarkFromGrade($grade) : '-';
            ?>
            <tr>
                <td><?php echo htmlspecialchars($subject['name']); ?></td>
                <td class="text-center"><?php echo ($scoresBySubject[$subject['name']]['BOT'] ?? '-'); ?></td>
                <td class="text-center"><?php echo ($scoresBySubject[$subject['name']]['MOT'] ?? '-'); ?></td>
                <td class="text-center"><?php echo ($eot_mark ?? '-'); ?></td>
                <td class="text-center"><?php echo $grade; ?></td>
                <td class="text-center"><?php echo htmlspecialchars($subject['teacher_initials'] ?? '-'); ?></td>
                <td class="text-center"><?php echo $remark; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Summary -->
    <table class="summary-table">
        <tr>
            <td width="18%"><span class="text-bold">Total Marks:</span></td>
            <td width="32%"><?php echo htmlspecialchars($summary['total_marks']); ?> / <?php echo (count($subjects) * 100); ?></td>
            <td width="18%"><span class="text-bold">Aggregates:</span></td>
            <td width="32%"><?php echo htmlspecialchars($summary['aggregate_points']); ?></td>
        </tr>
        <tr>
            <td><span class="text-bold">Division:</span></td>
            <td><?php echo htmlspecialchars($summary['division']); ?></td>
            <td><span class="text-bold">Position:</span></td>
            <td><?php echo htmlspecialchars($summary['position_in_stream']); ?> out of <?php echo $totalStudentsInStream; ?></td>
        </tr>
    </table>

    <!-- Remarks -->
    <div class="remarks-section">
        <p><span class="remarks-label">Class Teacher's Remarks:</span> <?php echo htmlspecialchars($summary['class_teacher_remarks']); ?></p>
        <p><span class="remarks-label">Headteacher's Remarks:</span> <?php echo htmlspecialchars($summary['headteacher_remarks']); ?></p>
    </div>

    <!-- Footer -->
    <div class="footer-section">
        <table width="100%">
            <tr>
                <td width="50%"><span class="text-bold">This Term Ended On:</span> <?php echo $batch['term_end_date'] ? date('d-m-Y', strtotime($batch['term_end_date'])) : 'N/A'; ?></td>
                <td width="50%"><span class="text-bold">Next Term Begins On:</span> <?php echo $batch['next_term_begin_date'] ? date('d-m-Y', strtotime($batch['next_term_begin_date'])) : 'N/A'; ?></td>
            </tr>
        </table>
        <br><br>
        <div class="text-center text-bold"><?php echo htmlspecialchars($schoolMotto); ?></div>
    </div>
</body>
</html>
