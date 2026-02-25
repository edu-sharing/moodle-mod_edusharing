<?php
namespace mod_edusharing\grading;

use cm_info;
use context_module;
use stdClass;

class Grader
{
    /** No automathic grading using attempt results. */
    const GRADEMANUAL = 0;

    /** Use highest attempt results for grading. */
    const GRADEHIGHESTATTEMPT = 1;

    /** Use average attempt results for grading. */
    const GRADEAVERAGEATTEMPT = 2;

    /** Use last attempt results for grading. */
    const GRADELASTATTEMPT = 3;

    /** Use first attempt results for grading. */
    const GRADEFIRSTATTEMPT = 4;

    private stdClass $instance;

    private string $idnumber;

    public function __construct(stdClass $instance, string $idnumber = '') {
        $this->instance = $instance;
        $this->idnumber = $idnumber;
    }

    /**
     * Creates or updates grade item for the given mod_edusharing instance.
     *
     * @param mixed $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
     * @return int 0 if ok, error code otherwise
     * @throws \coding_exception
     */
    public function grade_item_update($grades = null): int {
        global $CFG;
        require_once($CFG->libdir.'/gradelib.php');

        $item = [];
        $item['itemname'] = clean_param($this->instance->name, PARAM_NOTAGS);
        if (!empty($this->idnumber)) {
            $item['idnumber'] = $this->idnumber;
        }

        if ($this->instance->grade > 0) {
            $item['gradetype'] = GRADE_TYPE_VALUE;
            $item['grademax']  = $this->instance->grade;
            $item['grademin']  = 0;
        } else if ($this->instance->grade < 0) {
            $item['gradetype'] = GRADE_TYPE_SCALE;
            $item['scaleid']   = -$this->instance->grade;
        } else {
            $item['gradetype'] = GRADE_TYPE_NONE;
        }

        if ($grades === 'reset') {
            $item['reset'] = true;
            $grades = null;
        }

        return grade_update('mod/edusharing', $this->instance->course, 'mod',
            'edusharing', $this->instance->id, 0, $grades, $item);
    }

    /**
     * Delete grade item for given mod_edusharing instance.
     *
     * @return int|null Returns GRADE_UPDATE_OK, GRADE_UPDATE_FAILED, GRADE_UPDATE_MULTIPLE or GRADE_UPDATE_ITEM_LOCKED
     */
    public function grade_item_delete(): ?int {
        global $CFG;
        require_once($CFG->libdir.'/gradelib.php');

        return grade_update('mod/edusharing', $this->instance->course, 'mod', 'edusharing',
            $this->instance->id, 0, null, ['deleted' => 1]);
    }

    /**
     * Update grades in the gradebook.
     *
     * @param int $userid Update grade of specific user only, 0 means all participants.
     * @throws \coding_exception
     */
    public function update_grades(int $userid = 0): void {
        // Scaled and none grading doesn't have grade calculation.
        if ($this->instance->grade <= 0) {
            $this->grade_item_update();
            return;
        }
        // Populate array of grade objects indexed by userid.
        $grades = $this->get_user_grades_for_gradebook($userid);

        if (!empty($grades)) {
            $this->grade_item_update($grades);
        } else {
            $this->grade_item_update();
        }
    }

    /**
     * Get an updated list of user grades and feedback for the gradebook.
     *
     * @param int $userid int or 0 for all users
     * @return array of grade data formated for the gradebook api
     *         The data required by the gradebook api is userid,
     *                                                   rawgrade,
     *                                                   feedback,
     *                                                   feedbackformat,
     *                                                   usermodified,
     *                                                   dategraded,
     *                                                   datesubmitted
     * @throws \coding_exception
     */
    private function get_user_grades_for_gradebook(int $userid = 0): array {
        $grades = [];

        // In case of using manual grading this update must delete previous automatic gradings.
        if ($this->instance->grade_method == self::GRADEMANUAL) {
            return $this->get_user_grades_for_deletion($userid);
        }

        $scores = $this->get_users_scaled_score($userid);
        if (!$scores) {
            return $grades;
        }

        // Maxgrade depends on the type of grade used:
        // - grade > 0: regular quantitative grading.
        // - grade = 0: no grading.
        // - grade < 0: scale used.
        $maxgrade = floatval($this->instance->grade);

        // Convert scaled scores into gradebok compatible objects.
        foreach ($scores as $userid => $score) {
            $grades[$userid] = [
                'userid' => $userid,
                'rawgrade' => $maxgrade * $score->scaled,
                'dategraded' => $score->timemodified,
                'datesubmitted' => $score->timemodified,
            ];
        }

        return $grades;
    }

