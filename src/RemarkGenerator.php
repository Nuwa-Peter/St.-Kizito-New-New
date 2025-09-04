<?php

class RemarkGenerator
{
    /**
     * Generates a contextual remark for the class teacher based on performance.
     *
     * @param string $lastName The student's last name.
     * @param int|null $aggregate The student's aggregate score.
     * @return string The generated remark.
     */
    public function generateClassTeacherRemark($lastName, $aggregate)
    {
        $lastName = htmlspecialchars($lastName);

        if ($aggregate === null) {
            return "{$lastName}'s results have not been calculated yet.";
        }

        if ($aggregate <= 12) {
            return "{$lastName} has demonstrated outstanding academic excellence this term. His diligence and commitment to his studies are commendable. Keep up the brilliant work!";
        } elseif ($aggregate <= 24) {
            return "{$lastName} has shown great potential and has achieved a commendable result. With a bit more focus, he can achieve even greater heights. Well done.";
        } elseif ($aggregate <= 34) {
            return "{$lastName}'s performance this term is fair, but there is significant room for improvement. He needs to apply himself more consistently across all subjects.";
        } else {
            return "{$lastName}'s result is below expectations. He must improve his attitude towards his studies and seek help where needed. Consistent effort is required.";
        }
    }

    /**
     * Generates a contextual remark for the headteacher based on performance.
     *
     * @param string $lastName The student's last name.
     * @param int|null $aggregate The student's aggregate score.
     * @return string The generated remark.
     */
    public function generateHeadTeacherRemark($lastName, $aggregate)
    {
        $lastName = htmlspecialchars($lastName);

        if ($aggregate === null) {
            return "Results are pending final review.";
        }

        if ($aggregate <= 12) {
            return "An excellent and well-deserved result. {$lastName} is a model student whose hard work is evident. The school is proud of him.";
        } elseif ($aggregate <= 24) {
            return "A good performance from {$lastName} this term. He is encouraged to continue striving for excellence in all his academic pursuits.";
        } elseif ($aggregate <= 34) {
            return "{$lastName} has the potential to do much better. He is urged to work harder and remain focused to improve his grades next term.";
        } else {
            return "This result is unsatisfactory. {$lastName} needs to take his academic work more seriously to avoid failure in the future.";
        }
    }
}
