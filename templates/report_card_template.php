<?php
/**
 * HTML template for the student report card.
 * This file is executed by CustomReportGenerator.php and all variables are passed into its scope.
 * It uses basic HTML and inline CSS for maximum compatibility with TCPDF.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Card</title>
    <style>
        body { font-family: 'dejavusans', sans-serif; font-size: 10pt; color: #333; }
        .header-table { width: 100%; border-bottom: 1px solid #6f4e37; padding-bottom: 5px; }
        .school-name { font-size: 16pt; font-weight: bold; color: #6f4e37; }
        .report-title { font-size: 11pt; font-weight: bold; }
        .student-info-table { width: 100%; margin-top: 10px; margin-bottom: 15px; border-collapse: collapse; }
        .student-info-table td { padding: 4px 0; }
        .scores-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .scores-table th, .scores-table td { border: 1px solid #999; padding: 6px; }
        .scores-table th { background-color: #f2f2f2; font-weight: bold; text-align: center; }
        .summary-table { width: 100%; }
        .summary-table td { padding: 4px; font-size: 11pt; }
        .remarks-section { margin-top: 10px; border-top: 1px solid #ccc; padding-top: 10px;}
        .remarks-label { font-weight: bold; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
    </style>
</head>
<body>
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width:20%; text-align:left;">
                <img src="<?php echo $logoPath; ?>" width="70">
            </td>
            <td style="width:60%; text-align:center;">
                <div class="school-name"><?php echo htmlspecialchars($schoolName); ?></div>
                <div class="report-title">END OF <?php echo strtoupper(htmlspecialchars($batch['term'])); ?> <?php echo htmlspecialchars($batch['year']); ?> REPORT</div>
            </td>
            <td style="width:20%; text-align:right;">
                <img src="<?php echo $studentPhotoPath; ?>" width="70">
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
                <th width="34%">SUBJECT</th>
                <th width="11%">B.O.T</th>
                <th width="11%">M.O.T</th>
                <th width="11%">E.O.T</th>
                <th width="11%">GRADE</th>
                <th width="11%">INITIALS</th>
                <th width="11%">REMARK</th>
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
        <p><span class="remarks-label">Class Teacher's Remarks:</span> <i><?php echo htmlspecialchars($summary['class_teacher_remarks']); ?></i></p>
        <p><span class="remarks-label">Headteacher's Remarks:</span> <i><?php echo htmlspecialchars($summary['headteacher_remarks']); ?></i></p>
    </div>

</body>
</html>
