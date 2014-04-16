<?php

namespace Mooc\UI\TestBlock\Model;

use Mooc\UI\TestBlock\Vips\Bridge as VipsBridge;

/**
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class Exercise extends \SimpleORMap
{
    /**
     * @var \Exercise The Exercise parsed by Vips
     */
    private $vipsExercise;

    /**
     * @var Solution[] The solutions, one entry per user
     */
    private $solutions;

    /**
     * @var array The solutions parsed by Vips
     */
    private $vipsSolutions;

    /**
     * @var AnswersStrategyInterface The exercise's answers strategy
     */
    private $answersStrategy;

    public function __construct($id = null)
    {
        $this->db_table = 'vips_aufgabe';

        parent::__construct($id);
    }

    /**
     * {@inheritDoc}
     */
    public function setData($data, $reset = false)
    {
        $returnValue = parent::setData($data, $reset);

        if (isset($data['ID']) && $data['ID'] !== null) {
            $type = $this->getType();
            $this->vipsExercise = new $type($this->Aufgabe, $this->ID);

            $this->answersStrategy = AnswersStrategy::getStrategy($this->vipsExercise);
        }

        return $returnValue;
    }

    /**
     * Returns the Exercise type.
     *
     * @return string The type
     */
    public function getType()
    {
        return $this->URI;
    }

    /**
     * Returns the Exercise question.
     *
     * @return string The question
     */
    public function getQuestion()
    {
        return $this->answersStrategy->getQuestion();
    }

    /**
     * Returns the Exercise answers.
     *
     * @param Test          $test
     * @param \Seminar_User $solver User solving the Exercise
     *
     * @return array The answers
     */
    public function getAnswers(Test $test = null, \Seminar_User $solver = null)
    {
        $answers = array();
        $vipsUrl = VipsBridge::getVipsPlugin()->getPluginURL();
        $solution = null;

        if ($this->hasSolutionFor($test, $solver)) {
            $solution = $this->getVipsSolutionFor($test, $solver);
        }

        foreach ($this->answersStrategy->getAnswers() as $index => $answer) {
            $answers[] = array(
                'text' => $answer,
                'index' => $index,
                'name' => $this->answersStrategy->getName($index),
                'checked' => $this->answersStrategy->isSelected($index, $solution),
                'checked_image' => $vipsUrl.'/images/choice_checked.png',
                'unchecked_image' => $vipsUrl.'/images/choice_unchecked.png',
                'correct_answer' => $this->answersStrategy->isCorrect($index),
            );
        }

        return $answers;
    }

    /**
     * Returns a user's answers for this exercise as a part of the given test.
     *
     * @param Test          $test
     * @param \Seminar_User $solver User solving the Exercise
     *
     * @return array The user's answers
     */
    public function getUserAnswers(Test $test = null, \Seminar_User $solver)
    {
        $userAnswers = array();
        $solution = $this->getVipsSolutionFor($test, $solver);

        if ($solution === null) {
            return array();
        }

        foreach ($this->answersStrategy->getUserAnswers($solution) as $index => $answer) {
            $userAnswers[] = array(
                'index' => $index,
                'text' => $answer,
                'correct' => $this->answersStrategy->isUserAnswerCorrect($answer, $index),
                'correct_image' => \Assets::image_path('icons/16/green/accept'),
                'incorrect_image' => \Assets::image_path('icons/16/red/decline'),
            );
        }

        return $userAnswers;
    }

    /**
     * Returns the Solution for a certain test and user.
     *
     * @param Test          $test
     * @param \Seminar_User $user The user
     *
     * @return Solution The Solution or null
     */
    public function getSolutionFor(Test $test, \Seminar_User $user)
    {
        $testId = $test->getId();
        $userId = $user->cfg->getUserId();

        // search for a solution if there is no cached one
        if (!isset($this->solutions[$testId][$userId])) {
            $solution = Solution::findOneBy($test, $this, $user);
            $this->solutions[$testId][$userId] = $solution;
            $this->vipsSolutions[$testId][$userId] = null;

            if ($solution !== null) {
                $this->vipsSolutions[$testId][$userId] = $this->vipsExercise->getTagsFromXML(
                    $solution->solution, 'answer'
                );
            }
        }

        return $this->solutions[$testId][$userId];
    }

    /**
     * Checks if there is a Solution for a certain test and user.
     *
     * @param Test          $test
     * @param \Seminar_User $user The user
     *
     * @return boolean True, if there is a Solution for the given user, false
     *                 otherwise
     */
    public function hasSolutionFor(Test $test, \Seminar_User $user)
    {
        // ensure that we check for an existing solution
        $solution = $this->getSolutionFor($test, $user);

        return $solution !== null;
    }

    /**
     * Returns the Solution for a certain test and user in the Vips internal format.
     *
     * @param Test          $test
     * @param \Seminar_User $user The user
     *
     * @return array The Solution or null
     */
    public function getVipsSolutionFor(Test $test, \Seminar_User $user)
    {
        if (!$this->hasSolutionFor($test, $user)) {
            return null;
        }

        return $this->vipsSolutions[$test->getId()][$user->cfg->getUserId()];
    }

    /**
     * @return bool True, if the Exercise is a single choice Exercise, false
     *              otherwise
     */
    public function isSingleChoice()
    {
        return $this->getType() == 'sc_exercise';
    }

    /**
     * @return bool True, if the Exercise is a multiple choice Exercise, false
     *              otherwise
     */
    public function isMultipleChoice()
    {
        return $this->getType() == 'mc_exercise';
    }

    /**
     * {@inheritDoc}
     */
    public static function findThru($testId, $options)
    {
        $class = get_called_class();
        $record = new $class();
        $db = \DBManager::get();
        $stmt = $db->prepare(sprintf(
            'SELECT
              t.*
            FROM
              %s AS te
            INNER JOIN
              %s AS t
            ON
              te.%s = t.%s
            WHERE
              te.%s = :test_id
            ORDER BY
              te.position',
            $options['thru_table'],
            $record->db_table,
            $options['thru_assoc_key'],
            $options['assoc_foreign_key'],
            $options['thru_key']
        ));
        $stmt->bindValue(':test_id', $testId);
        $stmt->execute();

        $exercises = array();

        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $exercise = new $class();
            $exercise->setData($row, true);
            $exercise->setNew(false);

            $exercises[] = $exercise;
        }

        return $exercises;
    }

    /**
     * @return AnswersStrategyInterface
     */
    public function getAnswersStrategy()
    {
        return $this->answersStrategy;
    }
}