    /**
     * Return a relation of userid and the valid attempt's scaled score.
     *
     * The returned elements contain a record
     * of userid, scaled value, attemptid and timemodified. In case the grading method is "GRADEAVERAGEATTEMPT"
     * the attemptid will be zero. In case that tracking is disabled or grading method is "GRADEMANUAL"
     * the method will return null.
     *
     * @param int $userid a specific userid or 0 for all user attempts.
     * @return array|null of userid, scaled value and, if exists, the attempt id
     * @throws \dml_exception
     */
    private function get_users_scaled_score(int $userid = 0): ?array {
        global $DB;

        if ($this->instance->grade_method == self::GRADEMANUAL) {
            return null;
        }

        $sql = '';

        $where = 'a.edusharingid = :edusharingid';
        $params['edusharingid'] = $this->instance->id;

        if ($userid) {
            $where .= ' AND a.userid = :userid';
            $params['userid'] = $userid;
        }

        if ($this->instance->grade_method == self::GRADEAVERAGEATTEMPT) {
            $sql = "SELECT a.userid, AVG(a.scaled) AS scaled, 0 AS attemptid, MAX(timemodified) AS timemodified
                      FROM {edusharing_attempts} a
                     WHERE $where AND a.completion = 1
                  GROUP BY a.userid";
        }

        if (empty($sql)) {
            $condition = [
                self::GRADEHIGHESTATTEMPT => "a.scaled < b.scaled",
                self::GRADELASTATTEMPT => "a.attempt < b.attempt",
                self::GRADEFIRSTATTEMPT => "a.attempt > b.attempt",
            ];
            $join = $condition[$this->instance->grade_method] ?? $condition[self::GRADEHIGHESTATTEMPT];

            $sql = "SELECT a.userid, a.scaled, MAX(a.id) AS attemptid, MAX(a.timemodified) AS timemodified
                      FROM {edusharing_attempts} a
                 LEFT JOIN {edusharing_attempts} b ON a.edusharingid = b.edusharingid
                           AND a.userid = b.userid AND b.completion = 1
                           AND $join
                     WHERE $where AND b.id IS NULL AND a.completion = 1
                  GROUP BY a.userid, a.scaled";
        }

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get an deletion list of user grades and feedback for the gradebook.
     *
     * This method is used to delete all autmatic gradings when grading method is set to manual.
     *
     * @param int $userid int or 0 for all users
     * @return array of grade data formated for the gradebook api
     *         The data required by the gradebook api is userid,
     *                                                   rawgrade (null to delete),
     *                                                   dategraded,
     *                                                   datesubmitted
     * @throws \coding_exception
     */
    private function get_user_grades_for_deletion(int $userid = 0): array {
        $grades = [];

        if ($userid) {
            $grades[$userid] = [
                'userid' => $userid,
                'rawgrade' => null,
                'dategraded' => time(),
                'datesubmitted' => time(),
            ];
        } else {
            $coursemodule = get_coursemodule_from_instance('edusharing', $this->instance->id);
            $coursemodule = cm_info::create($coursemodule);
            $context = context_module::instance($coursemodule->id);
            $users = get_enrolled_users($context, 'mod/edusharing:submit');
            foreach ($users as $user) {
                $grades[$user->id] = [
                    'userid' => $user->id,
                    'rawgrade' => null,
                    'dategraded' => time(),
                    'datesubmitted' => time(),
                ];
            }
        }
        return $grades;
    }

    /**
     * Return the available grading methods.
     * @return string[] an array "option value" => "option description"
     * @throws \coding_exception
     */
    public static function get_grading_methods(): array {
        return [
            self::GRADEHIGHESTATTEMPT => get_string('grade_highest_attempt', 'mod_h5pactivity'),
            self::GRADEAVERAGEATTEMPT => get_string('grade_average_attempt', 'mod_h5pactivity'),
            self::GRADELASTATTEMPT => get_string('grade_last_attempt', 'mod_h5pactivity'),
            self::GRADEFIRSTATTEMPT => get_string('grade_first_attempt', 'mod_h5pactivity'),
            self::GRADEMANUAL => get_string('grade_manual', 'mod_h5pactivity'),
        ];
    }
}
